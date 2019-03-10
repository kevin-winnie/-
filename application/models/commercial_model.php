<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Commercial_model extends MY_Model
{
    public $redis;
    private $com_redis_pre = 'comercial_';

    function __construct()
    {
        parent::__construct();
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
        $this->p_db = $this->load->database('platform_master', TRUE);
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    function table_name()
    {
    	return 'commercial';
    }

    /*
     * redis data info
     */
    //设置商户的缓存数据
    function setCommInfo($id,$data=array(),$platform_rs_id){
        if(is_array($id)){
            foreach($id["id"] as $v){
                $this->setCacheOne($v,$data);
            }
        }else{
            $rs = $this->setCacheOne($id,$data,$platform_rs_id);
            return $rs;
        }
    }

    /*redis中存储的商户信息 同数据库字段名；  update_time为更新的时间
     * data  = array(
     *      "update_time",
     *      "need_deliver",
     *      "need_product",
     *      "ali_appid",
     *      "ali_secret",
     *      "pay_user_id",
     *      "pay_cent",
     *      "status",
     * );
     */
    private function setCacheOne($id,$data,$platform_rs_id){
        if(empty($data)){
            $data = $this->dump(array('id'=>$id),"short_name,need_deliver,need_product,ali_appid,ali_secret,notify_tpl_id,refund_tpl_id,pay_fail_tpl_id,pay_succ_tpl_id,pay_user_id,pay_cent,status,wechat_appid,wechat_secret,wechat_mchid,wechat_key,wechat_planid,wechat_pay_succ_tpl_id,wechat_pay_fail_tpl_id,wechat_refund_tpl_id,wechat_notify_tpl_id");
        }
        if($data){
            if (!$data['status']){
                $data['status'] = 1;
            }
            $this->redis->hSet($this->com_redis_pre.$platform_rs_id,'update_time',date("Y-m-d H:i:s"));
            $this->redis->hSet($this->com_redis_pre.$platform_rs_id,'need_deliver',$data['need_deliver']);
            $this->redis->hSet($this->com_redis_pre.$platform_rs_id,'need_product',$data['need_product']);
            $this->redis->hSet($this->com_redis_pre.$platform_rs_id,'short_name',$data['short_name']);
            //$this->redis->hSet($this->com_redis_pre.$id,'ali_appid',$data['ali_appid']);
            // $this->redis->hSet($this->com_redis_pre.$id,'ali_secret',$data['ali_secret']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_user_id',$data['pay_user_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_succ_tpl_id',$data['pay_succ_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_fail_tpl_id',$data['pay_fail_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'refund_tpl_id',$data['refund_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'notify_tpl_id',$data['notify_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_cent',$data['pay_cent']);
            $this->redis->hSet($this->com_redis_pre.$platform_rs_id,'status',$data['status']);

            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_appid',$data['wechat_appid']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_secret',$data['wechat_secret']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_mchid',$data['wechat_mchid']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_key',$data['wechat_key']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_planid',$data['wechat_planid']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_pay_succ_tpl_id',$data['wechat_pay_succ_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_pay_fail_tpl_id',$data['wechat_pay_fail_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_refund_tpl_id',$data['wechat_refund_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'wechat_notify_tpl_id',$data['wechat_notify_tpl_id']);
            return $data;
        }else{
            return false;
        }

    }
    
    public function get_all_platforms(){
        $this->db->select('*');
        $this->db->from('commercial');
        $this->db->where(array('is_hidden'=>0));
        $rs = $this->db->get()->result_array();
        return $rs;
    }
    public function get_platform($id){
        $this->db->select('*');
        $this->db->from('commercial');
        $this->db->where(array('status'=>1,"id"=>$id));
        $rs = $this->db->get()->row_array();
        return $rs;
    }

    public function get_platform_commercial($id)
    {
        $this->p_db->select('*');
        $this->p_db->from('commercial');
        $this->p_db->where(array('status'=>1,"id"=>$id));
        $rs = $this->p_db->get()->row_array();
        return $rs;
    }

    public function platform_insert($data)
    {
        $this->p_db->insert('commercial',$data);
        return $this->p_db->insert_id();
    }

    public function get_commercial_list($agent_id,$type= 0)
    {
        $this->db->select('*');
        $this->db->from('commercial');
        $this->db->where(array('high_agent_id'=>$agent_id));
        $rs = $this->db->get()->result_array();
        if($type == 0)
        {
            $rs = array_column($rs,'platform_rs_id');
        }
        return $rs;
    }


    /**
     * 通用的获取下级所有代理商及商户
     * @param $agent_id
     * @return array
     * $type = 1 商户  $type = 2 代理商
     */
    public function get_agent_level_list($agent,$type=1,$high_level = '')
    {
        //上海鲜动
        if($agent['high_level'] == 0)
        {
            $sql = " select * from p_agent WHERE id != '{$agent['id']}' ";
            if($high_level)
            {
               $sql .= " and high_level = '{$high_level}'";
            }
            $rs = $this->db->query($sql)->result_array();
            if($type == 2)
            {
                return $rs;
            }elseif($type == 3)
            {
                $array1['id'] = $agent['id'];
                $array1['name'] = $agent['name'];
                $rs[] = $array1;
                return $rs;
            }
            $all_agent = array_unique(array_column($rs,'id'));
            if(!$high_level)
            {
                $all_agent[] = $agent['id'];
            }
        }elseif($agent['high_level'] == 1)
        {//海星宝（递归吗？）
            $sql = " select * from p_agent WHERE high_agent_id = '{$agent['id']}' ";
            $rs = $this->db->query($sql)->result_array();
            $sql = " select * from p_agent WHERE id != '{$agent['id']}' and high_level not in (0,1) ";
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
            $t_info = array_merge((array)$rs,(array)$info);
            if($type == 2)
            {
                /**
                 * 代理商级别搜索 返回满足条件的信息
                 */
                if($high_level)
                {
                    foreach($t_info as $k=>$v)
                    {
                        if($v['high_level'] == $high_level)
                        {
                            $high_level_data[] = $v;
                        }
                    }
                    return $high_level_data;
                }
                return $t_info;
            }elseif($type == 3)
            {
                if(!$high_level)
                {
                    $array['id'] = $agent['id'];
                    $array['name'] = $agent['name'];
                    $t_info[] = $array;
                }
                return $t_info;
            }
            $all_agent = array_unique(array_column($t_info,'id'));
            if(!$high_level)
            {
                $all_agent[] = $agent['id'];
            }
        }
        if(empty($all_agent))
        {
            return array();
        }
        $this->db->select('*');
        $this->db->from('commercial');
        $this->db->where_in('high_agent_id', $all_agent);
        $rs = $this->db->get()->result_array();
        return $rs;
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
                        $info['name'] =  $valuetwo['name'];
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

    /**
     * 普通代理商查看自己下级代理商
     * $type = 1代理商 2商户
     */
    public function get_agent_level_list_pt($agent_id,$type=1,$agent='',$high_level)
    {
        if($agent_id == 0)
        {
            return array();
        }
        $sql = " select * from p_agent as a WHERE  a.high_agent_id = '{$agent_id}'";
        if($high_level)
        {
            $sql .= " and high_level = '{$high_level}'";
        }
        $rs = $this->db->query($sql)->result_array();
        if($type == 1)
        {
            if(!empty($agent))
            {
                $own_array['id'] =  $agent_id;
                $own_array['name'] =  $agent['name'];;
                $rs[]=$own_array;
            }
            return $rs;
        }
        $all_agent = array_unique(array_column($rs,'id'));
        if(!$high_level)
        {
            $all_agent[] = $agent_id;
        }
        if(empty($all_agent))
        {
            return array();
        }
        $this->db->select('*');
        $this->db->from('commercial');
        $this->db->where_in('high_agent_id', $all_agent);
        $rs = $this->db->get()->result_array();
        return $rs;
    }

    /**
     * 获取代理商下直营商户
     */
    public function get_commercial_list_by_agent($agent_id)
    {
        $sql = " select * from p_commercial WHERE high_agent_id = '{$agent_id}'";
        $rs = $this->db->query($sql)->result_array();
        return $rs;
    }
    /**
     * 获取代理商下下级代理
     */
    public function get_agent_list_by_agent($agent_id)
    {
        $sql = " select * from p_agent WHERE high_agent_id = '{$agent_id}'";
        $rs = $this->db->query($sql)->result_array();
        return $rs;
    }

    public function get_agent_by_commercial($high_agent_id)
    {
        $sql = " select * from p_agent WHERE id = '{$high_agent_id}'";
        $rs = $this->db->query($sql)->row_array();
        return $rs;
    }

    public function get_own_commercial($platform_id)
    {
        $sql = " select * from p_commercial WHERE id = '{$platform_id}'";
        $rs = $this->db->query($sql)->row_array();
        return $rs;
    }

    public function get_commercial_admin_id($admin_name)
    {
        $sql = " select id from s_admin WHERE name = '{$admin_name}'";
        $rs = $this->c_db->query($sql)->row_array();
        return $rs['id']?$rs['id']:0;
    }

    public function get_zhiying($key)
    {
        $sql = " select * from p_commercial WHERE high_agent_id = '{$key}'";
        $rs = $this->db->query($sql)->result_array();
        return $rs;
    }

    public function get_own_commercial_config($key)
    {
        $sql = " select a.id,a.platform_rs_id,a.alipay_account,a.alipay_realname,a.separate_rate,a.high_agent_id,b.alipay_rate,b.wechat_rate from p_commercial as a
                  LEFT JOIN p_config_device as b ON a.id = b.platform_id WHERE a.id =  '{$key}'
                  ";
        $res = $this->db->query($sql)->row_array();
        return $res;
    }


    public function get_commercila_list($where,$agent,$platform_array = array())
    {
        $sql = "SELECT *
        FROM p_commercial AS a ";
        $sql .= " WHERE 1 = 1 ";
        if ($where['name']) {
            $sql .= " and name like '%{$where['name']}%'";
        }
        if($where['status'])
        {
            $sql .= " and status = '{$where['status']}'";
        }
        if ($where['phone']) {
            $sql .= " and phone = '{$where['phone']}'";
        }
        if ($where['contacts']) {
            $sql .= " and contacts = '{$where['contacts']}'";
        }
        if($where['agent_id'])
        {
            $sql .= " and high_agent_id = '{$where['agent_id']}'";
        }
        if(empty($platform_array))
        {
            return array();
        }
        if(!empty($platform_array))
        {
            $string = "'".implode("','",$platform_array)."'";
            $sql .= " and  id in ($string)";
        }

        $res = $this->db->query($sql);
        $array = $res->result_array();
        return $array;
    }

}
