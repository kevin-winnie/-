<?php


class Business_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();

    }
    //添加线索
    //data数组 show表名
    function insert($data,$show){
         $this->db->insert($show,$data);
         $insert_id = $this->db->insert_id();
         return $insert_id;
    }
    //获取数据线索
    function getclue($select="clue.*",$where = "",$sort = "clue.create_time",$order = "ASC",$limit="", $offset = "",$equipment_id,$search_clue_id){
        if($equipment_id != ""){
            $this->db->select("*");
            $this->db->from('clue_equipment');
            $this->db->where("equipment_id",$equipment_id);
            $row = $this->db->get()->row_array();
            $this->db->where("clue.clue_id",$row['clue_id']);
        }
        if($where){
            $this->db->where($where);
        }
        if($search_clue_id){
            $this->db->where('clue_id in ('.$search_clue_id.')');
        }

        $this->db->select($select);
        $this->db->from('clue');

        $this->db->order_by($sort.' '.$order);
        if($limit !=""){
            $this->db->limit($limit, $offset);
        }

        $total = $this->db->get()->result_array();

        return $total;
    }

    //获取所有后台人员
    function getAdmin(){
        $sql = "select id,name,alias from s_admin";
        $result = $this->db->query($sql)->result_array();

        return $result;
    }
    //获取bd负责人
    function getUser($admin_id){
        $sql = "select id,name,alias from s_admin where id=".$admin_id;
        $result = $this->db->query($sql)->row_array();

        return $result;
    }
    //更新DB进度
    function db_status($clue_id,$db_status){
        $data = array(
            'db_status' => $db_status,

        );

        $this->db->where('clue_id', $clue_id);
        $bool = $this->db->update('clue', $data);
        if($bool){
            return 'success';
        }else{
            return "error";
        }
    }
    //更改DB负责人
    function db_duty($clue_id,$admin_id){
        $data = array(
            'db_duty' => $admin_id,

        );

        $this->db->where('clue_id', $clue_id);
        $bool = $this->db->update('clue', $data);
        if($bool){
            return 'success';
        }else{
            return "error";
        }
    }
    //线索丢弃
    function discard($clue_id,$data){
        $this->db->set('pupr_status', $data);
        $this->db->where('clue_id', $clue_id);
        $bool = $this->db->update('clue');
        if($bool){

            return 'success';
        }else{

            return "error";
        }

    }
    //线索拾起
    function pick_up($clue_id,$data,$admin_id){
        $this->db->set('pupr_status', $data);
        $this->db->set('db_duty', $admin_id);
        $this->db->where('clue_id', $clue_id);
        $bool = $this->db->update('clue');
        if($bool){

            return 'success';
        }else{

            return "error";
        }

    }

    //提交装机状态
    function pupr_status($clue_id,$data){
        $this->db->set('pupr_status', $data);
        $this->db->set('submit_time', time());
        $this->db->set('db_status', 8);
        $this->db->where('clue_id', $clue_id);
        $bool = $this->db->update('clue');
        if($bool){

            return 'success';
        }else{

            return "error";
        }

    }
    /*
     * 线索备注添加
     * */
     function clueLog_add($data){
         $result  = $this->db->insert("clue_log",$data);
         return $result;
     }
    /*
     * 获取单条线索详细信息
     * clue_id 条件 show 表名 ids 字段名
     * */
    function getRow($clue_id,$show,$ids,$select="*"){



        $this->db->select($select);
        $this->db->from($show);
        $this->db->where($ids,$clue_id);
        $total = $this->db->get()->row_array();

        return $total;
    }
    /*
   * 修改表数据
     *  id 条件 data数组 show 表名 ids 字段名
   * */
    function update($id,$data,$show,$ids){
        $this->db->where($ids, $id);
        $bool = $this->db->update($show,$data);
        return $bool;
    }
    //根据clue_id查所属商户
    function getCommercial($clue_id){
        $this->db->select("commercial.name,clue.merchant_owned");
        $this->db->from("clue");
        $this->db->join("commercial","commercial.id = clue.merchant_owned");
        $this->db->where("clue_id",$clue_id);
        return $this->db->get()->row_array();
    }
    //获取地理位置
    public function position($id){
         $this->db->select("AREANAME");
         $this->db->where("AREAIDS",$id);
         $this->db->from("sys_regional");
         return $this->db->get()->row_array();
    }
   //合同维护
    public function contract($contract_remark,$clue_id,$contract_number,$contract_img,$contract_name){
        $data = [
            'contract_remark'=>$contract_remark,
            'contract_number'=>$contract_number
        ];

        $this->db->select("contract_img");
        $this->db->from("contract_img");
        $this->db->where('clue_id',$clue_id);
        $total = $this->db->get()->result_array();
        $data1 = [];
        foreach($total as $val){
           $data1[] = $val['contract_img'];
        }
        $img =[];
        $contract_img =explode(",",$contract_img);
        $contract_name =explode(",",$contract_name);
        //var_dump($contract_name);die;
        foreach($contract_img as $k=>$v){
            if($v != ""){
                if(!in_array($v,$data1)){
                    $str = [
                        "clue_id"=>$clue_id,
                        "contract_img"=>$v,
                        "contract_name"=>$contract_name[$k],
                    ];
                    $img[] = $str;
                }
            }

        }

        $this->db->trans_begin();

        $this->db->where("clue_id",$clue_id);
        $this->db->update("clue",$data);


        if($img){
            $this->db->insert_batch('contract_img', $img);
        }

        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }
    }
}