<?php

/**
 * Created by PhpStorm.
 * User: wangchi
 * Date: 2018/6/29 11.04
 * Time: 上午11.04
 */

require APPPATH . 'libraries/ApiController.php';
class Openapi extends ApiController
{
    function __construct()
    {
        parent::__construct();

    }

    function get_openapi_config(){
        $posts = $this->input->post();
        $this->check_is_null($posts["app_id"],"缺少必要入参.");
        $rs = $this->db->from('openapi_config')->where('app_id',$posts["app_id"])->get()->row_array();
      //  error_log($posts['app_id'].var_export($rs,1),3,dirname(__FILE__).'123456789.log');
        if($rs){
            $this->succ_response($rs);
        }else{
            $this->err_response();

        }
    }
    // api $env_config['openapi_apps']倒入数据库platform
    public function import(){
        $data = $this->input->post();
      //  error_log(var_export($data,1),3,dirname(__FILE__).'12345.log');
        unset($data['timestamp']);
        unset($data['source']);
        unset($data['sign']);
        $this->db->insert('openapi_config',$data);
        $this->succ_response();
    }

}