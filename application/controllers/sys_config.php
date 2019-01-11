<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sys_config extends MY_Controller {
    public $workgroup = 'commercial';

    public $config_type = array();
    public $platform_list = array();

    private $open_refer = array();


    private $groups  = array();
    function __construct() {
        parent::__construct();
        $this->load->helper('config');

        $this->load->model('commercial_model');
        $this->config_type  = array(
            array('key'=>'wechat','name'=>"微信公众号"),
            array('key'=>'alipay','name'=>"支付宝公众号")
        );
        $this->_pagedata['config_types'] = $this->config_type;;
        $this->load->model('commercial_model');
        $platform_list= $this->commercial_model->getList("*");
        foreach ($platform_list as $v ){
            $this->platform_list[$v['id']] = $v['name'];
        }
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
    public function index(){
        if($this->adminid != 1){
            die("仅超级管理员可以访问，请返回");
        }
        $this->page('sys_config/index.html');
    }
    public function platform_device(){
        $this->page('sys_config/platform_device.html');
    }
    public function add_platform_device(){
        $id = $this->uri->segment(3);
        $this->_pagedata["open_refer"] = $this->open_refer;
        if($id){
            $sql = "select * from p_config_device WHERE  id = {$id}";
            $info = $this->db->query($sql)->row_array();
            $this->_pagedata['config'] = $info;
            $config_ids = json_decode($info['config_ids'],1);
            $config['refer'] = json_decode($info['refer'],1);
            foreach ($this->open_refer as $rk=> $rf){
                if(in_array($rk,$config['refer'])){
                    $open_refer_check[$rk] = true;
                }else{
                    $open_refer_check[$rk] = false;
                }
            }
            $this->_pagedata["open_refer_check"] = $open_refer_check;
        }else{
            $info['error_msg'] = "请使用xxx扫码开门";
        }
        if(! $info['common_pr'] ){
            $info['common_pr'] = BOX_API_URL.'/public/p.html?d=DEVICEID';
        }
        $this->_pagedata["groups"] = $this->groups;
        $this->_pagedata['config'] = $info;
        $this->_pagedata['platforms'] = $this->platform_list;
        $sql = "select * from p_config";
        $configs = $this->db->query($sql)->result_array();
        $this->_pagedata['configs'] = array();
        foreach ($configs as $v){
            foreach ($config_ids as $c=>$cv){
                if(intval($v['id']) == intval($cv) ){
                    $v['checked'] = true;
                }
            }
            $this->_pagedata['configs'][$v['type']][] = $v;
        }
        $this->load->model('equipment_model');
        $this->_pagedata['qr_common_url'] = Equipment_model::QR_COMMON_URL;
        $this->page('sys_config/add_platform_device.html');
    }

    public function save_platform_device(){

        if($_POST['device_id']){
            $_POST['platform_id'] = "0";
        }else{
            $_POST['device_id'] ="0";
        }
        $_POST['config_ids'] = json_encode($_POST['config']);
        $_POST['refer'] = json_encode($_POST['refer']);
        unset($_POST['config']);
        $_POST['last_update'] = date('Y-m-d H:i:s');
        if((int)$_POST['id']==0){
            $_POST['id'] = 0;
            $_POST['create_time'] = date('Y-m-d H:i:s');
            $this->db->insert('p_config_device',$_POST);
        }
        else{
            $this->db->update('p_config_device',$_POST,array('id'=>$_POST['id']));
        }
        refresh_config_cache();
        redirect('/sys_config/platform_device');
    }
    public function platform_table($filter)
    {
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $sql = "select * from p_config_device order by id desc limit {$offset},{$limit}";
        $list = $this->db->query($sql)->result_array();
        $sql = "select * from p_config order by id desc limit {$offset},{$limit}";
        $clist = $this->db->query($sql)->result_array();
        $config_list = array();
        foreach ($clist as $c){
            $config_list[$c['id']] = $c['name'];
        }
        foreach ($list  as &$value){
            $configs = json_decode($value['config_ids'],1);
            $value['config_text'] = "";
            foreach ($configs as $k=>$v){
                $value['config_text'] .=$k.'->'.$config_list[trim($v)]."[id=$v]"."<br/>";
            }
            if($value['platform_id'] == "0"){
                $value['platform_id'] = "-";
            }else{
                $value['platform_id'] =  $this->platform_list[$value['platform_id']];
            }
            if($value['device_id'] == "0"){
                $value['device_id'] = "-";
            }
        }
        $sql = "select count(id) as num from p_config_device ";
        $total = $this->db->query($sql)->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);
    }

    public function table($filter)
    {
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $sql = "select * from p_config order by id desc limit {$offset},{$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list  as &$value){
            $configs = json_decode($value['config_text'],1);
//            var_dump($configs);
            $value['config_text'] = "";
            foreach ($configs as $k=>$v){
                $value['config_text'] .=$k.'->'.$v."<br/>";
            }
        }
        $sql = "select count(id) as num from p_config ";
        $total = $this->db->query($sql)->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);
    }

    public function add()
    {
        $this->page('sys_config/add.html');
    }
    public function edit()
    {
        $id = $this->uri->segment(3);
        $sql = "select * from p_config WHERE  id = {$id}";
        $info = $this->db->query($sql)->row_array();
        $this->_pagedata['config'] = $info;
        $this->page('sys_config/add.html');
    }
    function save(){
        $_POST['last_update'] = date('Y-m-d H:i:s');
        if((int)$_POST['id']==0){
            $_POST['id'] = 0;
            $_POST['create_time'] = date('Y-m-d H:i:s');
            $this->db->insert('p_config',$_POST);
        }
        else{
            $this->db->update('p_config',$_POST,array('id'=>$_POST['id']));
        }
        refresh_config_cache();
        redirect('/sys_config/index');
    }

    function refresh_config(){
        refresh_config_cache();
        die("succ");
    }
    function del_pd_config(){
        $id = $this->uri->segment(3);
        $sql = "delete from p_config_device WHERE  id = {$id}";
        $this->db->query($sql);
        refresh_config_cache();
        die("succ");
    }

    /**
     * 单台设备单独配置
     */
    function device(){
        $device_id = $this->uri->segment(3);
        $this->_pagedata["open_refer"] = $this->open_refer;
        if($device_id){
            $sql = "select * from p_config_device WHERE  device_id = '{$device_id}'";
            $info = $this->db->query($sql)->row_array();
            if($info){
                $this->_pagedata['config'] = $info;
                $config_ids = json_decode($info['config_ids'],1);
                $config['refer'] = json_decode($info['refer'],1);
                foreach ($this->open_refer as $rk=> $rf){
                    if(in_array($rk,$config['refer'])){
                        $open_refer_check[$rk] = true;
                    }else{
                        $open_refer_check[$rk] = false;
                    }
                }
                $this->_pagedata["open_refer_check"] = $open_refer_check;
            }else{
                $info['device_id'] = $device_id;
                $info['common_pr'] = BOX_API_URL.'/public/p.html?d=DEVICEID';
                $info['error_msg'] = "请使用xxx扫码开门";
            }

        }else{
            $info['common_pr'] = BOX_API_URL.'/public/p.html?d=DEVICEID';
            $info['error_msg'] = "请使用xxx扫码开门";
        }
        if(! $info['common_pr'] ){
            $info['common_pr'] = BOX_API_URL.'/public/p.html?d=DEVICEID';
        }
        $this->_pagedata['config'] = $info;
        $this->_pagedata['platforms'] = $this->platform_list;
        $sql = "select * from p_config";
        $configs = $this->db->query($sql)->result_array();
        $this->_pagedata['configs'] = array();
        foreach ($configs as $v){
            foreach ($config_ids as $c=>$cv){
                if(intval($v['id']) == intval($cv) ){
                    $v['checked'] = true;
                }
            }
            $this->_pagedata['configs'][$v['type']][] = $v;
        }
        $this->_pagedata["groups"] = $this->groups;

        $this->load->model('equipment_model');
        $this->_pagedata['qr_common_url'] = Equipment_model::QR_COMMON_URL;

        $this->page('sys_config/add_platform_device.html');
    }
}
