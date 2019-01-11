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
    }

    function table_name()
    {
    	return 'commercial';
    }

    /*
     * redis data info
     */
    //设置商户的缓存数据
    function setCommInfo($id,$data=array()){
        if(is_array($id)){
            foreach($id["id"] as $v){
                $this->setCacheOne($v,$data);
            }
        }else{
            $rs = $this->setCacheOne($id,$data);
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
    private function setCacheOne($id,$data){
        if(empty($data)){
            $data = $this->dump(array('id'=>$id),"short_name,need_deliver,need_product,ali_appid,ali_secret,notify_tpl_id,refund_tpl_id,pay_fail_tpl_id,pay_succ_tpl_id,pay_user_id,pay_cent,status,wechat_appid,wechat_secret,wechat_mchid,wechat_key,wechat_planid,wechat_pay_succ_tpl_id,wechat_pay_fail_tpl_id,wechat_refund_tpl_id,wechat_notify_tpl_id");
        }
        if($data){
            if (!$data['status']){
                $data['status'] = 1;
            }
            $this->redis->hSet($this->com_redis_pre.$id,'update_time',date("Y-m-d H:i:s"));
            $this->redis->hSet($this->com_redis_pre.$id,'need_deliver',$data['need_deliver']);
            $this->redis->hSet($this->com_redis_pre.$id,'need_product',$data['need_product']);
            $this->redis->hSet($this->com_redis_pre.$id,'short_name',$data['short_name']);
            //$this->redis->hSet($this->com_redis_pre.$id,'ali_appid',$data['ali_appid']);
            // $this->redis->hSet($this->com_redis_pre.$id,'ali_secret',$data['ali_secret']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_user_id',$data['pay_user_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_succ_tpl_id',$data['pay_succ_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_fail_tpl_id',$data['pay_fail_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'refund_tpl_id',$data['refund_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'notify_tpl_id',$data['notify_tpl_id']);
            // $this->redis->hSet($this->com_redis_pre.$id,'pay_cent',$data['pay_cent']);
            $this->redis->hSet($this->com_redis_pre.$id,'status',$data['status']);

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
}
