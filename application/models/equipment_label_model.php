<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Equipment_label_model extends CI_Model
{
    function __construct() {
        parent::__construct();
        $this->table = 'equipment_label';
        $this->c_db = $this->load->database('citybox_master', TRUE);

    }
    
    //获取实时库存
    function getStock($equipment_id)
    {
        $sql_fields =  "t2.product_id,COUNT(t2.product_id) AS count_num,t3.product_name,t3.price,t3.preservation_time";
    
        $sql = "SELECT {$sql_fields}
        FROM cb_equipment_label t1 
        LEFT JOIN cb_label_product t2 ON t1.label = t2.label  
        LEFT JOIN cb_product t3 ON t2.product_id = t3.id
        WHERE t1.status = 'active' ";
        if ($equipment_id){
            $sql.= " and t1.equipment_id = '".$equipment_id."' and t2.product_id <> ''";
        }
        $sql .= " group by t2.product_id";
    
        $res = $this->c_db->query($sql);
        $array = $res->result_array();
        return $array;
    }

    //获取平台实时库存
    function getStock_platform(){
        $sql = "select  count(el.id) as stock , count(DISTINCT(lp.`product_id`)) as stock_p from `cb_equipment_label` as el  left join `cb_label_product` as lp on el.`label`=lp.`label`  where el.status='active' ";
        return $this->c_db->query($sql)->row_array();
    }
    
    //获取盒子当前的库存
    function get_eq_stock($equipment_id){
        $this->c_db->select('count(id) as stock');
        $this->c_db->from('equipment_label');
        $this->c_db->where(array('status'=>'active', 'equipment_id'=>$equipment_id));
        $rs = $this->c_db->get()->row_array();
        return intval($rs['stock']);
    }

    function get_stock_product(){
        $sql = "select  count(el.id) as stock , lp.product_id as product_id from `cb_equipment_label` as el  join `cb_label_product` as lp on el.`label`=lp.`label`  where el.status='active'  group by lp.product_id";
        $rs = $this->c_db->query($sql)->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['product_id']] = $v['stock'];
        }
        return $result;
    }

    function get_eq_stock_all(){
        $this->c_db->select('count(id) as stock, equipment_id');
        $this->c_db->from('equipment_label');
        $this->c_db->where(array('status'=>'active'));
        $this->c_db->group_by('equipment_id');
        $rs = $this->c_db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']] = intval($v['stock']);
        }
        return $tmp;
    }

}

?>
