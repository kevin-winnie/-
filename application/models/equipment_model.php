<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Equipment_model extends CI_Model
{
    private $secret_v2 = 'd50b6a5ff6ff4a3j814y6f6b97ec62ab';
    public $redis;
    const QR_COMMON_URL = [
        ['name' => '魔盒CITYBOX', 'value' => 'https://api.icitybox.cn/public/p.html?d=DEVICEID', 'error' => '/public/index.html'],
        ['name' => 'KA', 'value' => 'https://api.icitybox.cn/ka/p.html?d=DEVICEID', 'error' => '/ka/index.html'],
        ['name' => '天天果园', 'value' => 'https://api.icitybox.cn/pf/p.html?d=DEVICEID', 'error' => 'http://a.app.qq.com/o/simple.jsp?pkgname=com.ttxg.fruitday'],
        ['name' => '便利圈', 'value' => 'https://api.icitybox.cn/bianli/p.html?d=DEVICEID', 'error' => '/bianli/index.html'],
        ['name' => '开猫', 'value' => 'https://api.icitybox.cn/km/p.html?d=DEVICEID', 'error' => '/km/index.html'],
        ['name' => '钱大妈', 'value' => 'https://api.icitybox.cn/qdm/p.html?d=DEVICEID', 'error' => '/qdm/index.html'],
        ['name' => '五芳斋', 'value' => 'https://api.icitybox.cn/wfz/p.html?d=DEVICEID', 'error' => '/wfz/index.html'],
        ['name' => '加盟商', 'value' => 'https://api.icitybox.cn/wap/p.html?d=DEVICEID', 'error' => '/wap/index.html'],
        ['name' => '好菜集', 'value' => 'https://api.icitybox.cn/hcj/p.html?d=DEVICEID', 'error' => '/hcj/index.html'],
    ];

    function __construct() {
        parent::__construct();
        $this->table = 'equipment';
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
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
    
    function getEquipments($field = "",$where = "",$offset = 0, $limit = 0, $joinWhere_is_hidden = null)
    {
        $sql_fields = $field ? : "a.*,b.name as platform_name ";
    
        $sql = "SELECT {$sql_fields}
        FROM p_equipment AS a ";
        $sql .= " LEFT JOIN p_commercial as b on b.id = a.platform_id ";
        $sql .= " WHERE 1 = 1 ";
        if ($joinWhere_is_hidden != null) {
            $joinWhere_is_hidden = intval($joinWhere_is_hidden);
            // $sql .= " and b.status = {$joinWhere_status} ";
            $sql .= " AND (CASE WHEN a.platform_id > 0 THEN b.is_hidden = {$joinWhere_is_hidden} WHEN a.platform_id = 0 THEN b.is_hidden IS NULL END) ";
        }
        if ($where['status'] || $where['status'] === 0){
            $sql.= " and a.status = ".$where['status'];
        }
        if ($where['equipment_id']){
            $sql.= " and a.equipment_id like '%".$where['equipment_id']."%'";
        }
        if ($where['platform_id']){
            $sql.= " and a.platform_id = ".$where['platform_id'];
        }
        if ($where['code']){
            $sql.= " and a.code = '".$where['code']."'";
        }
        if ($where['start_time']){
            $sql.= " and a.created_time >= '".$where['start_time']."'";
        }
        if ($where['end_time']){
            $sql.= " and a.created_time <= '".$where['end_time']."'";
        }
        $sql .= " ORDER BY a.id ASC";
        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }
        $res = $this->db->query($sql);
        $array = $res->result_array();
        foreach ($array as $k=>$eachRes){
            if ($eachRes['status'] == 1){
                $array[$k]['status_name'] = '启用';
            } elseif ($eachRes['status'] == 0){
                $array[$k]['status_name'] = '停用';
            } elseif ($eachRes['status'] == 99){
                $array[$k]['status_name'] = '报废';
            }
            $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
            
            $has_cache = $this->redis->hGet("pro_expire_hash",'eq_id_'.$eachRes['equipment_id']);
        
            $remarks = '';
            if($has_cache){
                $remarks = "有<span style='color:red;font-weight: bold;'>".(json_decode($has_cache,1)['total_num'])."</span>个过保鲜期商品;";
            }
            $array[$k]['remarks'] = $remarks;
        }
    
        return $array;
    }



    //所有开启的盒子 status：1
    public function get_all_box(){
        $this->db->select('*');
        $this->db->from('equipment');
        $this->db->where(array('status'=>1));
        $rs = $this->db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['equipment_id']] = $v;
        }
        return $result;
    }
    function findByBoxId($equipment_id){
        $rs = $this->db->select("*")->from('equipment')->where(array(
            'equipment_id'=>$equipment_id
        ))->get()->row_array();

        return $rs;
    }
    
    function findByBoxCode($code,$id = ''){
        if ($id == ''){
            $where = array('code'=>$code);
        } else {
            $where = array('code'=>$code,'id <>'=>$id);
        }
       
        $rs = $this->db->select("*")->from('equipment')->where($where)->get()->row_array();
        return $rs;
    }
    
    function findById($id){
        $this->db->dbprefix = '';
        $rs = $this->db->select("*")->from('p_equipment')->where(array(
            'id'=>$id
        ))->get()->row_array();
        return $rs;
    }
    
    function getInsertId(){
        $rs = $this->db->select("id")->from('equipment')->order_by('id desc')->get()->row_array();
        if (!$rs){
            return 1;
        } else {
            return $rs['id']+1;
        }
    }
    
    function getLastTime(){
        $rs = $this->db->select("created_time")->from('equipment')->order_by('created_time desc')->get()->row_array();
        if (!$rs){
            return '';
        } else {
            return $rs['created_time'];
        }
    }
    
    function insertData($data){
        return $this->db->insert('equipment',$data);
    }
    
	

    public function create_sign_v2($params) {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . SECRET_V2), 0, -1) . 'w');
        return $sign;
    }
    
    public function get_equipments(){
        $this->db->select('*');
        $this->db->from('equipment');
        $this->db->order_by('id asc');
        return $this->db->get()->result_array();
    }

//    public function create_sign_v2($params) {
//        ksort($params);
//        $query = '';
//        foreach ($params as $k => $v) {
//            $query .= $k . '=' . $v . '&';
//        }
//        $sign = md5(substr(md5($query . $this->secret_v2), 0, -1) . 'w');
//        return $sign;
//    }


    public function dump($filter,$cols='*'){
        $this->db->select($cols);
        $this->db->where($filter);
        $this->db->from("equipment");
        $this->db->limit(1,0);
        $list = $this->db->get()->row_array();
        return $list;
    }

    function get_box_no($data, $field=''){
        if($data['province']){
            $where['province'] = $data['province'];
        }
        if($data['city']){
            $where['city'] = $data['city'];
        }
        if($data['area']){
            $where['area'] = $data['area'];
        }
        if($data['code']){
            $where['code'] = $data['code'];
        }
        if($data['name']){
            $where['name like'] = '%'.$data['name'].'%';
        }
        if($data['equipment_id']){
            $where['equipment_id'] = $data['equipment_id'];
        }
        if($data['replenish_location']){
            $where['replenish_warehouse'] = $data['replenish_location'];
        }
        if($data['admin_id']){
            $where['admin_id'] = $data['admin_id'];
        }
        if($data['status']){
            $where['status'] = $data['status'];
        }
        if(empty($where)){
            return array();
        }
        $this->db->from('equipment');
        $this->db->where($where);
        if($data['address']){
            $this->db->like('address', $data['address']);
        }
        $rs = $this->db->get()->result_array();
        if($field){
            $tmp = array();
            foreach($rs as $k=>$v){
                $tmp[] = $v[$field];
            }
            return $tmp;
        }
        return $rs;
    }
    //组装设备
    function add_assemble($params,$table='assemble_equipment'){
        $equipment_id = $params['equipment_id'];
        $this->c_db  = $this->load->database('citybox_master', TRUE);
        $eq = $this->c_db->from('equipment')->where('equipment_id',$equipment_id)->get()->row_array();
        if($eq){
            $params['platform_id'] = $eq['platform_id'];
        }
        return $this->db->insert($table,$params);
    }
    function update_assemble($params,$id,$table='assemble_equipment'){
        return $this->db->where('id',$id)->update($table,$params);
    }
    function assemble_table($where,$sort,$order,$offset,$limit){
        $sql = 'select e.*,a.alias as admin_name from p_assemble_equipment as e join s_admin as a on e.admin_id=a.id '.$where.' ORDER BY '.$sort.' '.$order.' LIMIT '.$offset.','.$limit;

        return $this->db->query($sql)->result_array();
    }
    public function pault_table($where,$sort,$order,$offset,$limit){

        $sql = 'select p.*,a.contacts,a.phone from p_pault as p LEFT JOIN p_clue_equipment as c on p.equipment_id=c.equipment_id LEFT JOIN p_clue as a on a.clue_id=c.clue_id '.$where.' ORDER BY '.$sort.' '.$order.' LIMIT '.$offset.','.$limit;

        return $this->db->query($sql)->result_array();
    }
}

?>
