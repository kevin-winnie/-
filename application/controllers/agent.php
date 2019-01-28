<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Agent extends MY_Controller {
    public $img_http  = 'http://fdaycdn.fruitday.com/';
    public $workgroup = 'commercial';
    const LOCK_LIMIT_MAX = 5;
    const RESET_PWD = "123456##!";
    public $redis;
    private $com_redis_pre = 'comercial_';
    private $open_refer = [];
    private $config_type  = array(
            array('key'=>'wechat','name'=>"微信公众号"),
            array('key'=>'alipay','name'=>"支付宝公众号")
            );
    private $groups  = array();

    function __construct() {
        parent::__construct();
        $this->load->model('admin_model');
        $this->load->model('commercial_model');
        $this->load->model('agent_model');
        $this->load->model('icon_model');
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
        $this->load->library('curl',null,'http_curl');
        $this->load->helper('config');
        $this->_pagedata['config_types'] = $this->config_type;;


        $sql = "select * from p_param WHERE  `type` = 'group_code'";
        $rs = $this->db->query($sql)->result_array();
        $group = array();
        foreach ($rs as $v){
            $group[]= array('code'=>$v['key'],'name'=>$v['value']);
        }
        $this->groups = $group;
        $refer_list = $this->db->from('refer')->get()->result_array();
        $refers = [];
        foreach($refer_list as $v){
           $refers[$v['refer']] = $v['short_name'];
        }
        $this->open_refer = $refers;
    }
    function agentAdd(){
        if($this->input->post("submit")){
            $post = $this->input->post();
            //上海鲜动为顶级代理商---->海星宝等为超级代理商（均有最高权限）
            if($this->platform_id == 123)
            {
                $high_level = 1;
            }else
            {
                //获取当前代理商等级
                $sql = " select * from p_agent WHERE id = '{$this->platform_id}' ";
                $rs = $this->db->query($sql)->row_array();
                $high_level = $rs['high_level'] + 1;
            }

            $data = array(
                'name'=>$post['name'],
                'short_name'=>$post['short_name'],
                'contacts'=>$post['contacts'],
                'phone'=>$post['phone'],
                'province'=>$post['search_province'],
                'city'=>$post['search_city'],
                'area'=>$post['search_area'],
                'address'=>$post['address'],
                'wechat_rate'=>$post['wechat_rate'],
                'alipay_rate'=>$post['alipay_rate'],
                'admin_name'=>$post['admin_name'],
                'separate_name'=>$post['separate_name'],
                'separate_pid'=>$post['separate_pid'],
                'separate_rate'=>$post['separate_rate'],
                'separate_account'=>$post['separate_account'],
                'high_agent_id'=>$this->platform_id,
                'high_level'=>$high_level,
            );
            $rs = $this->agent_model->insert($data);
            if($rs){
                $this->_pagedata["tips"] = "新增成功";
                $this->commercial_model->setCommInfo($rs);
            }else{
                $this->_pagedata["tips"] = "新增失败";
            }
        }
        $this->page('agent/agentAdd.html');
    }

    function agentList(){
        $search = $this->input->post();
        $agent_level_list = $this->commercial_model->get_agent_level_list_pt($this->platform_id,1);
        if($this->svip)
        {
            $this->_pagedata['is_svip'] = 1;
            //代理商级别
            $Agent = $this->agent_model->get_own_agents($this->platform_id);
            $agent_level_list = $this->commercial_model->get_agent_level_list($Agent,2);
        }
        $this->_pagedata['agent_level_list'] = $agent_level_list;
        if (!empty($search['name'])) {
            $where['name like '] = '%'.trim($search['name'].'%');
        }
        if($search['is_frozen'] == 1)
        {
            $where['status'] = 0;
        }elseif($search['is_frozen'] == 0)
        {
            $where['status'] = 1;
        }
        if (!empty($search['mobile'])) {
            $where['phone'] = trim($search['mobile']);
        }
        if(!empty($search['agent_name']))
        {
            $where['id'] = trim($search['agent_name']);
        }
//        echo '<pre>';print_r($where);exit;
        $where['high_agent_id'] = $this->platform_id;
        $this->title = '代理商列表';
        $this->_pagedata['search'] = $search;
        $this->_pagedata ["list"] = $this->agent_model->getList("*", $where);
        $this->page('agent/agentList.html');
    }


    function ajaxGenSadmin(){   //demo  接口
        $id = $this->input->post('id');
        $rs = $this->agent_model->dump(array('id'=>$id));
        if(!empty($rs)){
            $params = array(
                'name'        => 'agent_'.mt_rand(10,999).date('Ymdhis'),
                'pwd' => 'agent_'.mt_rand(100,999).date('Ymdhis'),
                'groupid'=>0,
                'grade'=>1,
                'platform_id'=>$id
            );
            $this->load->model('admin_model');
            $res = $this->admin_model->insertAdmin($params['name'], $params['pwd'], $params['alias'], $params['mobile'], '', $params['email'] ,$params['grade'],$id);
            if ($res > 0) {
                $this->agent_model->update(array('admin_name'=>$params['name']),array('id'=>$id));
                //自动生成所有权限组  过滤非上海鲜动、海星宝的添加设备权限
                $data['name'] = '超级管理员';
                $flag = $this->get_flag();
                if(!in_array($rs['high_level'],[0,1]))
                {
                    $flag = str_replace('37','',$flag);
                }
                $data['flag'] = $flag;
                $data['ctime'] = time();
                $data['platform_id'] = $id;
                $id = $this->admin_model->insertSgroup($data);
                if($id)
                {
                    $info['admin_id'] = $res;
                    $info['group_id'] = $id;
                    $this->admin_model->insertS_admin_group($info);
                }
                echo json_encode(array('code'=>200,'msg'=>'创建完成!'));
            } else {
                echo json_encode(array('code'=>300,'msg'=>'用户名存在!'));
            }
        }else{
            echo json_encode(array('code'=>300,'msg'=>'错误的代理商ID'));
        }
        exit;
    }
    function get_flag()
    {
        $modules = $this->function_class->getModulesXml("ModulesList");
        $options = $this->function_class->getModulesXml("OptionList");

        foreach ($modules as $module) {
            $moduleArr = array();
            $moduleArr['nodeName'] = $module->nodeValue;
            foreach ($options as $option) {
                $type = $option->getAttribute("type");
                if ($type == $module->getAttribute("value")) {
                    $value = $option->getAttribute("value");
                    $data_value[] = $value;
                }
            }
        }
        $flags = implode(",", $data_value);
        return $flags;
    }
    function ajaxResetPwd(){   //demo  接口
        $id = $this->input->post('id');
        $rs = $this->agent_model->dump(array('id'=>$id));
        if(!empty($rs)){
            $pwd = 'admin123456';
            //重置密码
            $admin = $this->admin_model->get_adminuser($rs['admin_name'],$id);
            if(!empty($admin))
            {
                $res = $this->admin_model->update_pwd($admin['id'],$pwd);
                if($res >0)
                {
                    echo json_encode(array('code'=>200,'msg'=>'重置成功!'));
                }else
                {
                    echo json_encode(array('code'=>300,'msg'=>'重置失败!'));
                }
            }else
            {
                echo json_encode(array('code'=>300,'msg'=>'该账号不存在!'));
            }
        }else{
            echo json_encode(array('code'=>300,'msg'=>'错误的商户编号'));
        }
        exit;
    }


}
