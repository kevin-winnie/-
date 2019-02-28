<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/10/31
 * Time: 下午5:32
 */

class Equipment_stock_model extends CI_Model
{


    function __construct()
    {
        parent::__construct();
        $this->table = 'equipment_stock';
        $this->c_db = $this->load->database('citybox_master', TRUE);

    }

    //获取平台商品库存
    public function get_platform_stock($platform_id=0,$array= array()){
        $this->c_db->select('sum(stock) as stock, count(DISTINCT(es.`product_id`)) as stock_p');
        $this->c_db->from('equipment_stock es');
        $this->c_db->join('equipment e', 'e.equipment_id=es.equipment_id');

        if($platform_id && empty($array)){
            $where = array('e.platform_id'=>$this->platform_id);
        }else{
            $where = array('e.platform_id >'=>0);
        }
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        return $this->c_db->get()->row_array();
    }

    //获取平台商品库存
    public function get_platform_eq_stock($platform_id=0 , $array=array()){
        $this->c_db->select('sum(es.stock) as stock, es.equipment_id');
        $this->c_db->from('equipment_stock es');
        $this->c_db->join('equipment e', 'e.equipment_id=es.equipment_id');

        if($platform_id && empty($array)){
            $where = array('e.platform_id'=>$this->platform_id);
        }else{
            $where = array('e.platform_id >'=>0);
        }
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        $this->c_db->group_by('e.equipment_id');
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['equipment_id']] = $v['stock'];
        }
        return $result;
    }

    public function get_stock_product($platform_id=0, $array=array()){
        $this->c_db->select('sum(stock) as stock, product_id');
        $this->c_db->from('equipment_stock es');
        $this->c_db->join('equipment e', 'e.equipment_id=es.equipment_id');

        if($platform_id && empty($array)){
            $where = array('e.platform_id'=>$this->platform_id);
        }
//        else{
//            $where = array('e.platform_id >'=>0);
//        }
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('e.platform_id', $array);
        }
        $this->c_db->group_by('es.product_id');
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['product_id']] = $v['stock'];
        }
        return $result;
    }

}