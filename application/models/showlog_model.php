<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/3/21
 * Time: 下午6:13
 */

class Showlog_model extends MY_Model
{
    function __construct(){
        parent::__construct();
    }

    public function get_user_id_by_mobile($mobile){
        $this->db->from('user');
        $this->db->where('mobile', $mobile);
        return $this->db->get()->row_array();
    }
    public function get_user_info($uid, $fild='*', $return=''){
        $this->db->select($fild);
        $this->db->from('user');
        $this->db->where('id', $uid);
        $rs = $this->db->get()->row_array();
        if($return){
            return $rs[$return];
        }
        return $rs;
    }

    /*获取订单商品*/
    public function get_order_product($order_name){
        $this->db->from('order_product');
        $this->db->where(array('order_name'=>$order_name));
        $rs = $this->db->get()->result_array();
        foreach($rs as $k=>$v){
            $rs[$k]['really_money'] = bcsub($v['total_money'], $v['dis_money'], 2);
        }
        return $rs;
    }

    /*获取订单支付状态*/
    public function get_order_pay($order_name){
        $this->db->from('order_pay');
        $this->db->where(array('order_name'=>$order_name));
        $this->db->order_by('id desc');
        return  $this->db->get()->row_array();
    }
}