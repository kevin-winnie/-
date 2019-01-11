<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class workorder_model extends CI_Model
{


    function __construct()
    {
        parent::__construct();

        $this->load->library('phpredis');
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }
    //根据条件获取商户平台设备表数据
    public function getAdminEquipment($where,$cols='*'){
        $this->db->select($cols);
        $this->db->where($where);
        $this->db->from("equipment");
        $this->db->limit(1,0);
        $list = $this->db->get()->row_array();
        return $list;
    }
    //根据equipment_id修改平台设备所属商户和商户平台设备s所属商户 名称 启用状态
    public function save($equipment_id,$platform_id,$name,$clue_id){
        $this->db->trans_begin();
        $this->db->set('platform_id', $platform_id);
        $this->db->set('status', 1);
        $this->db->where('equipment_id', $equipment_id);
        $this->db->update('equipment');

        $data = array("clue_id"=>$clue_id,"equipment_id"=>$equipment_id,"equipment_name"=>$name);
        $this->db->select("clue_id");
        $this->db->where("equipment_id",$equipment_id);
        $this->db->from("clue_equipment");
        $list = $this->db->get()->row_array();
        if($list){
            $this->db->set('work_status', 2);
            $this->db->where('clue_id', $list['clue_id']);
            $this->db->update('clue');
        }
        $total = $this->get($data);
        if($total == true){
            $this->db->set('equipment_name', $data['equipment_name']);
            $this->db->set('clue_id', $data['clue_id']);
            $this->db->where('equipment_id', $equipment_id);
            $this->db->update('clue_equipment');
        }else{
            $this->db->insert('clue_equipment',$data);
        }
        $this->db->set('work_status', 3);
        $this->db->where('clue_id', $clue_id);
        $this->db->update('clue');
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }
    }
    //判断
    public function get($data){
        $equipment_id = $data['equipment_id'];
        $this->db->select("*");
        $this->db->from("clue_equipment");
        $this->db->where('equipment_id',$equipment_id);
        $total = $this->db->get()->row_array();
        if($total){
            return true;
        }else{
            return false;
        }

    }
    //更改work_status装机状态已完成
    public function  work_status($clue_id){

        $this->db->set('work_status', 4);
        $this->db->set('db_status', 5);
        $this->db->where('clue_id', $clue_id);
        $res = $this->db->update('clue');
        return $res;
    }
    //根据equipment_id替换设备id和工作状态
    public function replace_equipment($platform_id,$v,$clue_id){
        $this->db->trans_begin();
        $this->db->set('platform_id',"");
        $this->db->set('status',0);
        $this->db->where('equipment_id', $v['equipment_id']);
        $this->db->update('equipment');

        $this->db->set('platform_id',$platform_id);
        $this->db->set('status',1);
        $this->db->where('equipment_id', $v['replace_equipment_id']);
        $this->db->update('equipment');

        $this->db->set('equipment_id', $v['replace_equipment_id']);
        $this->db->set('equipment_name', $v['name']);
        $this->db->set('clue_id', $clue_id);
        $this->db->where('equipment_id', $v['equipment_id']);
        $this->db->update('clue_equipment');


        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }
    }
    //排期
    function schedule($clue_id,$schedule_time){
        $this->db->set('schedule_time', strtotime($schedule_time));
        $this->db->set('work_status', 2);
        $this->db->where('clue_id', $clue_id);
        $res =  $this->db->update('clue');
        return $res;
    }
    function equipment_status($equipment_id){
        $this->db->set('status',0);
        $this->db->where('equipment_id',$equipment_id);
        $res =  $this->db->update('equipment');
        return $res;
    }
    /*
 * 获取某条线索的所有设备id
 * clue_id 条件 show 表名 ids 字段名
 * */
    function getEquipment($clue_id,$show,$ids){



        $this->db->select("*");
        $this->db->from($show);
        $this->db->where($ids,$clue_id);
        $total = $this->db->get()->result_array();

        return $total;
    }
    /*
    * 获取单条线索下所有设备id
    * clue_id 条件 show 表名 ids 字段名
    * */
    function getRow($clue_id,$show,$ids){



        $this->db->select("*");
        $this->db->from($show);
        $this->db->where($ids,$clue_id);
        $total = $this->db->get()->result_array();

        return $total;
    }
}

?>
