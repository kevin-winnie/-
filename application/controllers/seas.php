<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class seas extends MY_Controller
{
    public $workgroup = 'seas';
    public $redis;
    public static $db_status  = array(
        "0" => array( 0 => "",            1 => "--",),
        "1" => array( 0 => "btn-info",    1 =>"联系中"),
        "2" => array( 0 => "btn-info",    1 =>"意向确立") ,
        "3" => array( 0 => "btn-info",    1 =>"协议流程"),
        "4" => array( 0 => "btn-info",    1 =>"等待装机"),
        "5" => array( 0 => "btn-success", 1 => "成功"),
        "6" => array( 0 => "btn-danger",  1 =>"失败") ,
        "7" => array( 0 => "btn-warning", 1 =>"暂停") ,
        "8" => array(0 => "btn-success", 1 => "已提交装机"),
        "9" => array(0 => "btn-success", 1 => "打款成功")
    );

    function __construct() {
        parent::__construct();
        $this->load->model("commercial_model");
        $this->load->model("business_model");
        $this->load->model("admin_model");




    }
    function clue_index(){
        //获取数据线索
        //$this->_pagedata["clue_list"]  = $this->business_model->getclue();
        //获取所有后台人员
        $this->_pagedata["admin_list"] = $this->business_model->getAdmin();
        $this->_pagedata["db_status"]  = self::$db_status;
        $this->page("seas/clue_index.html");
    }

    function table(){

        $limit              = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset             = $this->input->get('offset')?$this->input->get('offset'):0;
        $search_name        = $this->input->get('search_name');
        $search_province    = $this->input->get('search_province');
        $search_city        = $this->input->get('search_city');
        $search_area        = $this->input->get('search_area');
        $search_address     = $this->input->get('search_address');
        $search_contacts    = $this->input->get('search_contacts');
        $search_phone       = $this->input->get('search_phone');
        $search_re_contacts = $this->input->get('search_re_contacts');
        $search_re_phone    = $this->input->get('search_re_phone');
        $search_db_duty     = $this->input->get('search_db_duty');
        $search_db_status   = $this->input->get('search_db_status');
        $search_source      = $this->input->get('search_source');
        $start_time         = $this->input->get('search_start_time');
        $end_time           = $this->input->get('search_end_time');
        $sort               = 'clue.clue_id';
        $order              = 'desc';

        $where = array("clue.pupr_status" => "0");
        if($search_name){
           $where['clue.name like ']  = "%".$search_name."%";
        }
        if($search_address ){
            $where['clue.address like ']  = "%".$search_address."%";
        }
        if($search_contacts ){
            $where['clue.contacts like ']  = "%".$search_contacts."%";
        }
        if($search_phone ){
            $where['clue.phone like ']  = "%".$search_phone."%";
        }
        if($search_re_contacts ){
            $where['clue.re_contacts like ']  = "%".$search_re_contacts."%";
        }
        if($search_re_phone ){
            $where['clue.re_phone like ']  = "%".$search_re_phone."%";
        }
        if($search_db_duty ){
            $where['clue.db_duty ']  = $search_db_duty;
        }
        if($search_db_status ){
            $where['clue.db_status ']  = $search_db_status;
        }
        if($search_source ){
            $where['clue.source like ']  = "%".$search_source."%";
        }
        if($start_time ){
            $where['clue.create_time  >=']  =  strtotime($start_time);
        }
        if($end_time ){
            $where['clue.create_time  <=']  =  strtotime($end_time);
        }
        if($search_province ){
            $where['clue.province ']  = $search_province;
        }
        if($search_city ){
            $where['clue.city ']  = $search_city;
        }
        if($search_area ){
            $where['clue.area ']  = $search_area;
        }
        $rows = $this->business_model->getclue("*",$where,$sort,$order,$limit,$offset );
        $this->db->where($where);
        $this->db->select("count(*) as c");
        $this->db->from('clue');
        $total = $this->db->get()->row_array();
        $admin_list = $this->business_model->getAdmin();

        foreach ($rows as $k=>$v){

            $rows[$k]['gl'] = "<a href='javascript:void(0)'  class='btn btn-link  col-sm-offset-2 col-sm-10 btn-gl' clue_id='".$v['clue_id']."' >管理</a>";
            //db进度状态转换按钮
            foreach (self::$db_status as $key => $value){
                if($v['db_status'] == $key){
                    $rows[$k]['db_status'] = "<button type='button' value='".$key ."' id='db_status' class='btn  col-sm-offset-2 col-sm-10  ".$value[0]."'  >".$value[1]."</button>";

                }
            }
             //转换添加时间
            if($v['create_time']){

                $rows[$k]['create_time']     = date("Y-m-d H:i:s",$v['create_time']);
            }

           //转换添加方式
            if($v['clue_source'] = 1){
                $rows[$k]['clue_source'] = "后台录入";
            }else{
                $rows[$k]['clue_source'] = "用户提交";
            }
            //根据s_admin.id和p_clue.db_duty相等取admin.name
            foreach ($admin_list as $val){
                if($v['db_duty'] == $val['id']){
                    $rows[$k]['admin_name'] = "<button type=\"button\" class=\"btn btn-primary col-sm-offset-2 col-sm-10\" id='".$v['clue_id']."' >".$val['alias']."</button>";
                }
            }

        }

        $result = array("rows"=>$rows,"total"=>$total['c']);
        echo json_encode($result);
    }
    //拾起线索
    function pickup(){
        $clue_id           = $this->input->post('clue_id');
        $clue_id          = explode(",",$clue_id);
        //修改0为丢弃状态

        $admin_id   = $this->session->userdata('sess_admin_data')["adminid"];
        $adminalias = $this->session->userdata('sess_admin_data')["adminalias"];
        $result = array();
        foreach ($clue_id as $v) {
            $data = 1;
            $status= $this->business_model->pick_up($v, $data, $admin_id);
            if ($status == "success") {
                $data = array(
                    "clue_id" => $v,
                    "admin_name" => $adminalias,
                    "create_time" => time(),
                    "log_text" => "公海线索认领并更改db负责人为自己"
                );
                $this->business_model->clueLog_add($data);
                $res['status'] = "success";
                $res['clue_id'] = $v;
            }else{
                $res['status'] = "error";
                $res['clue_id'] = $v;


            }
            $result[]=$res;

        }
        echo json_encode($result);
    }










}