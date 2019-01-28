<?php

/**
 * Class Reconciliation
 * 每日自动化计算代理商系统账目
 */
class Reconciliation extends CI_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->ci = &get_instance();
        $this->load->model('order_model');
        $this->load->model('equipment_model');
        $this->load->model('deliver_model');
    }

    /**
     * 自动化同步脚本
     */
    public function index()
    {
        //获取所有代理商
        $sql = " select * from p_agent";
        $rs = $this->db->query($sql)->result_array();
        if(!empty($rs))
        {
            foreach($rs as $key=>$val)
            {
                //数据源  每个代理商下的直营代理

            }
        }
        echo '<pre>';print_r($rs);exit;
    }
}