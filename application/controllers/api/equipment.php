<?php

/**
 * Created by PhpStorm.
 * User: sunyitao
 * Date: 2017/6/21
 * Time: 下午5:54
 */

require APPPATH . 'libraries/ApiController.php';
class Equipment extends ApiController
{
    function __construct()
    {
        parent::__construct();
        $this->load->model("equipment_model");
    }

    function editEquipment(){
        $posts = $this->input->post();
        $this->check_is_null($posts["equipment_id"],"设备id不能为空.");
        $this->check_is_null($posts["code"],"商户编码不能为空.");
        
        $data['code'] = $posts["code"];
        if ($data['code']){
            $param['code'] = $data['code'];
        }
        $res = $this->db->update('equipment', $param, array('equipment_id'=>$posts['equipment_id']));
        
        
        if ($res)
            $this->succ_response(array("msg"=>"修改成功!"));
        else
            $this->err_response(array("msg"=>"修改失败,请联系管理员!"));
    }
    
    function editStatus(){
        $posts = $this->input->post();
        $this->check_is_null($posts["equipment_id"],"设备id不能为空.");
        $this->check_is_null($posts["status"],"状态不能为空.");
        
        if ($posts['status']){
            $param['status'] = $posts['status'];
        }
        $res = $this->db->update('equipment', $param, array('equipment_id'=>$posts['equipment_id']));
        
        
        if ($res)
            $this->succ_response(array("msg"=>"修改成功!"));
        else
            $this->err_response(array("msg"=>"修改失败,请联系管理员!"));
    }
}