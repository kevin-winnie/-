<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Product_model extends CI_Model
{
    function __construct() {
        parent::__construct();
        $this->table = 'product';
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
    
    function getProducts($field = "",$where = "",$offset = 0, $limit = 0)
    {
        $sql_fields = $field ? : "a.*,b.name as class_name";
    
        $sql = "SELECT {$sql_fields}
        FROM p_product AS a 
        LEFT JOIN p_product_class as b on b.id = a.class_id 
        WHERE 1 = 1 and a.status = 1 ";
        if ($where['name']){
            $sql.= " and a.product_name like '%".$where['name']."%'";
        }
        if ($where['class_id']){
            $sql.= " and a.class_id = '".$where['class_id']."'";
        }
        if ($where['tag']){
            $sql.= " and a.tags like '%".$where['tag']."%'";
        }
        if ($where['id']){
            $sql.= " and a.id = ".$where['id'];
        }
        if ($where['is_paper_order']){
            if ($where['is_paper_order'] == 1){
                $sql.= " and a.inner_code = ''";
            } else {
                $sql.= " and a.inner_code <> ''";
            }
        }
        $sql .= " ORDER BY a.id DESC";
    
        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }
    
        $res = $this->db->query($sql);
        if ($res){
            $array = $res->result_array();
            foreach ($array as $k=>$eachRes){
                if ($eachRes['inner_code'] <> ''){
                    $array[$k]['is_paper_order'] = '否';
                } else {
                    $array[$k]['is_paper_order'] = '是';
                }
                $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
                $array[$k]['show_id'] = "<a href='/products/edit/".$eachRes['id']."'>".$eachRes['id']."</a>";
            } 
        }
        
    
        return $array;
    }
    
    public function getProduct($field,$where){
        return $this->db->select($field)->from('product')->where($where)->get()->row_array();
    }
    
    public function getProductWithClass($id){
        $sql = "SELECT a.*,b.name as class_name
        FROM p_product AS a
        LEFT JOIN p_product_class as b on b.id = a.class_id
        WHERE a.status = 1 and a.id = ".$id;
        $res = $this->db->query($sql);
        $result = $res->row_array();
        return $result;
    }
    
    public function findById($id){
        $rs = $this->db->select("*")->from('product')->where(array(
            'id'=>$id
        ))->get()->row_array();
        return $rs;
    }
    
    function product_update_where_in($data,$where="",$where_in=""){
        if($where_in)
            $this->db->where_in('id',$where_in);
        if($where)
            $this->db->where($where);
        return $this->db->update('product',$data);
    }


    public function getProductNew($field,$where){
        return $this->c_db->select($field)->from('product')->where($where)->get()->row_array();
    }

    /*
     * @desc  获取商品的详情
     * @param $product_ids array
     * */
    function get_product_list($product_ids){
        if(empty($product_ids)){
            return false;
        }
        $this->load->model('product_class_model');
        $product_class = $this->product_class_model->get_children_class();
        $this->c_db->from('product');
        $this->c_db->where_in('id', $product_ids);
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['id']]['product_name'] = $v['product_name'];
            $result[$v['id']]['price']        = $v['price'];
            $result[$v['id']]['class_name']   = $product_class[$v['class_id']]['name'];
            $result[$v['id']]['class_parent'] = $product_class[$v['class_id']]['parent'];
            $result[$v['id']]['purchase_price']= $v['purchase_price'];
        }
        return $result;
    }

}

?>
