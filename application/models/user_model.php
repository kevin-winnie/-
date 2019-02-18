<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/3/24
 */

class User_model extends MY_Model
{
    function __construct(){
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    function get_list($where,$limit='',$offset='',$order='',$sort='',$where_in = ''){

        $this->c_db->select("u.id,u.mobile,u.user_name,u.reg_time,i.buy_times,i.total_money,i.open_times,u.source,u.open_id,u.register_device_id,e.name");
        $this->c_db->from('user_platform_relations upr');
        $this->c_db->join('user u',"u.id=upr.uid ",'left');
        $this->c_db->join('user_daily_info i',"u.id=i.uid",'left');
        $this->c_db->join('equipment e',"e.equipment_id=u.register_device_id","left");
        $this->c_db->where($where);
        if($where_in){
            foreach ($where_in as $k=>$v)
            {
                $this->c_db->where_in($k,$v);
            }
        }
        if($sort){
            $this->c_db->order_by($sort,$order);
        }
        if($limit){
            $this->c_db->limit($limit,$offset);
        }
        $list = $this->c_db->get()->result_array();
        $this->c_db->select("u.id,u.mobile,u.user_name,u.reg_time,i.buy_times,i.total_money,i.open_times,u.source,u.open_id,u.register_device_id");
        $this->c_db->from('user_platform_relations upr');
        $this->c_db->join('user u',"u.id=upr.uid",'left');
        $this->c_db->join('user_daily_info i',"u.id=i.uid",'left');
        $this->c_db->join('equipment e',"e.equipment_id=u.register_device_id","left");
        $this->c_db->where($where);
        if($where_in){
            foreach ($where_in as $k=>$v)
            {
                $this->c_db->where_in($k,$v);
            }
        }
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }

    function get_user_count($where,$where_in){
        $this->c_db->select("count(*) as n");
//        $this->c_db->from('user u');
//        $this->c_db->join('user_daily_info i',"u.id=i.uid",'left');
//        $this->c_db->join('equipment e',"e.equipment_id=i.register_device_id",'left');
        $this->c_db->from('user_platform_relations upr');
        $this->c_db->join('user u',"u.id=upr.uid",'left');
        $this->c_db->join('user_daily_info i',"u.id=i.uid",'left');
        $this->c_db->join('equipment e',"e.equipment_id=u.register_device_id","left");
        $this->c_db->where($where);
        if($where_in){
            foreach ($where_in as $k=>$v)
            {
                $this->c_db->where_in($k,$v);
            }
        }
        $rs = $this->c_db->get()->row_array();
        return ($rs);
    }

    function get_info_by_id($id){
        $user = $this->c_db->select('id,mobile,user_name,s_admin_id')->from('user')->where(array(
            'id'=>$id
        ))->get()->row_array();
        $this->c_db->dbprefix = '';
        $admin = $this->c_db->select('name')->from('s_admin')->where(array(
            'id'=>$user['s_admin_id']
        ))->get()->row_array();
        return array('user'=>$user,'admin'=>$admin);
    }

    //获取后台用户信息
    function get_admin_info($name){
        $this->c_db->dbprefix = '';
        $admin_info = $this->c_db->select("id")->from("s_admin")->where(array(
            'name'=>$name
        ))->get()->row_array();
        return $admin_info;
    }

    //user表更新
    function user_update($data,$where){
        return $this->c_db->update('cb_user',$data,$where);
    }

    //user_daily_info更新
    function user_daily_info_update($data,$where){
        if(!empty($data)){
            foreach ($data as $k=>$v){
                $this->c_db->set($k,"$k + $v",false);
            }
            $this->c_db->where($where);
            $this->c_db->update('cb_user_daily_info');
        }
    }

    //user_daily_info更新v2
    function user_daily_info_update_v2($data,$where){
        if(!empty($data)){
            $is_user = $this->c_db->from('cb_user_daily_info')->where($where)->get()->row_array();
            if($is_user){
                unset($data['platform_id']);
                foreach ($data as $k=>$v){
                    $this->c_db->set($k,"$k + $v",false);
                }
                $this->c_db->where($where);
                $this->c_db->update('cb_user_daily_info');
//                echo $this->c_db->last_query();
            }else{
                $data['uid'] = $where['uid'];
                $this->c_db->insert('cb_user_daily_info',$data);
//                echo $this->c_db->last_query();exit;
            }
        }
    }

    //通过手机号 获取用户id
    public function get_user_id_by_mobile($mobile){
        if(!$mobile){
            return array();
        }
        $this->c_db->from('user');
        $this->c_db->where('mobile', $mobile);
        $rs = $this->c_db->get()->result_array();
        if(empty($rs)){
            return array();
        }
        $result = array();
        foreach($rs as $k=>$v){
            $result[] = $v['id'];
        }
        return $result;
    }


    public function get_user_info($uid, $fild='*', $return=''){
        $this->c_db->select($fild);
        $this->c_db->from('user');
        $this->c_db->where('id', $uid);
        $rs = $this->c_db->get()->row_array();
        if($return){
            return $rs[$return];
        }
        return $rs;
    }

    //获取盒子注册用户数
    public function get_reg_by_eq($equipment_id, $start_time='', $end_time=''){
        $this->c_db->select('count(id) as reg_user');
        $this->c_db->from('user');
        $this->c_db->where(array('register_device_id'=>$equipment_id, 'reg_time >='=>$start_time, 'reg_time <='=>$end_time));
        $rs = $this->c_db->get()->row_array();
        return intval($rs['reg_user']);
    }


    //获取平台注册用户数
    public function get_reg_by_pl($start_time='', $end_time='', $platform_id=0 , $array=array()){
        $where = array('reg_time >='=>$start_time, 'reg_time <='=>$end_time);
        if($platform_id&&$array){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select('count(id) as reg_user');
        $this->c_db->from('user');
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        $rs = $this->c_db->get()->row_array();
        return intval($rs['reg_user']);
    }
    //获取当天新设备注册用户
    public function get_req_by_new($platform_id=0,$array=array()){
        $date = date('Y-m-d 00:00:00');
        $time = strtotime($date);
        $where = "";
        if($platform_id&&empty($array)){
            $where = " and u.platform_id=".$platform_id.' ';
        }
        if(!empty($array))
        {
            $string = "'".implode("','",$array)."'";
            $where = " and u.platform_id in ({$string})";
        }

        $sql = "select count(u.id) as reg_user from cb_user u
                join `cb_equipment` e on u.register_device_id=e.`equipment_id`
                where u.`reg_time`>='{$date}' {$where}  and (e.firstordertime is null or e.firstordertime >={$time})
                ";
        $rs = $this->c_db->query($sql)->row_array();
        return intval($rs['reg_user']);
    }




    public function get_all_admin(){
        $sql = "  select sa.id, sa.alias from cb_equipment ce join s_admin sa on sa.id=ce.admin_id  group by sa.id ";
        return $this->c_db->query($sql)->result_array();
    }


    /*
     * #会员等级脚本方法开始
     */

    //获取用户总数
    public function get_total_user_num(){
        $rs = $this->c_db->select('count(id) as num')->from('user')->get()->row_array();
        return $rs['num'];
    }

    //查询用户某段时间范围内的订单数和金额
    public function user_rank_order_info($uid, $start_time, $end_time){
        $sql = "SELECT count(o.id) AS num ,sum(o.money) AS ordermoney FROM cb_order o WHERE o.uid={$uid} AND o.order_time >= '{$start_time}' AND o.order_time <= '{$end_time}' AND o.order_status>'0'";
        $data = $this->c_db->query($sql)->row_array();
        return $data;
    }

    //查询用户有效期内订单
    public function user_rank_orders($uid,$start_time, $end_time){
        $sql = "SELECT id,money,order_time FROM cb_order o WHERE o.uid={$uid} AND o.order_time >= '{$start_time}' AND o.order_time <= '{$end_time}' AND o.order_status>'0'";
        $data = $this->c_db->query($sql)->result_array();
        return $data;
    }

    //获取用户有效期内积分值
    function user_rank_scores($uid,$start_time, $end_time){
        $sql = "SELECT sum(score) AS avalible_score FROM cb_user_rank_score WHERE uid={$uid} AND create_time >= '{$start_time}' AND create_time <= '{$end_time}'";
        $data = $this->c_db->query($sql)->row_array();
        return $data;
    }

    //获取会员等级配置的列表
    function get_user_rank_config(){
        $ranklist =  $this->config->item('user_rank');
        return $ranklist;
    }

    //更新会员的等级
    function upgrade_user_rank($filter){
        $ranklist = $this->get_user_rank_config();
        foreach ($ranklist['level'] as $value) {
            if (bccomp($filter['avalible_score'], $value['score']) != -1) {
                $user_rank = $value;
                break;
            }
        }
        return $user_rank;
    }

    //会员升级记录增加
    function add_rank_log($data){
        if(empty($data['uid']) || (empty($data['from_rank'])&&$data['from_rank']!=0) || empty($data['to_rank'])){
            return ;
        }
        $insert_data = array();
        $insert_data['uid'] = $data['uid'];
        $insert_data['from_rank'] = $data['from_rank'];
        $insert_data['to_rank'] = $data['to_rank'];
        $insert_data['time'] = date('Y-m-d H:i:s');
        return $this->c_db->insert('user_rank_log',$insert_data);
    }

    //新增user_rank_score 记录  出发升级
    function add_user_rank_score($order_id){
        $order_info = $this->c_db->from('order')->where(array('order_status >'=>0,'id'=>$order_id))->get()->row_array();
        if(empty($order_info))
            return;
        $ranklist = $this->get_user_rank_config();
        $user_rank_score = array(
            'uid' => $order_info['uid'],
            'score' => 1*$ranklist['perOrder_cent'] + $order_info['money']*$ranklist['perMoney_cent'],
            'order_id' => $order_info['id'],
            'remark' =>'购买获得',
            'create_time' => $order_info['order_time']
        );
        $r = $this->c_db->insert("user_rank_score",$user_rank_score);

        $this->recaculate_user_rank($order_info['uid']);
    }

    //计算会员当前的等级
    function recaculate_user_rank($uid){
        $s_time = date('Y-m-d',strtotime("- 12 month"));
        $to_time = date('Y-m-d');
        $rank_expire_date = date('Y-m-d',strtotime("+ 3 MONTH"));
        $user = $this->c_db->select('id,user_rank')->from('user')->where(array('id'=>$uid))->get()->row_array();
        $avalible_score = $this->user_rank_scores($user['id'], $s_time, $to_time);
        $user_rank = $this->upgrade_user_rank($avalible_score);
        if ($user_rank && $user_rank['level_id'] > $user['user_rank']) {
            $this->c_db->update('user',array('user_rank'=>$user_rank['level_id'],'rank_expire_date'=>$rank_expire_date),array('id'=>$user['id'],'user_rank <'=>$user_rank['level_id']));
            $data = array();
            $data['uid'] = $user['id'];
            $data['from_rank'] = $user['user_rank'];
            $data['to_rank'] = $user_rank['level_id'];
            $rs = $this->user_model->add_rank_log($data);
        }
    }

    /*
     * @desc 获取账户信息
     * @param $acount_id 账户id  int or array
     * @return array
     * */
    public function get_acount_info($acount_id){
        if(!$acount_id && empty($acount_id)){
            return array();
        }
        $this->c_db->from('user_acount');
        if(!is_array($acount_id)){
            $acount_id = array($acount_id);
        }
        $this->c_db->where_in('id', $acount_id);
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['id']] = $v;
        }
        return $result;
    }

    /*
     * @desc 获取用户信息
     * @param int $acount_id 账号id
     * @return array
     * */
    public function get_user_by_acount($acount_id){
        if(!$acount_id){
            return array();
        }
        $this->c_db->select('id,mobile,user_name,source,group_code');
        $this->c_db->from('user');
        $this->c_db->where(array('acount_id'=>$acount_id));
        return $this->c_db->get()->result_array();
    }

    /**
     * @desc 获取用户信息
     * @param int $uid 账号id
     * @return array
     * */
    public function get_user($uid){
        if(!$uid){
            return array();
        }
        $this->c_db->select('id,mobile,user_name,source,group_code');
        $this->c_db->from('user');
        $this->c_db->where(array('id'=>$uid));
        return $this->c_db->get()->row_array();
    }

    /*
     * @desc 获取账户信息
     * @param $acount_id 账户id  int
     * @return array
     * */
    public function get_acount($acount_id){
        if(!$acount_id){
            return array();
        }
        $this->c_db->from('user_acount');
        $this->c_db->where(array('id'=>$acount_id));
        return $this->c_db->get()->row_array();
    }


    /*
     * @desc 反推用户余额 组成比例
     * @param int $acount_id 账户id
     * @param float $n_money 账户剩余余额
     * */
    public function get_yue_libi($acount_id, $n_money){
        $withdraw = 0; //可提现金额
        $bonusMoney = 0; //须额外扣除金额（赠送金额）

        $this->load->helper('public_helper');
        $card_money = 0;
        //卡券充值
        $yue_card = $this->c_db->select('id,card_money as money,used_time')->from('recharge_cards')->where(array('acount_id'=>$acount_id))->get()->result_array();
        foreach($yue_card as $k=>$v){
            $yue_card[$k]['time'] = strtotime($v['used_time']);
            $yue_card[$k]['bonus']= 0;
            $card_money = bcadd($card_money, $v['money'], 2);
        }
        //在线充值  bonus  赠送
        $online_money = 0;
        $yue_online = $this->c_db->select('id,money,yue,(yue-money) as bonus,update_time')->from('recharge_online')->where(array('acount_id'=>$acount_id, 'status'=>2, 'is_tixian'=>0))->get()->result_array();
        foreach($yue_online as $k=>$v){
            $yue_online[$k]['time'] = strtotime($v['update_time']);
            $online_money = bcadd($online_money, $v['yue'], 2);
        }
        $yue_array = array_merge($yue_card,$yue_online);
        $yue_array = array_sort($yue_array, 'time', 'desc');
        //统计所有 入账金额
        $sum_money = bcadd($online_money, $card_money, 2);
        $fee_money = bcsub($sum_money, $n_money,2); //所有已消费金额
        if($fee_money<0) $fee_money = 0;

        $cut_money = bcsub($card_money,$fee_money,2);
        $cut_money<0 and $cut_money = 0;

        foreach ($yue_array as $key => $value) {
            if(bccomp($n_money, bcadd($value['money'], $value['bonus'],2),2) >= 0){
                $withdraw = bcadd($withdraw, $value['money'],2);
                $bonusMoney = bcadd($bonusMoney, $value['bonus'],2);
                $n_money = bcsub($n_money, bcadd($value['money'], $value['bonus'],2),2);
            }else{
                if($value['bonus'] == 0){
                    $money = min($value['money'],$n_money);
                    $bonus = 0;
                }else{
                    $diff_money = bcsub($n_money, $value['bonus'],2);
                    $money = ($diff_money>0)?$diff_money:0;
                    $bonus = ($diff_money>0)?$value['bonus']:0;
                }
                $withdraw   = bcadd($withdraw, $money,2);
                $bonusMoney = bcadd($bonusMoney, $bonus,2);
                break;
            }
        }
        $data = array();
        $w_money = bcsub($withdraw, $cut_money , 2);
        $data['withdraw'] = ($w_money>0)?$w_money:0;//可提现
        $data['bonusMoney'] = $bonusMoney;////须额外扣除金额（赠送金额）
        return $data;
    }

    /*
     * @desc insert余额明细
     * @param '余额类型0:消费，2：在线充值，1：赠送 3:退款，4:卡券充值，5：提现，6:冻结'
     * */
    public function insert_acount_yue($uid, $acount_id, $desc='余额提现', $yue=0, $yue_type=5, $remarks=''){
        $acount_yue['uid']       = $uid;
        $acount_yue['acount_id'] = $acount_id;
        $acount_yue['des']       = $desc;
        $acount_yue['yue']       = $yue;
        $acount_yue['add_time']  = date('Y-m-d H:i:s');
        $acount_yue['yue_type']  = $yue_type;
        $acount_yue['remarks']   = $remarks;
        return $this->c_db->insert('user_acount_yue', $acount_yue);
    }

}