<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class business extends MY_Controller
{
    public $workgroup = 'business';
    public $redis;
    public $img_http = 'http://fdaycdn.fruitday.com/';
    //db状态
    public static $db_status = array(
        "0" => array(0 => "", 1 => "--",),
        "1" => array(0 => "btn-info", 1 => "联系中"),
        "2" => array(0 => "btn-info", 1 => "意向确立"),
        "3" => array(0 => "btn-info", 1 => "协议流程"),
        "4" => array(0 => "btn-info", 1 => "等待装机"),
        "5" => array(0 => "btn-success", 1 =>"装机成功"),
        "6" => array(0 => "btn-danger", 1 => "失败"),
        "7" => array(0 => "btn-warning", 1 => "暂停"),
        "8" => array(0 => "btn-success", 1 => "已提交装机"),
        "9" => array(0 => "btn-success", 1 => "打款成功")
    );

    public  static $fare = array("1"=>"先票后款","2"=>"先款后票");
    public  static $payment = array("1"=>"先付","2"=>"后付");
    public  static $status = array("1"=>"有","2"=>"无");
    //企业场景
    public static $scene = array(
        "1" => "企业",
        "2" => "写字楼",
        "3" => "社区",
        "4" => "联合办公",
        //"5" => "众创空间",
        "6" => "商场",
        "7" => "学校",
        "8" => "健身房",
        "9" => "体育馆",
        "10" => "网吧",
        "11" => "其他",
        "12" => "园区",
        "13" => "通勤",
        "14" => "政府单位",
        "15" => "医院",
        "16" => "酒店",
        "17" => "4S店",
        "18" => "餐厅/食堂",
    );
    //企业性质
    public static $nature = array("1" => "民营", "2" => "国企", "3" => "个体经营", "4" => "外企");
    public static $industry = array("1" => "IT软件", "2" => "电子商务", "3" => "互联网", "4" => "O2O", "5" => "教育培训", "6" => "智能硬件", "7" => "金融", "8" => "旅游", "9" => "文化娱乐", "10" => "广告营销", "11" => "社交网络", "12" => "分类信息", "13" => "企业无服务", "14" => "生活服务", "15" => "媒体", "16" => "健康医疗", "17" => "交通物流", "18" => "其他行业");


    function __construct()
    {
        parent::__construct();
        $this->load->model("commercial_model");
        $this->load->model("business_model");
        $this->load->model("admin_model");


    }

    function clue_index()
    {
        $admin_flag = $this->session->userdata('sess_admin_data')["adminflag"];
        if (in_array(94, $admin_flag)) {
            $this->_pagedata["admin_flag"] = 94;
        } else {
            $this->_pagedata["admin_flag"] = 0;
        }
        $this->_pagedata["admin_id"] = $this->session->userdata('sess_admin_data')["adminid"];
        //获取数据线索
        $this->_pagedata["clue_list"] = $this->business_model->getclue();
        //获取所有后台人员
        $this->_pagedata["admin_list"] = $this->business_model->getAdmin();
        $this->_pagedata["db_status"] = self::$db_status;
        $this->page("business/clue_index.html");
    }

    //线索添加
    function clue_add()
    {

        if ($this->input->post("submit")) {
            $post = $this->input->post();
            $data = array(
                'name' => $post['name'],
                "province" => $post['province'],
                "city" => $post['city'],
                "area" => $post['area'],
                'address' => $post['address'],
                'contacts' => $post['contacts'],
                'phone' => $post['phone'],
                'scene' => $post['scene'],
                'merchant_owned' => $post['merchant_owned'],
                'source' => $post['source'],
                'db_duty' => $post['db_duty'],
                'db_status' => $post['db_status'],
                'create_time' => time(),
                'grade' =>isset($post['grade']) ? $post['grade'] : "",
                'scene_additional' =>isset($post['scene_additional']) ? $post['scene_additional'] : ""

            );
            //判断add等于0添加到公海
            if($post['add']==0){
                $data['pupr_status']=0;
            }
            $insert_id = $this->business_model->insert($data, "clue");
            $adminalias = $this->session->userdata('sess_admin_data')["adminalias"];
            if ($insert_id) {
                $data = array(
                    "clue_id" => $insert_id,
                    "admin_name" => $adminalias,
                    "create_time" => time(),
                    "log_text" => "添加线索"
                );
                $this->business_model->clueLog_add($data);
                $this->_pagedata["tips"] = "新增成功";
                // $this->business_model->setCommInfo($rs);
            } else {
                $this->_pagedata["tips"] = "新增失败";
            }
        }
        //获取当前登录平台用户
        $this->_pagedata['admin_name'] = $this->session->userdata('sess_admin_data')["adminalias"];
        $this->_pagedata['admin_id'] = $this->session->userdata('sess_admin_data')["adminid"];

        //获取所有商户户
        $this->_pagedata['commercial_list'] = $this->commercial_model->get_all_platforms();
        $this->_pagedata["scene"] = self::$scene;
        $this->page("business/clue_add.html");


    }

    function table()
    {

        $limit = $this->input->get('limit') ? $this->input->get('limit') : 50;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $search_name = $this->input->get('search_name');
        $search_province = $this->input->get('search_province');
        $search_city = $this->input->get('search_city');
        $search_area = $this->input->get('search_area');
        $search_address = $this->input->get('search_address');
        $search_contacts = $this->input->get('search_contacts');
        $search_phone = $this->input->get('search_phone');
        $search_re_contacts = $this->input->get('search_re_contacts');
        $search_re_phone = $this->input->get('search_re_phone');
        $search_db_duty = $this->input->get('search_db_duty');
        $search_db_status = $this->input->get('search_db_status');
        $search_source = $this->input->get('search_source');
        $start_time = $this->input->get('search_start_time');
        $end_time = $this->input->get('search_end_time');
        $sort =  'clue.create_time';
        $order = 'desc';

        $where = array("clue.pupr_status  !=" => "0");
        $admin_flag = $this->session->userdata('sess_admin_data')["adminflag"];

        $admin_id = $this->session->userdata('sess_admin_data')["adminid"];

        if ($admin_id != 1 && !in_array(95, $admin_flag)) {
            $where['clue.db_duty '] = $admin_id;

        }
        if ($search_name) {
            $where['clue.name like '] = "%" . $search_name . "%";
        }
        if ($search_address) {
            $where['clue.address like '] = "%" . $search_address . "%";
        }
        if ($search_contacts) {
            $where['clue.contacts like'] = "%" . $search_contacts . "%";
        }

        if ($search_phone) {
            $where['clue.phone like'] = "%" . $search_phone . "%";
        }
        if ($search_re_contacts) {
            $where['clue.re_contacts like'] = "%" . $search_re_contacts . "%";
        }
        if ($search_re_phone) {
            $where['clue.re_phone like'] = "%" . $search_re_phone . "%";
        }
        if ($search_db_duty) {
            if($admin_id != 1 && !in_array(95, $admin_flag)){
                $where['clue.db_duty '] = $admin_id;
            }else{
                $where['clue.db_duty '] = $search_db_duty;
            }

        }
        if ($search_db_status) {
            $where['clue.db_status '] = $search_db_status;
        }
        if ($search_source) {
            $where['clue.source like '] = "%" . $search_source . "%";
        }
        if ($start_time) {
            $where['clue.create_time  >='] = strtotime($start_time);
        }
        if ($end_time) {
            $where['clue.create_time  <='] = strtotime($end_time);
        }
        if ($search_province) {
            $where['clue.province '] = $search_province;
        }
        if ($search_city) {
            $where['clue.city '] = $search_city;
        }
        if ($search_area) {
            $where['clue.area '] = $search_area;
        }
        $rows = $this->business_model->getclue("*", $where, $sort, $order, $limit, $offset);
        $this->db->where($where);
        $this->db->select("count(*) as c");
        $this->db->from('clue');
        $total = $this->db->get()->row_array();
        $admin_list = $this->business_model->getAdmin();
        foreach ($rows as $k => $v) {

            $rows[$k]['gl'] = "<a href='javascript:void(0)'  class='btn btn-link  col-sm-offset-2 col-sm-10 btn-gl' clue_id='" . $v['clue_id'] . "' >管理</a>";
            //db进度状态转换按钮
            foreach (self::$db_status as $key => $value) {
                if ($v['db_status'] == $key) {
                    $rows[$k]['db_status'] = "<button type='button' value='" . $key . "' id='db_status" . $v['clue_id'] . "' class='btn   col-sm-offset-2 col-sm-10  " . $value[0] . "'  >" . $value[1] . "</button>";

                }
            }
            //转换添加时间
            if ($v['create_time']) {
                $rows[$k]['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            }
            if ($v['install_time']) {

                //转换安装时间
                $rows[$k]['install_time'] = date("Y-m-d H:i:s", $v['install_time']);
            }
            if ($v['schedule_time']) {

                //转换安装时间
                $rows[$k]['schedule_time'] = date("Y-m-d H:i:s", $v['schedule_time']);
            }else{
                $rows[$k]['schedule_time'] = "待排期";
            }
            //$rows[$k]['id']              = '<input type="radio" name="radiochecked"   value="'.$v['clue_id'].'"> '.$v['clue_id'];
            //转换添加方式
            if ($v['clue_source'] = 1) {
                $rows[$k]['clue_source'] = "后台录入";
            } else {
                $rows[$k]['clue_source'] = "用户提交";
            }
            //根据s_admin.id和p_clue.db_duty相等取admin.name
            foreach ($admin_list as $val) {

                if ($v['db_duty'] == $val['id']) {
                    $rows[$k]['admin_name'] = "<button type=\"button\" class=\"btn btn-primary col-sm-offset-2 col-sm-10\" id='" . $v['clue_id'] . "' >" . $val['alias'] . "</button>";
                }
            }

        }

        $result = array("rows" => $rows, "total" => $total['c']);
        echo json_encode($result);
    }

    //更新db进度
    function db_status()
    {
        $clue_id = $this->input->post('clue_id');
        $db_status = $this->input->post('db_status');
        $clue_id = explode(",", $clue_id);
        $adminalias = $this->session->userdata('sess_admin_data')["adminalias"];
        $db_sta = self::$db_status;
        $result = array();
        foreach ($clue_id as $v) {
            $status = $this->business_model->db_status($v, $db_status);
            if ($status == "success") {
                $data = array(
                    "clue_id" => $v,
                    "admin_name" => $adminalias,
                    "create_time" => time(),
                    "log_text" => "更改BD进度为" . $db_sta[$db_status][1]
                );
                $this->business_model->clueLog_add($data);
                foreach (self::$db_status as $key => $value) {
                    if ($key == $db_status) {
                        $res['total'] = "<button type='button' value='" . $key . "' id='db_status' class='btn col-sm-offset-2 col-sm-10  " . $value[0] . "'  >" . $value[1] . "</button>";

                    }
                }
                $res['status'] = "success";
                $res['clue_id'] = $v;
            } else {
                $res['status'] = "error";
                $res['clue_id'] = $v;
                $res['msg'] = "更改失败";

            }
            $result[] = $res;
        }
        echo json_encode($result);
    }

    //更改db负责人
    function db_duty()
    {
        $admin_flag = $this->session->userdata('sess_admin_data')["adminflag"];
        $adminid = $this->session->userdata('sess_admin_data')["adminid"];

        $result = array();
        if (in_array("94", $admin_flag) || $adminid == 1) {
            $clue_id = $this->input->post('clue_id');
            $admin_id = $this->input->post('admin_id');
            $clue_id = explode(",", $clue_id);
            $admin_row = $this->business_model->getUser($admin_id);
            $adminalias = $this->session->userdata('sess_admin_data')["adminalias"];
            foreach ($clue_id as $v) {
                $status = $this->business_model->db_duty($v, $admin_id);
                if ($status == "success") {
                    //记录日志
                    $data = array(
                        "clue_id" => $v,
                        "admin_name" => $adminalias,
                        "create_time" => time(),
                        "log_text" => "更改BD负责人为" . $admin_row['alias']
                    );
                    $this->business_model->clueLog_add($data);
                    $res['status'] = "success";
                    $res['clue_id'] = $v;
                    $res['total'] = "<button type='button' class='btn col-sm-offset-2 col-sm-10  btn-primary'  >" . $admin_row['alias'] . "</button>";

                } else {
                    $res['status'] = "error";
                    $res['clue_id'] = $v;
                    $res['msg'] = "更改失败";

                }
                $result[] = $res;

            }
        } else {
            $res['status'] = "error";
            $res['msg'] = "没有该权限";
            $result[] = $res;
        }


        echo json_encode($result);
    }

    //线索丢弃
    function discard()
    {
        $clue_id = $this->input->post('clue_id');

        $clue_id = explode(",", $clue_id);
        $adminalias = $this->session->userdata('sess_admin_data')["adminalias"];
        //修改0为丢弃状态

        $result = array();
        foreach ($clue_id as $v) {
            $status = $this->business_model->discard($v, 0);
            if ($status == "success") {
                $data = array(
                    "clue_id" => $v,
                    "admin_name" => $adminalias,
                    "create_time" => time(),
                    "log_text" => "线索丢弃到公海"
                );
                $this->business_model->clueLog_add($data);
                $res['status'] = "success";
                $res['clue_id'] = $v;

            } else {
                $res['status'] = "error";
                $res['clue_id'] = $v;


            }
            $result[] = $res;
        }
        echo json_encode($result);
    }

    //线索备注日志
    function clueLog()
    {
        $clue_id = $this->input->get("clue_id");
        $this->_pagedata['clue_id'] = $clue_id;
        $this->page("business/clue_log.html");
    }

    //线索备注添加
    function clueLog_add()
    {
        $clue_id = $this->input->post("clue_id");
        $log_text = $this->input->post("log_text");
        $data = array(
            "clue_id" => $clue_id,
            "log_text" => $log_text,
            "create_time" => time(),
            "admin_name" => $this->session->userdata('sess_admin_data')["adminalias"],
        );
        $result = $this->business_model->clueLog_add($data);
        redirect('/business/clueLog?clue_id=' . $clue_id);
    }

    function log_table()
    {
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $clue_id = $this->input->get('clue_id');
        $this->db->select("*");
        $this->db->from('clue_log');
        $this->db->where("clue_id", $clue_id);
        $this->db->order_by('log_id   desc');
        $this->db->limit($limit, $offset);
        $rows = $this->db->get()->result_array();
        $this->db->select(" count(*) as c");
        $this->db->from('clue_log');
        $this->db->where("clue_id", $clue_id);
        $total = $this->db->get()->row_array();

        foreach ($rows as $k => $v) {
            $rows[$k]['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
        }
        $result = array("rows" => $rows, "total" => $total['c']);
        echo json_encode($result);
    }

    //提交装机更改pupr_status状态
    function pupr_status()
    {

        $clue_id = $this->input->post("clue_id");
        $clue_id = explode(",", $clue_id);
        $adminalias = $this->session->userdata('sess_admin_data')["adminalias"];
        $result = array();

        foreach ($clue_id as $v) {

            $row = $this->business_model->getRow($v,"clue","clue_id","name,province,city,area,address,equipment_name,equipment_phone,socket_status,ground_status,ladder_status,equipment_number,first_deliver,equipment_address,number,contacts,phone,company,merchant_owned");

            $flog = true;
            foreach($row as $val){
                if($val == "" or $val == "-1"){

                    $flog = false;
                    break;
                }
            }
            if($flog == true){
                //修改2为装机提交状态
                $data = 2;
                $status = $this->business_model->pupr_status($v, $data);
                if ($status == "success") {
                    $data = array(
                        "clue_id" => $v,
                        "admin_name" => $adminalias,
                        "create_time" => time(),
                        "log_text" => "提交装机工单"
                    );
                    $this->business_model->clueLog_add($data);
                    $res['status'] = "success";
                    $res['msg'] = "提交成功";
                    $res['clue_id'] = $v;
                } else {
                    $res['status'] = "error";
                    $res['msg'] = "提交失败";
                    $res['clue_id'] = $v;
                }
                $result[] = $res;
            } else{
                $res['status'] = "error";
                $res['msg'] = "提交装机前，请维护点位下的装机信息( 页面标 * 的为必填项！)";
                $res['clue_id'] = $v;
                $result[] = $res;
            }

        }

        echo json_encode($result);
    }

    /*
     * 根据clue_id获取详细线索信息
     * */
    function maintain_add()
    {
        $clue_id = $this->input->get("clue_id");
        $edit_status = $this->input->get("edit_status");
        $admin_list = $this->business_model->getAdmin();
        //企业场景
        $this->_pagedata['scene'] = self::$scene;
        //企业性质
        $this->_pagedata['nature'] = self::$nature;
        //所属行业
        $this->_pagedata['industry'] = self::$industry;
        $this->_pagedata['admin_list'] = $admin_list;

//        获取地理位置
        $clue_list = $this->business_model->getRow($clue_id, "clue", "clue_id");
        $province = $this->business_model->position($clue_list['province']);
        $city = $this->business_model->position($clue_list['city']);
        $area = $this->business_model->position($clue_list['area']);
        $clue_list['province_name'] = $province['AREANAME'];
        $clue_list['city_name'] = $city['AREANAME'];
        $clue_list['area_name'] = $area['AREANAME'];
        //获取商户
        $this->_pagedata['commercial_list'] = $this->commercial_model->get_all_platforms();
        //获取bd负责人
        $admin_name = $this->business_model->getUser($clue_list['db_duty']);
        $clue_list ['admin_name'] = $admin_name['alias'];
        if ($clue_list['install_time']) {

            $clue_list['install_time'] = date("Y-m-d H:i:s", $clue_list['install_time']);
        }

        $clue_list['days'] = explode(",", $clue_list['days']);

        $this->_pagedata['clue_list'] = $clue_list;
        //合同
        $contract_img = $this->db->from("contract_img")->where("clue_id",$clue_id)->where("is_del",0)->get()->result_array();

        //判断是否显示保存按钮
        $this->_pagedata['edit_status'] = $edit_status;
        $this->_pagedata['img_http'] = $this->img_http;
        $this->_pagedata['contract_img'] = $contract_img;

        $this->page("business/maintain_add.html");
        //  $this->page("business/add.html");
    }
    //获取contract_img表信息
    function contract_img(){
        $clue_id = $this->input->get("clue_id");
        $contract_img = $this->db->from("contract_img")->where("clue_id",$clue_id)->where("is_del",0)->get()->result_array();
        if($contract_img){
            echo json_encode($contract_img);
        }else{
            echo "";
        }
    }
    //获取clue_img表信息
    function clue_img(){
        $clue_id = $this->input->get("clue_id");
        $clue_img = $this->db->from("clue_img")->where("clue_id",$clue_id)->get()->result_array();
        if($clue_img){
           echo json_encode($clue_img);
        }else{
            echo "";
        }
    }
    //w维护点位信息
    function save()
    {

        $data['clue_id']  = $this->input->post("clue_id");
        $data['name']  = $this->input->post("name");
        $data['days']  = $this->input->post("days");
        $data['province']  = $this->input->post("province");
        $data['phone']  = $this->input->post("phone");
        $data['city']  = $this->input->post("city");
        $data['area']  = $this->input->post("area");
        $data['contacts']  = $this->input->post("contacts");
        $data['contacts_title']  = $this->input->post("contacts_title");
        $data['address']  = $this->input->post("address");
        $data['scene']  = $this->input->post("scene");
        $data['scene_additional']  = $this->input->post("scene_additional");
        $data['merchant_owned']  = $this->input->post("merchant_owned");
        $data['source']  = $this->input->post("source");
        $data['nature']  = $this->input->post("nature");
        $data['industry']  = $this->input->post("industry");
        $data['start_hours']  = $this->input->post("start_hours");
        $data['end_hours']  = $this->input->post("end_hours");
        $data['goods']  = $this->input->post("goods");
        $data['overtime']  = $this->input->post("overtime");
        $data['welfare']  = $this->input->post("welfare");
        $data['welfare_describe']  = $this->input->post("welfare_describe");
        $data['remark']  = $this->input->post("remark");
        $data['company']  = $this->input->post("company");
        $data['facilitate_status']  = $this->input->post("facilitate_status");
        $data['micro_status']  = $this->input->post("micro_status");
        $data['sell_status']  = $this->input->post("sell_status");
        $sell_img  = $this->input->post("sell_img");
        if($sell_img != ""){
            $data['sell_img']  = $sell_img;

        }
        $data['protocol_start_time']  = $this->input->post("protocol_start_time");
        $data['protocol_end_time']  = $this->input->post("protocol_end_time");
        $data['merchant_name']  = $this->input->post("merchant_name");
        $data['power_status']  = $this->input->post("power_status");
        $data['power_money']  = $this->input->post("power_money");
        $data['power_payment_cycle']  = $this->input->post("power_payment_cycle");
        $data['power_payment']  = $this->input->post("power_payment");
        $data['synthesize_status']  = $this->input->post("synthesize_status");
        $data['synthesize_money']  = $this->input->post("synthesize_money");
        $data['synthesize_payment_cycle']  = $this->input->post("synthesize_payment_cycle");
        $data['synthesize_payment']  = $this->input->post("synthesize_payment");
       // $data['synthesize_payment_time']  = $this->input->post("synthesize_payment_time");
        $data['sale_status']  = $this->input->post("sale_status");
        $data['sale_payment_cycle']  = $this->input->post("sale_payment_cycle");
        $data['sale_money']  = $this->input->post("sale_money");
        $data['sale_payment']  = $this->input->post("sale_payment");
       // $data['sale_payment_time']  = $this->input->post("sale_payment_time");
        $data['invoice_status']  = $this->input->post("invoice_status");
       // $data['power_payment_time']  = $this->input->post("power_payment_time");
        $data['activity_remark']  = $this->input->post("activity_remark");
        $data['special_remark']  = $this->input->post("special_remark");
        $data['number']  = $this->input->post("number");
        $data['boynumber']  = $this->input->post("boynumber");
        $data['girlnumber']  = $this->input->post("girlnumber");
        $data['first_deliver']  = $this->input->post("first_deliver");
        $data['equipment_number']  = $this->input->post("equipment_number");
        $data['equipment_address']  = $this->input->post("equipment_address");
        $data['equipment_phone']  = $this->input->post("equipment_phone");
        $data['equipment_name']  = $this->input->post("equipment_name");
        $data['camera_status']  = $this->input->post("camera_status");
        $data['install_time']  = $this->input->post("install_time");
        $data['socket_status']  = $this->input->post("socket_status");
        $data['socket_remark']  = $this->input->post("socket_remark");
        $data['ground_status']  = $this->input->post("ground_status");
        $data['ground_remark']  = $this->input->post("ground_remark");
        $data['ladder_remark']  = $this->input->post("ladder_remark");
        $data['ladder_status']  = $this->input->post("ladder_status");
        $data['carry_status']  = $this->input->post("carry_status");
        $data['carry_remark']  = $this->input->post("carry_remark");
        $data['remark_two']  = $this->input->post("remark_two");

        $data['banak']  = $this->input->post("banak");
        $data['bank_number']  = $this->input->post("bank_number");
        $data['paragraph']  = $this->input->post("paragraph");
        $data['paragraph_fare']  = $this->input->post("paragraph_fare");
        $data['attention']  = $this->input->post("attention");
        $data['field_status']  = $this->input->post("field_status");
        $data['field_money']  = $this->input->post("field_money");
        $data['field_payment_cycle']  = $this->input->post("field_payment_cycle");
        $data['field_payment']  = $this->input->post("field_payment");
        $data['field_fare']  = $this->input->post("field_fare");
        $data['synthesize_fare']  = $this->input->post("synthesize_fare");
        $data['power_fare']  = $this->input->post("power_fare");
        $data['sale_fare']  = $this->input->post("sale_fare");
        $data['grade']  = $this->input->post("grade");
        $equipment_img  = $this->input->post("equipment_img");
        $entrust_img  = $this->input->post("entrust_img");
        $contrac_img  = $this->input->post("contrac_img");
        $data['bank_name']  = $this->input->post("bank_name");
        $data['entrust_status']  = $this->input->post("entrust_status");
        $data['parkinginfo']  = $this->input->post("parkinginfo");
        $equipment_address_img  = $this->input->post("equipment_address_img");

        $equipment_position_img  = $this->input->post("equipment_position_img");
        $position_img  = $this->input->post("position_img");

        if($equipment_img != ""){
           $data['equipment_img']  = $equipment_img;
        }
        if($equipment_address_img != ""){
            $data['equipment_address_img']  = $equipment_address_img;
        }
        if($equipment_position_img != ""){
            $data['equipment_position_img']  = $equipment_position_img;
        }

        if($entrust_img != ""){
            $data['entrust_img']  = $entrust_img;
        } if($contrac_img != ""){
            $data['contrac_img']  = $contrac_img;
        }
        $data['install_time'] = strtotime($data['install_time']);

       $clue_id = array_shift($data);
       $days = "";
        foreach ($data['days'] as $v) {
            $days .= "," . $v;
        }
        $data['days'] = ltrim($days, ",");

        $res = $this->business_model->update($clue_id, $data, "clue", "clue_id");
        //点位位置图片入库
        $position_img = explode(",",$position_img);
        foreach($position_img as $v){
             if($v != ""){
                 $this->db->from("clue_img");
                 $this->db->where('clue_id',$clue_id);
                 $this->db->where('position_img',$v);
                 $total = $this->db->get()->row_array();
                 if(!$total){
                     $this->db->insert('clue_img',['position_img'=>$v,'clue_id'=>$clue_id]);
                 }
             }
        }

        if($res){
            $result['status'] = "success";
            $result['msg'] = "保存成功";
        }else{

            $result['status'] = "error";
            $result['msg'] = "保存失败";
        }


        $this->showJson($result);


    }


    //装机信息完善
 //   function install()
   // {
     //   $data = $this->input->post("di");

//        $clue_id = $data['clue_id'];
//        $data['install_time'] = strtotime($data['install_time']);
//
//        $res = $this->business_model->getRow($clue_id, "install_info", "clue_id");
//        if ($res) {
//
//            $clue_id = array_shift($data);
//
//            $res = $this->business_model->update($clue_id, $data, "install_info", "clue_id");
//            if ($res) {
//                $status = "保存成功";
//            } else {
//                $status = "保存失败";
//            }
//        } else {
//
//            $res = $this->business_model->insert($data, "install_info");
//            if ($res) {
//                $status = "保存成功";
//            } else {
//                $status = "保存失败";
//            }
//        }


        //redirect("/business/maintain_add?clue_id=" . $clue_id . "&status=" . $status);
   // }
    //合同维护
    public function contract(){
        $contract_remark = trim($this->input->post("contract_remark"));
        $clue_id = $this->input->post("clue_id");
        $contract_number = trim($this->input->post("contract_number"));
        $contract_img    = $this->input->post("contract_img");
        $contract_name    = $this->input->post("name");
//var_dump($contract_remark);die;
        $res = $this->business_model->contract($contract_remark,$clue_id,$contract_number,$contract_img,$contract_name);

        if($res){
            $result['status'] = "success";
            $result['msg'] = "保存成功";
        }else{

            $result['status'] = "error";
            $result['msg'] = "保存失败";
        }


        $this->showJson($result);


    }
    //删除合同图片
    public function del_contract_img(){
        $id = $this->input->post("id");
        $res = $this->db->set("is_del",1)->where("id",$id)->update("contract_img");
        if($res){
            $result['status'] = "success";
            $result['msg'] = "删除成功";
        }else{

            $result['status'] = "error";
            $result['msg'] = "删除失败";
        }
        $this->showJson($result);
    }
    //查看合同
    public function contract_img_show(){
        $url = $this->input->get("url");
        header("Content-type:application/pdf");
        readfile($url);die;
    }


    public function ajax_upload()
    {
        $pdf = $this->input->get("pdf");
        if($pdf == 1){
            foreach ($_FILES as $k => $v) {

                if($v['type'] == "application/pdf"){

                    $photo_url =   upload_img($v['tmp_name'],$v['name']);
                    //href=""
                    $this->showJson(array('status'=>'success', 'url'=>$photo_url, 'img_http'=>$this->img_http,'name'=>$v['name']));
                }else{
                    $this->showJson(array('status'=>'error', 'message'=>"只支持pdf格式，请重新上传"));
                }

            }
        }else{
            foreach ($_FILES as $k => $v) {

                $photo_url =   upload_img($v['tmp_name'],$v['name']);
                //href=""
                $this->showJson(array('status'=>'success', 'url'=>$photo_url, 'img_http'=>$this->img_http));

            }
        }


    }

    //查看工单下有什么设备id 名称
    public function order_select()
    {
        $clue_id = $this->input->get("clue_id");
        $this->db->where("clue_id", $clue_id);
        $this->db->from("clue_equipment");
        $result = $this->db->get()->result_array();
        $this->_pagedata['commercial_row'] = $this->business_model->getCommercial($clue_id);
        $this->_pagedata['result'] = $result;
        $this->page("business/order_select.html");
    }
    public function clue_export()
    {
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
//        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
//        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $search_name = $this->input->get('search_name');
        $search_province = $this->input->get('search_province');
        $search_city = $this->input->get('search_city');
        $search_area = $this->input->get('search_area');
        $search_address = $this->input->get('search_address');
        $search_contacts = $this->input->get('search_contacts');
        $search_phone = $this->input->get('search_phone');
        $search_re_contacts = $this->input->get('search_re_contacts');
        $search_re_phone = $this->input->get('search_re_phone');
        $search_db_duty = $this->input->get('search_db_duty');
        $search_db_status = $this->input->get('search_db_status');
        $search_source = $this->input->get('search_source');
        $start_time = $this->input->get('search_start_time');
        $end_time = $this->input->get('search_end_time');
        $sort = $this->input->get('sort') ? $this->input->get('sort') : 'clue.clue_id';
        $order = $this->input->get('order') ? $this->input->get('order') : 'desc';

        $where = array("clue.pupr_status  !=" => "0");
        $admin_flag = $this->session->userdata('sess_admin_data')["adminflag"];

        $admin_id = $this->session->userdata('sess_admin_data')["adminid"];

        if ($admin_id != 1 && !in_array(95, $admin_flag)) {
            $where['clue.db_duty '] = $admin_id;

        }
        if ($search_name) {
            $where['clue.name like '] = "%" . $search_name . "%";
        }
        if ($search_address) {
            $where['clue.address like '] = "%" . $search_address . "%";
        }
        if ($search_contacts) {
            $where['clue.contacts like'] = "%" . $search_contacts . "%";
        }
        if ($search_phone) {
            $where['clue.phone like'] = "%" . $search_phone . "%";
        }
        if ($search_re_contacts) {
            $where['clue.re_contacts like'] = "%" . $search_re_contacts . "%";
        }
        if ($search_re_phone) {
            $where['clue.re_phone like'] = "%" . $search_re_phone . "%";
        }
        if ($search_db_duty) {
            if($admin_id != 1 && !in_array(95, $admin_flag)){
                $where['clue.db_duty '] = $admin_id;
            }else{
                $where['clue.db_duty '] = $search_db_duty;
            }

        }
        if ($search_db_status) {
            $where['clue.db_status '] = $search_db_status;
        }
        if ($search_source) {
            $where['clue.source like '] = "%" . $search_source . "%";
        }
        if ($start_time) {
            $where['clue.create_time  >='] = strtotime($start_time);
        }
        if ($end_time) {
            $where['clue.create_time  <='] = strtotime($end_time);
        }
        if ($search_province) {
            $where['clue.province '] = $search_province;
        }
        if ($search_city) {
            $where['clue.city '] = $search_city;
        }
        if ($search_area) {
            $where['clue.area '] = $search_area;
        }
        $rows = $this->business_model->getclue("*", $where, $sort, $order);

//var_dump($rows);die;
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $columns = [
            'A' => ['width' => 10, 'title' => '序号', 'field' => 'clue_id',],
            'B' => ['width' => 10, 'title' => '等级', 'field' => 'grade',],
            'C' => ['width' => 15, 'title' => '点位名称', 'field' => 'name'],
            'D' => ['width' => 10, 'title' => '点位人数', 'field' => 'number'],
            'E' => ['width' => 10, 'title' => '联系人', 'field' => 'contacts'],
            'F' => ['width' => 15, 'title' => '电话', 'field' => 'phone'],
            'G' => ['width' => 10, 'title' => '职位', 'field' => 'contacts_title'],
            'H' => ['width' => 50, 'title' => '地址', 'field' => 'address'],
            'I' => ['width' => 15, 'title' => '所属商户', 'field' => 'merchant_owned'],
            'J' => ['width' => 15, 'title' => '场景', 'field' => 'scene'],
            'K' => ['width' => 20, 'title' => '期望安装时间', 'field' => 'install_time'],
            'L' => ['width' => 20, 'title' => '工单排期时间', 'field' => 'schedule_time'],
            'M' => ['width' => 10, 'title' => '装机数量', 'field' => 'equipment_number'],
            'N' => ['width' => 20, 'title' => '点位合同乙方公司名称', 'field' => 'company'],
            'O' => ['width' => 20, 'title' => '打款账户', 'field' => 'bank_number'],
            'P' => ['width' => 20, 'title' => '是否委托收款', 'field' => 'entrust_status'],
            'Q' => ['width' => 10, 'title' => '是否有便利店', 'field' => 'facilitate_status'],
            'R' => ['width' => 10, 'title' => '是否有微波炉', 'field' => 'micro_status'],
            'S' => ['width' => 10, 'title' => '是否有自动售卖机', 'field' => 'sell_status'],
            'T' => ['width' => 50, 'title' => '备注', 'field' => 'remark'],
            'U' => ['width' => 10, 'title' => '推荐源', 'field' => 'source'],
            'V' => ['width' => 15, 'title' => 'BD负责人', 'field' => 'db_duty'],
            'W' => ['width' => 15, 'title' => 'BD进度', 'field' => 'db_status'],
            'X' => ['width' => 15, 'title' => '客户经理', 'field' => 'merchant_name'],
            'Y' => ['width' => 15, 'title' => '电费/月', 'field' => 'power_money'],
            'Z' => ['width' => 10, 'title' => '支付周期', 'field' => 'power_payment_cycle'],
            'AA' => ['width' => 10, 'title' => '先票后款/先款后票', 'field' => 'power_fare'],
            'AB' => ['width' => 10, 'title' => '预付/后付', 'field' => 'power_payment'],
            'AC' => ['width' => 10, 'title' => '场地费/月', 'field' => 'field_money'],
            'AD' => ['width' => 10, 'title' => '支付周期', 'field' => 'field_payment_cycle'],
            'AE' => ['width' => 10, 'title' => '先票后款/先款后票', 'field' => 'field_fare'],
            'AF' => ['width' => 10, 'title' => '预付/后付', 'field' => 'field_payment'],
            'AG' => ['width' => 10, 'title' => '发票类型', 'field' => 'invoice_status'],
            'AH' => ['width' => 50, 'title' => '装机位置', 'field' => 'equipment_address'],
            'AI' => ['width' => 10, 'title' => '装机联系人', 'field' => 'equipment_name'],
            'AJ' => ['width' => 15, 'title' => '电话', 'field' => 'equipment_phone'],
            'AK' => ['width' => 10, 'title' => '是否需要摄像头', 'field' => 'camera_status'],
            'AL' => ['width' => 20, 'title' => '首次补货注意事项', 'field' => 'first_deliver'],
            'AM' => ['width' => 50, 'title' => '特殊备注', 'field' => 'remark_two']
        ];


        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle('点位线索' . date('YmdHis'));
        //第一行
        $line = 1;
        foreach ($columns as $k => $v) {
            $sheet->getColumnDimension($k)->setWidth($v['width']);
            $sheet->setCellValue("{$k}{$line}", $v['title']);
        }

        //第二行
        $line++;
        foreach ($rows as $k => $row) {
            $province = $this->business_model->position($row['province']);
            $city = $this->business_model->position($row['city']);
            $area = $this->business_model->position($row['area']);
            $row['address'] = $province['AREANAME'] .$city['AREANAME'].$area['AREANAME'].$row['address'];
            //获取商户
            $row['merchant_owned'] = $this->commercial_model->get_platform($row['merchant_owned'])['name'];
            //获取bd负责人
            $row['db_duty'] = $this->business_model->getUser($row['db_duty'])['alias'];
            //BD进度
            $row['db_status'] = self::$db_status[$row['db_status']][1];

            //企业场景
            $row['scene'] = self::$scene[$row['scene']];
            if ($row['install_time']) {
                $row['install_time'] = date("Y-m-d H:i:s", $row['install_time']);
            }
            if ($row['schedule_time']) {
                $row['schedule_time'] = date("Y-m-d H:i:s", $row['schedule_time']);
            }
            $row['facilitate_status'] = self::$status[$row['facilitate_status']];
            $row['micro_status']      = self::$status[$row['micro_status']];
            $row['sell_status']       = self::$status[$row['sell_status']];
            $row['power_fare']        = self::$fare[$row['power_fare']];
            $row['power_payment']     = self::$payment[$row['power_payment']];
            $row['field_fare']        = self::$fare[$row['field_fare']];
            $row['field_payment']     = self::$payment[$row['field_payment']];
            if($row['camera_status'] == 1){
                $row['camera_status'] = "需要";
            }else if($row['camera_status'] == 2){
                $row['camera_status'] = "不需要";
            }else{
                $row['camera_status'] = "";
            }
            if($row['invoice_status'] == 1){
                $row['invoice_status'] = "普票";
            }else if($row['invoice_status'] == 2){
                $row['invoice_status'] = "专票";
            }else{
                $row['invoice_status'] = "";
            }
            if($row['entrust_status'] == 1){
                $row['entrust_status'] = "是";
            }else if($row['entrust_status'] == 2){
                $row['entrust_status'] = "否";
            }else{
                $row['entrust_status'] = "";
            }
            $sheet->setCellValue("A{$line}", $row[$columns['A']['field']]);
            $sheet->setCellValue("B{$line}", $row[$columns['B']['field']]);
            $sheet->setCellValue("C{$line}", $row[$columns['C']['field']]);
            $sheet->setCellValue("D{$line}", $row[$columns['D']['field']]);
            $sheet->setCellValue("E{$line}", $row[$columns['E']['field']]);
            $sheet->setCellValue("F{$line}", $row[$columns['F']['field']]);
            $sheet->setCellValue("G{$line}", $row[$columns['G']['field']]);
            $sheet->setCellValue("H{$line}", $row[$columns['H']['field']]);
            $sheet->setCellValue("I{$line}", $row[$columns['I']['field']]);
            $sheet->setCellValue("J{$line}", $row[$columns['J']['field']]);
            $sheet->setCellValue("K{$line}", $row[$columns['K']['field']]);
            $sheet->setCellValue("L{$line}", $row[$columns['L']['field']]);
            $sheet->setCellValue("M{$line}", $row[$columns['M']['field']]);
            $sheet->setCellValue("N{$line}", $row[$columns['N']['field']]);
            $sheet->setCellValue("O{$line}", $row[$columns['O']['field']]);
            $sheet->setCellValue("P{$line}", $row[$columns['P']['field']]);
            $sheet->setCellValue("Q{$line}", $row[$columns['Q']['field']]);
            $sheet->setCellValue("R{$line}", $row[$columns['R']['field']]);
            $sheet->setCellValue("S{$line}", $row[$columns['S']['field']]);
            $sheet->setCellValue("T{$line}", $row[$columns['T']['field']]);
            $sheet->setCellValue("U{$line}", $row[$columns['U']['field']]);
            $sheet->setCellValue("V{$line}", $row[$columns['V']['field']]);
            $sheet->setCellValue("W{$line}", $row[$columns['W']['field']]);
            $sheet->setCellValue("X{$line}", $row[$columns['X']['field']]);
            $sheet->setCellValue("Y{$line}", $row[$columns['Y']['field']]);
            $sheet->setCellValue("Z{$line}", $row[$columns['Z']['field']]);
            $sheet->setCellValue("AA{$line}", $row[$columns['AA']['field']]);
            $sheet->setCellValue("AB{$line}", $row[$columns['AB']['field']]);
            $sheet->setCellValue("AC{$line}", $row[$columns['AC']['field']]);
            $sheet->setCellValue("AD{$line}", $row[$columns['AD']['field']]);
            $sheet->setCellValue("AE{$line}", $row[$columns['AE']['field']]);
            $sheet->setCellValue("AF{$line}", $row[$columns['AF']['field']]);
            $sheet->setCellValue("AG{$line}", $row[$columns['AG']['field']]);
            $sheet->setCellValue("AH{$line}", $row[$columns['AH']['field']]);
            $sheet->setCellValue("AI{$line}", $row[$columns['AI']['field']]);
            $sheet->setCellValue("AJ{$line}", $row[$columns['AJ']['field']]);
            $sheet->setCellValue("AK{$line}", $row[$columns['AK']['field']]);
            $sheet->setCellValue("AL{$line}", $row[$columns['AL']['field']]);
            $sheet->setCellValue("AM{$line}", $row[$columns['AM']['field']]);



            $line++;
        }

        $sheet->getStyle('A1:AM1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => ['rgb' => 'f9bf92']
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ]
        ]);

        $sheet->getStyle('A1:AM' . ($line-1))->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('argb' => '000000')
                ]
            ]
        ]);

        $objPHPExcel->initHeader('点位线索' . date('Y-m-d'));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        die;
    }

}