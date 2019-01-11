<?php
/**
 * Created by PhpStorm.
 * User: wangchi
 * Date: 2018/6/27
 * Time: 16:04
 */


if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Openapi extends MY_Controller {
    private $prefix = "openapi";
    function __construct() {
        parent::__construct();
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }
    //open_api配置商户页面
    public function get_open_api($tips){
        $sql = "select * from p_param WHERE  `type` = 'group_code'";
        $rs = $this->db->query($sql)->result_array();
        $group = array();
        foreach ($rs as $v){
            $group[]= array('code'=>$v['key'],'name'=>$v['value']);
        }
        $sql1 = "select id,name from p_commercial";
        $res = $this->db->query($sql1)->result_array();
        $this->_pagedata["groups"] = $group;
        $this->_pagedata["platform_ids"] = $res;
        $this->_pagedata["tips"] = $tips;
        $this->page('openapi/open_api.html');
    }
    public function openapi_list(){
        $this->page('openapi/open_api_list.html');
    }
    public function table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $openapi_config_list = $this->db->select("o.*,c.short_name")->from("openapi_config o")->join('commercial c','o.platform_id=c.id')->order_by('o.id','desc')->limit($limit,$offset)->get()->result_array();

        foreach($openapi_config_list as &$v){
            $v['is_push'] = $v['is_push'] == 1 ? "是" : "否";
        }
        $total = $this->db->select('count(id) as num')->from("openapi_config")->get()->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $openapi_config_list
        );
        echo json_encode($result);
    }
    public function openapi_add(){
        $params = $this->input->post();
        $app_id = trim($params['app_id']);
        $openapi_config = $this->db->from("openapi_config")->where("app_id",$app_id)->get()->row_array();
        if($openapi_config){
            $this->get_open_api($tips='app_id已存在');die;
        }
        $data['app_id'] = $app_id;
        $data['secret'] = md5($app_id);
        $data['platform_id'] = $params['platform_id'];
        $data['refer'] = $params['group_code'];
        $data['group_code'] = $params['group_code'];
        $data['is_push'] = $params['is_push'];
        if($params['is_push'] == 1){
            $data['api_url'] = trim($params['api_url']);
            $data['push_params'] = json_encode([
                'app_id'=> isset($params['appid']) ? trim($params['appid']) : "",
                'app_auth_token'=> isset($params['app_auth_token']) ? trim($params['app_auth_token']) : "",
                'key'=> trim($params['key']),
                'method'=> isset($params['method']) ? trim($params['method']) : ""
            ]);
        }

//var_dump($data);die;
        $res = $this->db->insert("openapi_config",$data);
        if($res){
            $this->get_open_api($tips='新增成功');die;
        }else{
            $this->get_open_api($tips='新增失败');die;
        }
    }
    public function get_openapi_update(){
        $id = $this->uri->segment(3);
        $info = $this->db->from('openapi_config')->where('id',$id)->get()->row_array();
        if($info['is_push'] == 1){
            $info['push_params'] = json_decode($info['push_params'],1);
        }
        $this->_pagedata['row'] = $info;
        $sql = "select * from p_param WHERE  `type` = 'group_code'";
        $rs = $this->db->query($sql)->result_array();
        $group = array();
        foreach ($rs as $v){
            $group[]= array('code'=>$v['key'],'name'=>$v['value']);
        }
        $sql1 = "select id,name from p_commercial";
        $res = $this->db->query($sql1)->result_array();
        $this->_pagedata["groups"] = $group;
        $this->_pagedata["platform_ids"] = $res;
        $this->page('openapi/open_api_update.html');
    }
    public function openapi_update(){
        $params = $this->input->post();
        $app_id = trim($params['app_id']);
        $data['app_id'] = $app_id;
        $data['secret'] = md5($app_id);
        $data['platform_id'] = $params['platform_id'];
        $data['refer'] = $params['group_code'];
        $data['group_code'] = $params['group_code'];
        $data['is_push'] = $params['is_push'];
        if($params['is_push'] == 1){
            $data['api_url'] = trim($params['api_url']);
            $data['push_params'] = json_encode([
                'app_id'=> isset($params['appid']) ? trim($params['appid']) : "",
                'app_auth_token'=> isset($params['app_auth_token']) ? trim($params['app_auth_token']) : "",
                'key'=> trim($params['key']),
                'method'=> isset($params['method']) ? trim($params['method']) : ""
            ]);
        }
        $res = $this->db->update("openapi_config",$data,['id'=>$params['id']]);
        if($res){
            $this->openapi_list();
        }else{
            redirect("/openapi/get_openapi_update/".$params['id']);
        }
    }


    //开门来源
    public function refer_list(){
        $this->page('openapi/refer_list.html');
    }
    public function refer_table(){
        $rows = $this->db->from('refer')->get()->result_array();
        $total = $this->db->select('count(id) as num')->from('refer')->get()->row_array();
        $result = [
            'rows'=>$rows,
            'total'=>$total['num']
        ];
        echo json_encode($result);
    }
    public function refer_add(){
        if($this->input->post("submit")){
            $params = $this->input->post();
            if($this->db->from('refer')->where('refer',trim($params['refer']))->get()->row_array()){
                $this->_pagedata["tips"] = "来源已存在";
            }else{
                $data['refer'] = trim($params['refer']);
                $data['short_name'] = trim($params['short_name']);
                $res = $this->db->insert('refer',$data);
                if($res){
                    $this->_pagedata["tips"] = "新增成功";
                }else{
                    $this->_pagedata["tips"] = "新增失败";
                }
            }

        }
        $this->page('openapi/refer_add.html');

   }
    public function refer_update(){
        if($this->input->post("submit")){
            $params = $this->input->post();
            if($this->db->from('refer')->where('refer',trim($params['refer']))->where('id !=',$params['id'])->get()->row_array()){
                $this->_pagedata["tips"] = "来源已存在";

            }else{
                $data['refer'] = trim($params['refer']);
                $data['short_name'] = trim($params['short_name']);
                $res = $this->db->update('refer',$data,['id'=>$params['id']]);
                if($res){
                    $this->_pagedata["tips"] = "编辑成功";
                }else{
                    $this->_pagedata["tips"] = "编辑失败";

                }
            }

        }
            $id = $this->uri->segment(3);
            $id = isset($params['id']) ? $params['id'] : $id;
            $info = $this->db->from('refer')->where('id',$id)->get()->row_array();
            $this->_pagedata['row'] = $info;
            $this->page('openapi/refer_update.html');



    }
    public function config_cache(){
        //删除所有的key
        $keys = $this->redis->keys($this->prefix."*");
        $this->redis->del($keys);
        $sql = "select * from p_openapi_config";
        $list = $this->db->query($sql)->result_array();
        $app_ids = [];
        foreach ($list as $key=>$value) {
            $this->redis->set($this->prefix.$value['app_id'],json_encode($value));
            if($value['is_push'] == 1){
                $app_ids[$key] =$value['app_id'];
            }
        }
        $app_ids = array_values($app_ids);
        $this->redis->set($this->prefix,json_encode($app_ids));
    }
}
