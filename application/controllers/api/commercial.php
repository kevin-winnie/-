<?php

/**
 * Created by PhpStorm.
 * User: sunyitao
 * Date: 2017/6/21
 * Time: 下午5:54
 */

require APPPATH . 'libraries/ApiController.php';
class Commercial extends ApiController
{
    function __construct()
    {
        parent::__construct();
        $this->load->model("commercial_model");
    }

    function update_cache(){
        $posts = $this->input->post();
        $this->check_is_null($posts["id"],"缺少必要入参.");
        $rs = $this->commercial_model->setCommInfo($posts["id"]);
        $this->succ_response($rs);
    }

    function refresh_cache(){
        $this->load->helper('config');
        $rs = refresh_config_cache();
        $this->succ_response($rs);
    }

    function get_commercial(){
        $id = $this->input->post('id');
        $this->db->select('short_name,kf_tel,msg_title,img_banner,qr_logo,kf_tel');
        $this->db->from('commercial');
        $this->db->where(array('id'=>$id));
        $rs = $this->db->get()->row_array();
        $this->succ_response($rs);
    }
}