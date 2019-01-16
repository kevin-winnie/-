<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Agent_model extends MY_Model
{
    public $redis;
    private $com_redis_pre = 'agent_';

    function __construct()
    {
        parent::__construct();
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    function table_name()
    {
    	return 'agent';
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
            $data = $this->dump(array('id'=>$id),"short_name,status");
        }
        if($data){
            if (!$data['status']){
                $data['status'] = 1;
            }
            $this->redis->hSet($this->com_redis_pre.$id,'update_time',date("Y-m-d H:i:s"));
            $this->redis->hSet($this->com_redis_pre.$id,'short_name',$data['short_name']);
            $this->redis->hSet($this->com_redis_pre.$id,'status',$data['status']);
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

    public function get_all_agents($id){
        $this->db->select('*');
        $this->db->from('agent');
        $this->db->where(array('high_agent_id'=>$id));
        $rs = $this->db->get()->result_array();
        return $rs;
    }

    public function get_commercial($id){
        $this->db->select('*');
        $this->db->from('commercial');
        $this->db->where(array('high_agent_id'=>$id));
        $rs = $this->db->get()->result_array();
        return $rs;
    }


    public function get_own_agents($id){
        $this->db->select('*');
        $this->db->from('agent');
        $this->db->where(array('id'=>$id));
        $rs = $this->db->get()->row_array();
        return $rs;
    }


}
