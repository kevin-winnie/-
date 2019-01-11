<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Equipment_new_model extends CI_Model
{
    private $secret_v2 = 'd50b6a5ff6ff4a3j814y6f6b97ec62ab';
    public $redis;

    function __construct() {
        parent::__construct();
        $this->table = 'equipment';
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    function getList($where = array(),$limit = array()){
        $this->c_db->where($where);
        if (!empty($limit)) {
            $this->c_db->limit($limit['per_page'], $limit['curr_page']);
        }
        $this->c_db->select("*");
        $this->c_db->from($this->table);
        $this->c_db->order_by('id', 'asc');
        $query = $this->c_db->get();
        $res = $query->result_array();
        return $res;
    }

    function getEquipments($field = "",$where = "",$offset = 0, $limit = 0, $isCountSku=false, $order='created_time',$asc = 'desc')
    {
        $sql_fields = $field ? : "a.*,b.alias as admin_name,datediff(NOW(),FROM_UNIXTIME(case when a.firstordertime = 0 then null else a.firstordertime end)) as order_day,'正常' as heart_status ";
        //20170801新增总库存量和累计购买用户数
        $sql_fields .= ",(SELECT COUNT(eq.id) FROM cb_equipment_label AS eq WHERE eq.equipment_id = a.equipment_id AND eq.status='active') AS stock_all";
        $sql_fields .= ",(SELECT COUNT(DISTINCT(order.uid)) FROM cb_order AS `order` WHERE order.box_no = a.equipment_id AND order.order_status = 1) AS count_order_people";
        if($isCountSku){
            $sql_fields .= ", d.num, d.sku_num, f.send_date";
        }

        $todayTime = date('Y-m-d H:i:s',time()-6*60*60);

        $sql = "SELECT {$sql_fields}
        FROM cb_equipment AS a 
        LEFT JOIN s_admin as b on b.id = a.admin_id ";
        if ($where['admin_id']){
            $sql .= " LEFT JOIN cb_admin_equipment c on a.equipment_id = c.equipment_id ";
        }
//        $sql .= " LEFT JOIN (SELECT TIMESTAMPDIFF(SECOND,TIMESTAMP(MAX(`receive_time`)),TIMESTAMP(NOW())) AS receive_time,device_id FROM cb_receive_box_log WHERE msg_type = 'heart' and receive_time > '".$todayTime."' GROUP BY `device_id`) g ON g.device_id = a.equipment_id";

        //补货单的列表需要加关联表
        if($isCountSku){
            $sql .= " LEFT JOIN (select count(product_id) as num, count(distinct product_id) sku_num,equipment_id from cb_equipment_label d   LEFT JOIN cb_label_product e on d.label = e.label where d.status = 'active' group by equipment_id) d on d.equipment_id=a.equipment_id";
            $sql .= " LEFT JOIN (select max(`send_date`) as send_date,equipment_id from cb_shipping_order group by `equipment_id`) f on f.`equipment_id` = a.`equipment_id`";
        }



        $sql .= " WHERE 1 = 1 ";
        if ($where['admin_name']){
            $sql.= " and b.name = '".$where['admin_name']."'";
        }
        if ($where['name']){
            $sql.= " and a.name like '%".$where['name']."%'";
        }
        if ($where['alias']){
            $sql.= " and b.alias like '%".$where['alias']."%'";
        }
        if ($where['address']){
            $sql.= " and a.address like '%".$where['address']."%'";
        }
        if ($where['province']){
            $sql.= " and a.province = '".$where['province']."'";
        }
        if ($where['city']){
            $sql.= " and a.city = '".$where['city']."'";
        }
        if ($where['area']){
            $sql.= " and a.area = '".$where['area']."'";
        }
        if($where['replenish_location']) {
            $sql .= " and a.replenish_warehouse = '" . $where['replenish_location'] . "'";
        }
        if ($where['status'] || $where['status'] === 0){
            $sql.= " and a.status = ".$where['status'];
        }
        if ($where['equipment_id']){
            $sql.= " and a.equipment_id like '%".$where['equipment_id']."%'";
        }
        if ($where['admin_id']){
            $sql.= " and c.admin_id = ".$where['admin_id'];
        }
        //心跳

        $corn_heart_box_list_str = $this->redis->get("corn_heart_box_list");
        if($corn_heart_box_list_str){
            $corn_heart_box_list = explode(",",$corn_heart_box_list_str);
            $corn_heart_box_list = array_filter($corn_heart_box_list);//去掉空值
        }else{
            $corn_heart_box_list = array();
        }
        $corn_heart_box_list_str = join(',',$corn_heart_box_list);

        if ($where['heart_status']){
            if ($where['heart_status'] == 1){
                //正常
                if(count($corn_heart_box_list)>0){
                    foreach ($corn_heart_box_list as $cq)
                    {
                        $sql.= ' and a.equipment_id != \''.$cq.'\' ';
                    }
                }
            } elseif ($where['heart_status'] == 2){
                //异常
                if(count($corn_heart_box_list)>0){
                    $sql.= ' and (';
                    foreach ($corn_heart_box_list as $cq)
                    {
                        $sql.= '  a.equipment_id = \''.$cq.'\' OR';
                    }
                    $sql = rtrim($sql,'OR');
                    $sql.= ' )';
                }else{
                    $sql.= ' and a.equipment_id  in (0) ';
                }
            }
        }
//        $sql .= " AND a.platform_id = {$this->platform_id}";

        if($isCountSku) {
            $sql .= " GROUP BY a.`id`";
        }
        if (empty($order)){
            $sql .= " ORDER BY a.created_time desc";
        }else {
            $sql .= " ORDER BY ".$order.' '.$asc;
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }
        $res = $this->c_db->query($sql);
        $array = $res->result_array();
        foreach ($array as $k=>$eachRes){
            if ($eachRes['status'] == 1){
                $array[$k]['status_name'] = '启用';
            } elseif ($eachRes['status'] == 0){
                $array[$k]['status_name'] = '停用';
            } elseif ($eachRes['status'] == 99){
                $array[$k]['status_name'] = '报废';
            }
            if ($eachRes['send_type'] == 'motor'){
                $array[$k]['send_type_name'] = '电车';
            } else {
                $array[$k]['send_type_name'] = '汽车';
            }
            $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
            $array[$k]['firstordertime'] = $array[$k]['firstordertime'] ? date('Y-m-d H:i:s',$array[$k]['firstordertime']) : '无订单记录';
            $address = $this->get_box_address($eachRes['equipment_id']);
            $array[$k]['province_city_area'] = $address['province'].$address['city'].$address['area'];
            $array[$k]['camera'] = $eachRes['is_camera'] == 1 ? '是':'否';

            $has_cache = $this->redis->hGet("pro_expire_hash",'eq_id_'.$eachRes['equipment_id']);

            $remarks = '';
            if($has_cache){
                $remarks = "有<span style='color:red;font-weight: bold;'>".(json_decode($has_cache,1)['total_num'])."</span>个过保鲜期商品;";
            }

            //心跳

            if(in_array($eachRes['equipment_id'],$corn_heart_box_list)){
                $array[$k]['heart_status'] = '异常';
            }


            $array[$k]['remarks'] = $remarks;
        }

//        echo $sql;

        return $array;
    }

    function get_box_no($data, $field='',$field2=''){
        $where = array();//array("platform_id"=>$this->platform_id);
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
        if($data['type']){
            $where['type'] = $data['type'];
        }
        if($data['platform_id']){
            $where['platform_id'] = $data['platform_id'];
        }
        if(empty($where)){
            return array();
        }
        $this->c_db->from('equipment');
        $this->c_db->where($where);
        if($data['address']){
            $this->c_db->like('address', $data['address']);
        }
        $rs = $this->c_db->get()->result_array();
        if($field){
            $tmp = array();
            foreach($rs as $k=>$v){
                $tmp[] = $v[$field];
                if($field2){
                    $tmp[] = $v[$field2];
                }
            }
            return $tmp;
        }
        return $rs;
    }

    //所有开启的盒子 status：1
    public function get_all_box(){
        $this->c_db->select('equipment_id, name, replenish_location,type');
        $this->c_db->from('equipment');
        $this->c_db->where(array('status'=>1));
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['equipment_id']] = $v;
        }
        return $result;
    }

    //所有有admin的盒子
    public function get_all_box_admin(){
        $this->c_db->select('equipment_id, name, replenish_location, type');
        $this->c_db->from('equipment');
        $this->c_db->where(array('admin_id >'=>0));
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['equipment_id']] = $v;
        }
        return $result;
    }

    function findByBoxId($equipment_id,$need_admin_info=false){
        $rs = $this->c_db->select("*")->from('equipment')->where(array(
            'equipment_id'=>$equipment_id
//            'platform_id'=>$this->platform_id
        ))->get()->row_array();

        if($need_admin_info&&$rs){
//            $this->c_db->dbprefix = '';
            $query = "SELECT `name`,`alias`,`mobile` FROM `s_admin` WHERE `id` = {$rs['admin_id']}";
            $admin_rs = $this->c_db->query($query)->row_array();
//            $admin_rs = $this->c_db->select('*')->from('s_admin')->where(array(
//                'id'=>$rs['admin_id']
//            ))->get()->row_array();
            if ($admin_rs){
                $rs['admin_name'] = $admin_rs['name'];
                $rs['admin_alias'] = $admin_rs['alias'];
                $rs['mobile'] = $admin_rs['mobile'];
            }
        }
        return $rs;
    }

    function findByBoxCode($code,$id = ''){
        if ($id == ''){
            $where = array('code'=>$code);
        } else {
            $where = array('code'=>$code,'id <>'=>$id);
        }

        $rs = $this->c_db->select("*")->from('equipment')->where($where)->get()->row_array();
        return $rs;
    }

    function findById($id){
        $this->c_db->dbprefix = '';
        $rs = $this->c_db->select("*")->from('cb_equipment')->where(array(
            'id'=>$id
//            'platform_id'=>$this->platform_id
        ))->get()->row_array();
        if ($rs && $rs['admin_id']){
            $this->c_db->dbprefix = '';
            $admin_rs = $this->c_db->select('*')->from('s_admin')->where(array(
                'id'=>$rs['admin_id']
            ))->get()->row_array();
            if ($admin_rs){
                $rs['admin_name'] = $admin_rs['name'];
                $rs['admin_alias'] = $admin_rs['alias'];
                $rs['mobile'] = $admin_rs['mobile'];
            }
        }
        return $rs;
    }

    function getInsertId(){
        $rs = $this->c_db->select("id")->from('equipment')->order_by('id desc')->get()->row_array();
        if (!$rs){
            return 1;
        } else {
            return $rs['id']+1;
        }
    }

    function getLastTime(){
        $rs = $this->c_db->select("created_time")->from('equipment')->order_by('created_time desc')->get()->row_array();
        if (!$rs){
            return '';
        } else {
            return $rs['created_time'];
        }
    }

    function insertData($data){
        return $this->c_db->insert('equipment',$data);
    }


    public function get_box_address($equipment_id){
        $this->c_db->from('equipment');
        $this->c_db->where('equipment_id', $equipment_id);
        $rs = $this->c_db->get()->row_array();
        $ids = array($rs['province'], $rs['city'], $rs['area']);

        $this->c_db->select('AREAIDS, AREANAME');
        $this->c_db->from('sys_regional');
        $this->c_db->where_in('AREAIDS', $ids);
        $regional = $this->c_db->get()->result_array();
        $tmp = array();
        foreach($regional as $k=>$v){
            $tmp[$v['AREAIDS']] = $v['AREANAME'];
        }
        $result['province'] = $tmp[$rs['province']]?$tmp[$rs['province']]:'';
        $result['city']     = $tmp[$rs['city']]?$tmp[$rs['city']]:'';
        $result['area']     = $tmp[$rs['area']]?$tmp[$rs['area']]:'';
        $result['address']  = $rs['address'];
        return $result;
    }

    //获取门店列表
    public function get_store_list(){
        $key = 'get_store_list_';
        $has_cache = $this->redis->get($key);
        if($has_cache){
            return json_decode($has_cache, true);
        }
        if($this->platform_id == 1){
            $time = time();
            $service = 'open.getStores';
            $params = array(
                'timestamp' => $time,
                'service' => $service,
                "code"=>'',
            );
            $params['sign'] = $this->create_sign_v2($params);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, 'http://nirvana.fruitday.com/openApi');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result, true);
            curl_close($ch);
            $stores = array();
            if (!empty($result['stores'])){
                foreach ($result['stores'] as $val){
                    $stores[] = array('code'=>$val['code'],'name'=>$val['name']);
                }
            }
        }else{
            $stores[] = array('code'=>'default','name'=>'商户自建补货仓');
        }
//        array_unshift($stores,array('code'=>'default','name'=>'商户自建补货仓'));
        $this->redis->set($key, json_encode($stores), 86400*7);
        return $stores;
    }

    public function get_store_list_byCode(){
        $has_cache = $this->redis->get('get_store_list_byCode');
        if($has_cache){
            return json_decode($has_cache, true);
        }
        $time = time();
        $service = 'open.getStores';
        $params = array(
            'timestamp' => $time,
            'service' => $service,
            "code"=>'',
        );
        $params['sign'] = $this->create_sign_v2($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, 'http://nirvana.fruitday.com/openApi');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        curl_close($ch);
        $stores = array();
        if (!empty($result['stores'])){
            foreach ($result['stores'] as $val){
                $stores[$val['code']] = $val['name'];
            }
        }
        $stores['default'] = '商户自建补货仓';

        $this->redis->set('get_store_list_byCode', json_encode($stores), 86400*7);

        return $stores;
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
        $this->c_db->select('equipment_id,name,replenish_location,replenish_warehouse');
        $this->c_db->from('equipment');
//        $this->c_db->where(array('platform_id'=>$this->platform_id));
        $this->c_db->order_by('id asc');
        return $this->c_db->get()->result_array();
    }

    /*
     * @desc 根据补货仓获取盒子列表
     * @param $code_arr array 补货仓code
     * */
    public function get_equipment_by_code($code_arr){
        $this->c_db->select('equipment_id,name,replenish_warehouse');
        $this->c_db->from('equipment');
//        $this->c_db->where(array("platform_id"=>$this->platform_id));
        $this->c_db->where_in('replenish_warehouse', $code_arr);
        $this->c_db->order_by('id asc');
        $rs = $this->c_db->get()->result_array();
//        $admin_id = $this->session->userdata('sess_admin_data')["adminid"];
//        $this->load->model("admin_equipment_model");
//        $equipment_list = $this->admin_equipment_model->getList($admin_id);
//        if(!empty($rs)){
//            foreach($rs as $k=>$v){
//                if(!in_array($v['equipment_id'], $equipment_list)){
//                    unset($rs[$k]);
//                }
//            }
//        }
        return $rs;
    }

    //判断当前管理员有哪些盒子权限
    public function check_equipment($search_equipment_arr){
        $admin_id = $this->session->userdata('sess_admin_data')["adminid"];
        $this->load->model("admin_equipment_model");
        $equipment_list = $this->admin_equipment_model->getList($admin_id);
        if(!empty($search_equipment_arr)){
            foreach($search_equipment_arr as $k=>$v){
                if(!in_array($v, $equipment_list)){
                    unset($search_equipment_arr[$k]);
                }
            }
        }
        return $search_equipment_arr;
    }

    public function get_equipment_by_id($id_arr){
        $this->c_db->select('equipment_id,name');
        $this->c_db->from('equipment');
//        $this->c_db->where(array("platform_id"=>$this->platform_id));
        $this->c_db->where_in('equipment_id', $id_arr);
        $rs = $this->c_db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']] = $v['name'];
        }
        return $tmp;
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

    function getStores(){
        //获取门店列表
        $time = time();
        $service = 'open.getStores';
        $params = array(
            'timestamp' => $time,
            'service' => $service,
            "code"=>'',
        );
        $params['sign'] = $this->create_sign_v2($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, 'http://nirvana.fruitday.com/openApi');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);
        $stores = array();
        if ($result->stores){
            foreach ($result->stores as $val){
                $stores[] = array('code'=>$val->code,'name'=>$val->name);
            }
        }
        return $stores;
    }

    public function dump($filter,$cols='*'){
        $this->c_db->select($cols);
        $this->c_db->where($filter);
        $this->c_db->from("equipment");
        $this->c_db->limit(1,0);
        $list = $this->c_db->get()->row_array();
        return $list;
    }

    function get_eq_name($equipment_id, $field=''){
        $where['equipment_id'] = $equipment_id;
        $this->c_db->from('equipment');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();
        if($field){
            return $rs[$field];
        }
        return $rs;
    }

    //获取盒子负责人
    function get_eq_admin($equipment_id){
        $admin_id = $this->get_eq_name($equipment_id, 'admin_id');
        if(!$admin_id){
            return '';
        }
        $sql = "select `name` from s_admin where id={$admin_id}";
        $rs = $this->c_db->query($sql)->row_array();
        return $rs['name'];
    }

    /*
     * @desc 获取某天 首次订单的盒子
     * @param $start_time 开始时间 时间戳
     * @param $end_time 结束时间 时间戳
     * */
    function get_eq_num_first($start_time, $end_time, $platform_id=0){
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $where['status'] = 1;
        $where['firstordertime >='] = $start_time;
        $where['firstordertime <='] = $end_time;
        $this->c_db->from('equipment');
        $this->c_db->where($where);
        return $this->c_db->get()->num_rows();
    }

    /*
     * @desc 当天 首次订单的盒子
     * @param
     * */
    public function get_eq_curr($platform_id=0){
        $where = '';
        if($platform_id){
            $where = ' and platform_id='.$platform_id.' ';
        }
        $date = date('Y-m-d 00:00:00');
        $sql = "select count(DISTINCT(`box_no`)) as new_eq from cb_order where box_no in(SELECT `equipment_id` FROM (`cb_equipment`) WHERE  `status` = 1 AND `firstordertime` is null) and order_time>'{$date}' {$where}";
        $rs = $this->c_db->query($sql)->row_array();
        return intval($rs['new_eq']);
    }

    //获取所有盒子的名称
    function get_eq_name_all(){
        $where['id >'] = 0;
        $this->c_db->from('equipment');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']] = $v['name'];
        }
        return $tmp;
    }

    /**
     * 根据设备号  获取设备名
     * @param array $id_arr
     * @return array
     */
    public function get_equipment_by_ids($id_arr){
        if(empty($id_arr)){
            return array();
        }
        $this->c_db->select('equipment_id,name');
        $this->c_db->from('equipment');
        $this->c_db->where_in('equipment_id', $id_arr);
        $rs = $this->c_db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']] = $v['name'];
        }
        return $tmp;
    }

}

?>
