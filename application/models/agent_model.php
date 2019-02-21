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
            $info = array_merge((array)$rs,(array)$info);
            $level = array_unique(array_column($info,'high_level'));
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


    public function get_agent_list_by_agent($agent_id)
    {
        $sql = " select * from p_agent WHERE high_agent_id = '{$agent_id}'";
        return $this->db->query($sql)->result_array();
    }

    public function getList($where,$agent,$agent_array = array())
    {
        $sql = "SELECT *
        FROM p_agent AS a ";
        $sql .= " WHERE 1 = 1 ";
        if ($where['name']) {
            $sql .= " and name like '%'.'{$where['name']}.'&'";
        }
        if($where['is_frozen'] == 1)
        {
            $sql .= " and status = 0";
        }elseif($where['is_frozen'] == 0)
        {
            $sql .= " and status = 1";
        }
        if ($where['mobile']) {
            $sql .= " and phone = '{$where['mobile']}'";
        }
        if($where['agent_name'])
        {
            $sql .= " and id = '{$where['agent_name']}'";
        }

        if($where['svip'] == -1)
        {
            $sql .= " and high_agent_id = '{$agent['id']}'";
        }
        $agent['high_level'] = 1;
        if($agent['high_level'] == 0)
        {
            $sql .= " and id != '{$agent['id']}'";
        }
        if($agent['high_level'] == 1)
        {
            $string = "'".implode("','",$agent_array)."'";
            $sql .= " and  id in ($string)";
        }
        $res = $this->db->query($sql);
        $array = $res->result_array();
        return $array;
    }

}
