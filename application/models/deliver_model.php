<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/3/24
 */

class Deliver_model extends MY_Model
{
    function __construct(){
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    //获取table
    function get_list($where,$limit,$offset){
        $this->c_db->dbprefix = '';
        $this->c_db->select("d.id,d.deliver_no,e.name,a.user_name,d.time,d.begin_time,d.end_time,d.result,a.mobile");
        $this->c_db->from('cb_deliver d');
        $this->c_db->join('cb_user a',"d.originator=a.id");
        $this->c_db->join('cb_equipment e','e.equipment_id = d.equipment_id');
        $this->c_db->where($where);
        $this->c_db->limit($limit,$offset);
        $this->c_db->order_by('id','desc');
        $list = $this->c_db->get()->result_array();

        $this->c_db->select("d.id,d.deliver_no,e.name,a.user_name,d.time,d.begin_time,d.end_time,d.result");
        $this->c_db->from('cb_deliver d');
        $this->c_db->join('cb_user a',"d.originator=a.id");
        $this->c_db->join('cb_equipment e','e.equipment_id = d.equipment_id');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }

    //获取详情
    function get_info_by_id($id){
        $this->c_db->dbprefix = '';
        $this->c_db->select("d.id,d.deliver_no,e.name,a.user_name,d.time,d.begin_time,d.end_time,d.result");
        $this->c_db->from('cb_deliver d');
        $this->c_db->join('cb_user a',"d.originator=a.id");
        $this->c_db->join('cb_equipment e','e.equipment_id = d.equipment_id');
        $this->c_db->where(array('d.id'=>$id,'d.platform_id'=>$this->platform_id));
        $deliver = $this->c_db->get()->row_array();
        return $deliver;
    }

    //获取详情的商品上下架情况
    function get_deliver_products($id){
        $deliver_products = $this->c_db->from('cb_deliver_product')->where(array('deliver_id'=>$id))->get()->result_array();
        return $deliver_products;
    }

//    //获取出库单详情
//    function get_equipment_diliver_list($where,$limit,$offset){
//        $this->c_db->dbprefix = '';
//        $this->c_db->select("a.*,b.name admin_name");
//        $this->c_db->from('cb_equipment a');
//        $this->c_db->join('s_admin b',"a.admin_id=b.id");
//        $this->c_db->join('cb_shipping_config c','c.equipment_id = a.id');
//        $this->c_db->where($where);
//        $this->c_db->limit($limit,$offset);
//        $list = $this->c_db->get()->result_array();
//
//        $this->c_db->select("d.id,d.deliver_no,e.name,a.alias,d.time,d.begin_time,d.end_time,d.result");
//        $this->c_db->from('cb_deliver d');
//        $this->c_db->join('s_admin a',"d.originator=a.id");
//        $this->c_db->join('cb_equipment e','e.id = d.equipment_id');
//        $this->c_db->where($where);
//        $total = $this->c_db->get()->num_rows();
//        $result = array(
//            'total' => $total,
//            'rows' => $list
//        );
//        return $result;
//    }

    //获取单台设备的预存量配置信息
    function get_deliver_shipping_list($equipment_id){
        $rs = $this->c_db->select("a.id,a.equipment_id,a.product_id,b.product_name,a.pre_qty,b.price,b.preservation_time")->from('shipping_config a')->join('product b',"a.product_id = b.id")->where(array(
            'a.equipment_id'=>$equipment_id,
            'a.platform_id'=>$this->platform_id
        ))->get()->result_array();
        return $rs;
    }
    
    //获取预存量配置记录
    function get_shipping_config($equipment_id){
        $this->c_db->from('cb_shipping_config');
        $this->c_db->where(array('equipment_id'=>$equipment_id,'platform_id'=>$this->platform_id));
        $result = $this->c_db->get()->result_array();
        
        return $result;
    }

    //编辑预存量配置记录
    function edit_shipping_config($data,$where){
        if(!isset($where['platform_id'])){
            $where['platform_id'] = $this->platform_id;
        }
        return $this->c_db->update('shipping_config',$data,$where);
    }

    //删除预存量配置记录
    function del_shipping_config($where){
        if(!isset($where['platform_id'])){
            $where['platform_id'] = $this->platform_id;
        }
        return $this->c_db->delete('shipping_config',$where);
    }

    //插入预存量数据
    function add_shipping_config($data){
        if(!isset($data['platform_id'])){
            $data['platform_id'] = $this->platform_id;
        }
        return $this->c_db->insert('shipping_config',$data);
    }

    //单个查找预存表
    function is_added_pre_product($where,$field="*"){
        if(!isset($where['platform_id'])){
            $where['platform_id'] = $this->platform_id;
        }
        return $this->c_db->select($field)->from('shipping_config')->where($where)->get()->row_array();
    }

    /*
     * @desc 获取时间段内 上下架货品数量
     * @param $start_time 开始时间
     * @param $end_time 结束时间
     * @param $type 类型 1.上架 2.下架
     * */
    function get_product_num($start_time, $end_time, $type, $platform_id=0){
        if($platform_id){
            $where['d.platform_id'] = $platform_id;
        }
        $where['d.time >='] = $start_time;
        $where['d.time <='] = $end_time;
        $where['dp.type']   = $type;
        $this->c_db->select('SUM(dp.qty) as qty');
        $this->c_db->from('deliver d');
        $this->c_db->join('deliver_product dp', 'dp.deliver_id=d.id');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();
        return intval($rs['qty']);
    }
}