<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/3/30
 */

class Order_model extends MY_Model
{
    function __construct(){
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    function getOrdersCount($where){
        $today_count = $this->c_db->select('sum(money) as total_money,count(*) as buy_times,uid')->from("order")->where($where)->group_by('uid')->get()->result_array();
        return $today_count;
    }

    function getOrdersCount_v2($where){
        $today_count = $this->c_db->select('sum(money) as total_money,count(*) as buy_times,uid')->from("order")->where($where)->group_by('uid')->get()->result_array();
        return $today_count;
    }

    /*
     * @desc 根据product_id获取订单
     * @param $product_id int 商品id
     * */
    function get_order_by_product($product_id){
        $this->c_db->from('order_product');
        if(is_numeric($product_id)){
            $this->c_db->where('product_id', $product_id);
        }else{
            $this->c_db->like('product_name', $product_id);
        }
        $rs = $this->c_db->get()->result_array();
        $tmp[] = -1;
        foreach($rs as $k=>$v){
            $tmp[]=$v['order_name'];
        }
        return $tmp;

    }


    public function get_order_discount_log($order_name){
        $this->c_db->from('order_discount_log');
        $this->c_db->where('order_name', $order_name);
        $rs = $this->c_db->get()->row_array();
        $rs['content'] = json_decode($rs['content'], true);
        return $rs;
    }

    //获取当天实时订单数
    public function get_day_order($date = '', $end_date='', $platform_id=0,$array=array()){
        $date = $date?$date:date('Y-m-d 00:00:00');
        $where = array('order_status >'=>0, 'order_time >'=>$date);
        if($end_date){
            $where['order_time <'] = $end_date;
        }
        if($platform_id && empty($array)){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("count(id) as num,  SUM(money+yue) as money,SUM(good_money) as good_money, count(DISTINCT(uid)) as user_num, SUM(qty) as qty, SUM(discounted_money) as discounted_money");
        $this->c_db->from('order');
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        return $this->c_db->get()->row_array();
    }


    public function get_hour_data($date='', $end_date='', $platform_id=0,$array=array()){
        $where = '';
        if($platform_id && empty($array)){
            $where = ' and platform_id='.$platform_id.' ';
        }
        if(!empty($array))
        {
            $string = "'".implode("','",$array)."'";
            $where = " and platform_id in ({$string})";
        }
        $end_date = $end_date?$end_date:$date;
        $sql = "  select sum(money) as total_money , time_hour from (   SELECT money,date_format(order_time, '%H') as time_hour from cb_order WHERE `order_status` > 0 AND `order_time` >= '{$date} 00:00:00' and `order_time` <= '{$end_date} 23:59:59' {$where} ) as tmp  group by time_hour";
        $rs = $this->c_db->query($sql)->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[intval($v['time_hour'])] = $v['total_money'];
        }
        return $result;
    }

    //获取盒子当天的订单
    public function get_order_by_eq($date = '', $group_by='', $platform_id=0,$array=array()){
        $date = $date?$date:date('Y-m-d 00:00:00');
        $where = array('order_status >'=>0, 'order_time >'=>$date);
        if($platform_id && empty($array)){
            $where['platform_id']=$platform_id;
        }
        $this->c_db->select("count(id) as num,  SUM(money+yue) as money,SUM(good_money) as good_money, count(DISTINCT(uid)) as user_num, box_no");
        $this->c_db->from('order');
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        if($group_by){
            $this->c_db->group_by($group_by);
        }
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['box_no']] = $v;
        }
        return $result;
    }

    //获取盒子当天的订单
    public function get_order_by_eq_day($date1 = '',$date2 = '', $group_by='', $platform_id=0 ,$array=array()){
      //  $date = $date?$date:date('Y-m-d 00:00:00');
      //  $where = array('order_status >'=>0, 'order_time >'=>$date);
        $where = array( 'order_status >'=>0,'order_time >= ' => $date1,'order_time <= ' => $date2 );
        if($platform_id && empty($array)){
            $where['platform_id']=$platform_id;
        }
        $this->c_db->select("count(id) as num,  SUM(money+yue) as money, count(DISTINCT(uid)) as user_num, box_no");
        $this->c_db->from('order');
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        if($group_by){
            $this->c_db->group_by($group_by);
        }
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['box_no']] = $v;
        }
        return $result;
    }


    //获取订单用户数
    public function get_order_user($start_time, $end_time, $platform_id=0,$array=array()){
        $where = array('order_status >'=>0, 'order_time >='=>$start_time, "order_time <="=>$end_time );
        if($platform_id && empty($array)){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select(" count(DISTINCT(uid)) as user_num");
        $this->c_db->from('order');
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        $rs = $this->c_db->get()->row_array();
        return $rs['user_num'];
    }


    //获取某盒子某天已支付订单数
    public function get_order_payed($equipment_id, $date = '', $end_date=''){
        $date = $date?$date:date('Y-m-d 00:00:00');
        $where = array('order_status'=>1, 'order_time >'=>$date, "box_no"=>$equipment_id);
        if($end_date){
            $where['order_time <'] = $end_date;
        }
        $this->c_db->select("count(id) as order_num, count(DISTINCT(uid)) as user_num");
        $this->c_db->from('order');
        $this->c_db->where($where);
        return $this->c_db->get()->row_array();
    }

    //截止某天，盒子累计用户数,出去未支付的订单
    public function get_user_num($equipment_id, $date=''){
        $this->c_db->select("count(DISTINCT(uid)) as total_user_num");
        $this->c_db->from('order');
        $this->c_db->where(array('box_no' => $equipment_id, 'order_status >' => 0, 'order_time <=' => $date.' 23:59:59'));
        $rs = $this->c_db->get()->row_array();
        return $rs['total_user_num'];
    }

    //获取平台某天库存
    public function get_platform_stock($date, $platform_id=0){
        $where = array( 'sale_date' => $date );
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("SUM(stock) as stock");
        $this->c_db->from('order_sale');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();
        return $rs['stock'];
    }

    //获取某盒子某天已支付订单数
    public function get_order_user_num($equipment_id, $start_time = '', $end_time='', $platform_id=0){
        $where = array('order_status >'=>0, 'order_time >='=>$start_time, "box_no"=>$equipment_id, 'order_time <='=>$end_time);
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("count(DISTINCT(uid)) as user_num");
        $this->c_db->from('order');
        $this->c_db->where($where);
        return $this->c_db->get()->row_array();
    }

    /*获取订单商品*/
    public function get_order_product($order_name){
        $this->c_db->from('order_product');
        $this->c_db->where(array('order_name'=>$order_name));
        return  $this->c_db->get()->result_array();
    }

    /*获取订单支付状态*/
    public function get_order_pay($order_name){
        $this->c_db->from('order_pay');
        $this->c_db->where(array('order_name'=>$order_name));
        $this->c_db->order_by('id desc');
        return  $this->c_db->get()->row_array();
    }

    /*获取订单商品*/
    public function get_order_product_new($order_name){
        $this->c_db->from('order_product');
        $this->c_db->where(array('order_name'=>$order_name));
        $rs = $this->c_db->get()->result_array();
        foreach($rs as $k=>$v){
            $rs[$k]['really_money'] = bcsub($v['total_money'], $v['dis_money'], 2);
        }
        return $rs;
    }

    //获取平台盒子某天已支付用户
    public function get_order_user_num_all( $start_time = '', $end_time=''){
        $where = array('order_status >'=>0, 'order_time >='=>$start_time, 'order_time <='=>$end_time, 'platform_id'=>$this->platform_id);
        $this->c_db->select("count(DISTINCT(uid)) as user_num, box_no");
        $this->c_db->from('order');
        $this->c_db->where($where);
        $this->c_db->group_by('box_no');
        $rs = $this->c_db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['box_no']] = $v['user_num'];
        }
        return $tmp;
    }
    /**
     * @desc 获取用户最后一单时间
     * @param array $uid
     * @return array
     * */
    public function get_user_last_order($uid){
        if(empty($uid)){
            return array();
        }
        $this->c_db->select('max(order_time) as last_buy_time,uid');
        $this->c_db->from('order');
        $this->c_db->where_in('uid', $uid);
        $this->c_db->group_by('uid');
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['uid']] =  $v['last_buy_time'];
        }
        return $result;
    }

    /**
     * @param $agent_id
     * @return array
     */
    public function get_box_list_by_agent($agent_id,$field = null)
    {
        //只推商户
        $sql = " select * from p_equipment as a WHERE a.platform_id > 0 AND a.last_agent_id = '{$agent_id}'";
        $rs = $this->db->query($sql)->result_array();
        if($field){
            $tmp = array();
            foreach($rs as $k=>$v){
                $tmp[] = $v[$field];
            }
            return $tmp;
        }
        return $rs;
    }

    /**
     * @param $agent_id
     * @return array
     */
    public function get_box_list_by_next_agent($agent_id,$field = null,$type=0)
    {
        //该代理商下级代理
        $sql = " select * from p_agent as a WHERE a.high_agent_id = '{$agent_id}'";
        $rs = $this->db->query($sql)->result_array();
        if($type == 0)
        {
            $res_id = array_column($rs,'id');
            $rs = $this->get_box_list_by_agent_array($res_id,$field);
            return $rs;
        }
        $sql = " select * from p_agent WHERE id != '{$agent_id}' and high_level not in (0,1) ";
        $member = $this->db->query($sql)->result_array();
        $res = array();
        foreach($rs as $key=>$val)
        {
            $res[] = $this->GetTeamMember($member,$val['id']);
        }
        foreach($res as $key=>$val)
        {
            foreach($val as $k=>$v)
            {
                $info[] = $v;
            }
        }
        $info = array_merge($rs,$info);
        $res_id = array_unique(array_column($info,'id'));
        $rs = $this->get_box_list_by_agent_array($res_id,$field);
        return $rs;
    }

    public function get_box_list_by_agent_array($array,$field)
    {
        //设备
        $where = array('platform_id >'=>0);
        $this->db->from('equipment');
        $this->db->where($where);
        $this->db->where_in('last_agent_id', $array);
        $list = $this->db->get()->result_array();
        if($field){
            $tmp = array();
            foreach($list as $k=>$v){
                $tmp[] = $v[$field];
            }
            return $tmp;
        }
        return $list;
    }

    /*
   *2.获取某个会员的无限下级方法
   *$members是所有会员数据表,$mid是用户的id
   */
    function GetTeamMember($members,$mid) {
        $Teams=array();//最终结果
        $mids=array($mid);//第一次执行时候的用户id
        do {
            $othermids=array();
            $state=false;
            foreach ($mids as $valueone) {
                foreach ($members as $key => $valuetwo) {
                    if($valuetwo['high_agent_id']==$valueone){
                        $info['id'] =  $valuetwo['id'];
                        $info['high_level'] =  $valuetwo['high_level'];
                        $Teams[]=$info;//找到我的下级立即添加到最终结果中
                        $othermids[]=$valuetwo['id'];//将我的下级id保存起来用来下轮循环他的下级
                        array_splice($members,$key,1);//从所有会员中删除他
                        $state=true;
                    }
                }
            }
            $mids=$othermids;//foreach中找到的我的下级集合,用来下次循环
        } while ($state==true);

        return $Teams;
    }

}
