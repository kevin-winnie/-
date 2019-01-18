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

    public function change_platform($id_string)
    {
        $sql = " select * from p_commercial as a WHERE a.high_agent_id in ({$id_string})";
        return $this->db->query($sql)->result_array();
    }

    public function high_agent_list($agent_id)
    {
        $sql = " select * from p_agent as a WHERE  a.high_agent_id = '{$agent_id}'";
        return $this->db->query($sql)->result_array();
    }


    public function get_agent_level_list($agent)
    {
        //上海鲜动
        if($agent['high_level'] == 0)
        {
            $sql = " select * from p_agent WHERE id != '{$agent['id']}' ";
            $rs = $this->db->query($sql)->result_array();
            $level = array_unique(array_column($rs,'high_level'));
        }elseif($agent['high_level'] == 1)
        {//海星宝（递归吗？）

        }
        $data_level = array();
        foreach($level as $key=>$val)
        {
            switch($val)
            {
                case 1:
                    $data_level[$key]['id'] = '1';
                    $data_level[$key]['name'] = '顶级';
                    break;
                case 2:
                    $data_level[$key]['id'] = '2';
                    $data_level[$key]['name'] = '一级';
                    break;
                case 3:
                    $data_level[$key]['id'] = '3';
                    $data_level[$key]['name'] = '二级';
                    break;
                case 4:
                    $data_level[$key]['id'] = '4';
                    $data_level[$key]['name'] = '三级';
                    break;
                case 5:
                    $data_level[$key]['id'] = '4';
                    $data_level[$key]['name'] = '四级';
                    break;
            }
        }
        return $data_level;
    }

}
