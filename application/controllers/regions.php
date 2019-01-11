<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Regions extends MY_Controller {

    public $workgroup = 'regions';

    function __construct() {
        parent::__construct();
        $this->load->model("region_model");
    }


    //查询
    public function search(){
        if($this->input->is_ajax_request()){
            $pid = $this->input->post('pid') ? $this->input->post('pid') : 0;
            if ($pid == -1){
                $this->showJson(array('status'=>'error'));
            }
            $region_list = $this->region_model->getSonRegions($pid);
            $this->showJson(array('status'=>'success','region_list'=>$region_list));
        }
        $this->showJson(array('status'=>'error'));
    }



}
