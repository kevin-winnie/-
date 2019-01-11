<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Product_class_model extends CI_Model
{
    function __construct() {
        parent::__construct();
        $this->table = 'product_class';
        $this->c_db = $this->load->database('citybox_master', TRUE);

    }
    
    function getList($where = array(),$limit = array()){
        $this->db->where($where);
        if (!empty($limit)) {
            $this->db->limit($limit['per_page'], $limit['curr_page']);
        }
        $this->db->select("*");
        $this->db->from($this->table);
        $this->db->order_by('id', 'asc');
        $query = $this->db->get();
        $res = $query->result_array();
        return $res;
    }
    
    function getProductClasses($field = "",$where = "",$offset = 0, $limit = 0)
    {
        $sql_fields = $field ? : "a.*";
    
        $sql = "SELECT {$sql_fields}
        FROM p_product_class AS a 
        WHERE 1 = 1 ";
        if (isset($where['parent_id'])){
            $sql.= " and a.parent_id = '".$where['parent_id']."'";
        }
        $sql .= " ORDER BY a.id ASC";
    
        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }
    
        $res = $this->db->query($sql);
        $array = $res->result_array();
        foreach ($array as $k=>$eachRes){
            $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
        }
    
        return $array;
    }

    //获取 所有子分类，同时返回他们的父分类
    function get_children_class(){
        $this->c_db->from($this->table);
        $this->c_db->where(array('id >'=>0));
        $rs = $this->c_db->get()->result_array();
        $parent = $children = array();
        foreach($rs as $k=>$v){
            if($v['parent_id'] == 0){
                $parent[$v['id']] = $v['name'];
            }else{
                $children[$v['id']] = $v;
            }
        }
        foreach($children as $k=>$v){
            $children[$k]['parent'] = $parent[$v['parent_id']];
        }
        return $children;
    }
    
	
}

?>
