<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class workorder extends MY_Controller
{
    public $workgroup = 'workorder';
    public $redis;
    public static $work_status =   array("1"=>"待排期","2"=>"待安装","3"=>"待测试","4"=>"已完成");
    public  static $status = array("1"=>"有","2"=>"无");
    function __construct() {
        parent::__construct();
        $this->load->model("commercial_model");
        $this->load->model("equipment_model");
        $this->load->model("business_model");
        $this->load->model("admin_model");
        $this->load->model("workorder_model");
        $this->load->library('curl',null,'http_curl');



    }
    function work_index(){
        $clue_ids = $this->input->get('clue_ids');
        //获取数据线索
        $this->_pagedata["clue_ids"]  = $clue_ids;
        $this->_pagedata["clue_list"]  = $this->business_model->getclue();
        //获取所有后台人员
        $this->_pagedata["admin_list"] = $this->business_model->getAdmin();
        $this->_pagedata["work_status"]  = self::$work_status;
        $this->page("workorder/work_index.html");
    }

    function work_table(){

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
        $search_work_status = $this->input->get('search_work_status');
        $search_source      = $this->input->get('search_source');
        $start_time         = $this->input->get('search_start_time');
        $end_time           = $this->input->get('search_end_time');
        $equipment_id       = $this->input->get('search_equipment_id') ? $this->input->get('search_equipment_id') : "";
        $search_clue_id       = $this->input->get('search_clue_id') ? $this->input->get('search_clue_id') : "";
        $sort               = 'clue.submit_time';
        $order              = 'desc';

        $where = array("clue.pupr_status" => 2);
        if($search_name){
            $where['clue.name like']  = "%".$search_name."%";
        }
        if($search_address ){
            $where['clue.address like']  = "%".$search_address."%";
        }
        if($search_contacts ){
            $where['clue.contacts like']  = "%".$search_contacts."%";
        }
        if($search_phone ){
            $where['clue.phone like']  = "%".$search_phone."%";
        }
        if($search_re_contacts ){
            $where['clue.re_contacts like']  = "%".$search_re_contacts."%";
        }
        if($search_re_phone ){
            $where['clue.re_phone like']  = "%".$search_re_phone."%";
        }
        if($search_db_duty ){
            $where['clue.db_duty ']  = $search_db_duty;
        }
        if($search_work_status ){
            $where['clue.work_status ']  = $search_work_status;
        }
        if($search_source ){
            $where['clue.source like ']  = "%".$search_source."%";
        }
        if($start_time ){
            $where['clue.submit_time  >=']  =  strtotime($start_time);
        }
        if($end_time ){
            $where['clue.submit_time  <=']  =  strtotime($end_time);
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


        $rows = $this->business_model->getclue("*",$where,$sort,$order,$limit,$offset,$equipment_id,$search_clue_id );

        $this->db->where($where);
        $this->db->select("count(*) as c");
        $this->db->from('clue');
        $total = $this->db->get()->row_array();
        $admin_list = $this->business_model->getAdmin();
        foreach ($rows as $k=>$v){

            $rows[$k]['gl'] = "<a href='#' clue_id='".$v['clue_id']."'  class='btn btn-link  col-sm-offset-2 col-sm-10  equipment_info'  >查看装机信息</a>";
            $province                          = $this->business_model->position($v['province']);
            $city                              = $this->business_model->position($v['city']);
            $area                              = $this->business_model->position($v['area']);
            $rows[$k]['address']              = $province['AREANAME'].$city['AREANAME'].$area['AREANAME'].$v['address'];
            //转换添加时间
            if($v['submit_time']){
                $rows[$k]['submit_time']     = date("Y-m-d H:i:s",$v['submit_time']);
            }
            if($v['schedule_time']){
                $rows[$k]['schedule_time']     = date("Y-m-d H:i:s",$v['schedule_time']);
            }
            if($v['install_time']){
                $rows[$k]['install_time']     = date("Y-m-d H:i:s",$v['install_time']);
            }
            //工单状态转换按钮
            foreach (self::$work_status as $key => $value){
                if($v['work_status'] == $key){

                   $rows[$k]['work_status'] = "<button type='button' value='".$key ."' id='work_status' class='btn  btn-info col-sm-offset-2 col-sm-10 ' style='right:6px;' >".$value."</button>";

                }
            }

            //根据s_admin.id和p_clue.db_duty相等取admin.name
            foreach ($admin_list as $val){

                if($v['db_duty'] == $val['id']){
                    $rows[$k]['admin_name'] = "<button type=\"button\" class=\"btn btn-primary col-sm-offset-2 col-sm-10 \" id='".$v['clue_id']."' style='right:6px;'>".$val['alias']."</button>";
                }
            }

        }

        $result = array("rows"=>$rows,"total"=>$total['c']);
        echo json_encode($result);
    }
    //安装编辑
    function edit_add(){

            $enterprise    = $this->input->post("enterprise");
            $province      = $this->input->post("province");
            $area          = $this->input->post("area");
            $city          = $this->input->post("city");
            $address       = $this->input->post("address");
            $platform_id   = $this->input->post("platform_id");
            $equipment_id  = explode(",",$this->input->post("equipment_id"));
            $name          = explode(",",$this->input->post("name"));
            $clue_id       = $this->input->post("clue_id");
            $arr = array("name"=>$enterprise,"province"=>$province,"area"=>$area,"city"=>$city,"address"=>$address);
            $this->business_model->update($clue_id,$arr,"clue","clue_id");
            $data = "";
            if(is_array($equipment_id)){
                foreach($equipment_id as $k=>$v){
                    $data[$k]['equipment_id'] = $v;
                    $data[$k]['platform_id '] = $platform_id;
                    $data[$k]['name'] = $name[$k];
                }
            }
            $status = "";
            $equipments = array();
            $flag = true;
            foreach($data as $k=>$v){
                $where = array("equipment_id"=>$v['equipment_id']);
                //查询平台是否有equipment_id
                $re   = $this->equipment_model->dump($where);

                if (!$re){

                        $status = "设备id:". $v['equipment_id']."不存在，需商务预先在平台设备管理里添加此设备";
                        $flag = false;
                        break;
                }else{
//                    if( $re['platform_id'] > 0){
//                        $status = "设备id:".$v['equipment_id']."已绑定";
//                        $flag = false;
//                        break;
//                    }else {
                        //查询商户平台是否有equipment_id
                        // $rs = $this->workorder_model->getAdminEquipment($where);
                        $params = array(
                            'timestamp' => time() . '000',
                            'source' => 'platform',
                            'equipment_id' => $v['equipment_id']
                        );
                        $result = $this->add_save($params, "getEquipment");
                        if ($result == true) {
                            $res = $this->workorder_model->save($v['equipment_id'], $platform_id, $v['name'], $clue_id);
                            if ($res == true) {

                                $params = array(
                                    'timestamp' => time() . '000',
                                    'source' => 'platform',
                                    'equipment_id' => $v['equipment_id'],
                                    'platform_id' => $platform_id,
                                    'name' => $v['name'],
                                    'status' => 1
                                );
                                $equipments[] = $v['equipment_id'];
                                $result = $this->add_save($params, "saveEquipment");
                                if($result == true){
                                    $status = '保存成功';

                                }else{

                                    $status = "设备id:".$v['equipment_id'] ."保存失败";
                                    $flag = false;
                                    break;

                                }

                            } else {
                                $status= "设备id:".$v['equipment_id'] . '保存失败';
                                $flag = false;
                                break;
                            }
                        } else {
                            $status = "设备id:".$v['equipment_id'] . "商户平台不存在或已绑定";
                            $flag = false;
                            break;
                        }
//                    }
                }
                sleep(1);//curl 停留1s
            }
            if($flag == false){
                //判断是否全部成功 不则回滚
                $this->rollback($equipments,$clue_id );
                $this->showJson(array('status'=>'error', 'msg' =>  $status ));
            }else{
                $this->showJson(array('status'=>'success', 'msg' =>  $status ));
            }



    }
    //回滚
    public function rollback($equipments,$clue_id ){
        foreach($equipments as $val){
            $this->db->set('platform_id', "");
            $this->db->set('status', 0);
            $this->db->where('equipment_id', $val);
            $this->db->update('equipment');
            $this->db->where('equipment_id', $val);
            $this->db->delete('clue_equipment');
            $this->db->set('work_status', 2);
            $this->db->where('clue_id',$clue_id );
            $this->db->update('clue');
            $params = array(
                'timestamp' => time() . '000',
                'source' => 'platform',
                'equipment_id' => $val
            );
            $this->add_save($params, "rollback");
        }
    }
    //安装编辑页面
    public function edit_index(){

        $clue_id = $this->input->get("clue_id");
        $status = $this->input->get("tips");
        $clue_list = $this->business_model->getRow($clue_id,"clue","clue_id","province,city,area,address,name");
        $clue_equipment =$this->workorder_model->getEquipment($clue_id,"clue_equipment","clue_id");
        $province = $this->business_model->position($clue_list['province']);
        $city = $this->business_model->position($clue_list['city']);
        $area = $this->business_model->position($clue_list['area']);
        $clue_list['province_name'] = $province['AREANAME'];
        $clue_list['city_name'] = $city['AREANAME'];
        $clue_list['area_name'] = $area['AREANAME'];
        $this->_pagedata['clue_list'] = $clue_list;
        $this->_pagedata['commercial_row'] = $this->business_model->getCommercial($clue_id);
        $this->_pagedata['tips'] = $status;
        $this->_pagedata['clue_id'] = $clue_id;
        $this->_pagedata['clue_equipment'] = $clue_equipment;
        $this->page("workorder/edit_add.html");
    }
//    接口
    public function add_save($params,$action){
                //done 打cityboxadmin接口 插入设备或获取设备
//        RBAC_URL    http://stagingcityboxadmin.fruitday.com
                $url =RBAC_URL."/apiEquipment/".$action;

                $params['sign'] = $this->create_platform_sign($params);

                $options['timeout'] = 100;
                $result = $this->http_curl->request($url, $params, 'POST', $options);

                if(json_decode($result['response'],1)['code']==200){
                    return true;
                }else {
                    return false;
                }
    }
    //替换设备id修改
    function edit_update(){
        if($this->input->post("submit")) {
            $enterprise   = $this->input->post("enterprise");
            $province      = $this->input->post("province");
            $area      = $this->input->post("area");
            $city      = $this->input->post("city");
            $address      = $this->input->post("address");
            $platform_id  = $this->input->post("platform_id");
            $equipment_id = $this->input->post("equipment_id");
            $name         = $this->input->post("name");
            $replace_equipment_id = $this->input->post("replace_equipment_id");
            $clue_id      = $this->input->post("clue_id");
            //修改企业名称和企业地址
            $arr = array("name"=>$enterprise,"province"=>$province,"area"=>$area,"city"=>$city,"address"=>$address);
            $this->business_model->update($clue_id,$arr,"clue","clue_id");
            $data = "";
            foreach ($replace_equipment_id as $k => $v) {
                if ($v != "") {
                    $data[$k]['equipment_id'] = $equipment_id[$k];
                    $data[$k]['name'] = $name[$k];
                    $data[$k]['replace_equipment_id'] = $v;
                }
            }
            $status = "";
            foreach ($data as $k => $v) {

                $where = array("equipment_id"=>$v['replace_equipment_id']);
                //查询平台是否有equipment_id
                $re   = $this->equipment_model->dump($where);

                if (!$re){

                    $status .= $v['replace_equipment_id']."平台不存在";
                }else{
//                    if( $re['platform_id'] > 0){
//                        $status .= $v['replace_equipment_id']."已绑定商户";
//                    }else {
                        //查询商户平台是否有equipment_id
                        // $rs = $this->workorder_model->getAdminEquipment($where);
                        $params = array(
                            'timestamp' => time() . '000',
                            'source' => 'platform',
                            'equipment_id' => $v['replace_equipment_id']
                        );
                        $result = $this->add_save($params, "getEquipment");
                        if ($result == true) {
                            $res = $this->workorder_model->replace_equipment($platform_id, $v, $clue_id);

                            if ($res == true) {
                                $params = array(
                                    'timestamp' => time() . '000',
                                    'source' => 'platform',
                                    'equipment_id' => $v['equipment_id'],
                                    'platform_id' => $platform_id,
                                    'name' => $v['name'],
                                    'replace_equipment_id' => $v['replace_equipment_id']
                                );
                                $result = $this->add_save($params, "replaceEquipment");
                                if($result == true){
                                    $status .= $v['replace_equipment_id'] . '保存成功';
                                }else{
                                    $status .= $v['replace_equipment_id'] . '商户平台保存失败';
                                }
                            } else {
                                $status .= $v['replace_equipment_id'] . '保存失败';
                            }
                        }else{
                            $status .= $v['replace_equipment_id'] . '商户平台已存在';
                        }
//                    }
                }
                sleep(1);//curl 停留1s

            }

        }
       redirect("workorder/edit_save?clue_id=".$clue_id."&tips=".$status);
    }
//替换设备id修改页面
    public function edit_save(){

        $clue_id = $this->input->get("clue_id");
        $status  = $this->input->get("tips");
        $clue_equipments = $this->workorder_model->getRow($clue_id,"clue_equipment","clue_id");
        $clue_list = $this->business_model->getRow($clue_id,"clue","clue_id","province,city,area,address,name");
        $province = $this->business_model->position($clue_list['province']);
        $city = $this->business_model->position($clue_list['city']);
        $area = $this->business_model->position($clue_list['area']);
        $clue_list['province_name'] = $province['AREANAME'];
        $clue_list['city_name'] = $city['AREANAME'];
        $clue_list['area_name'] = $area['AREANAME'];
        $this->_pagedata['clue_list'] = $clue_list;

        $this->_pagedata['commercial_row'] = $this->business_model->getCommercial($clue_id);
        $this->_pagedata['clue_id'] = $clue_id;
        $this->_pagedata['tips'] = $status;
        $this->_pagedata['clue_equipments'] = $clue_equipments;
        $this->page("workorder/edit_update.html");
    }
    //更改work_status为4已完成装机
    function ajaxPost(){
        $clue_id = $this->input->post("clue_id");
        $clue_equipment = $this->workorder_model->getRow($clue_id,'clue_equipment','clue_id');
        $res = $this->workorder_model->work_status($clue_id);

        if($res){
            foreach($clue_equipment as $v){
                $rs = $this->workorder_model->equipment_status($v['equipment_id']);
                if($rs){
                    $params = array(
                        'timestamp'=>time() . '000',
                        'source'    => 'platform',
                        'equipment_id'=>$v['equipment_id'],
                        'status'      =>0
                    );
                    $this->add_save($params,"upEquipment");
                }
            }
             $result['status'] = "success";
             $result['total']  = "<button type='button' value='4' id='work_status' class='btn  btn-info col-sm-offset-2 col-sm-10  '  >已完成</button>";
        }else{
            $result['status']  = "error";
        }
        echo json_encode($result);
    }
    //工单排期表单
    function schedule(){
        $this->_pagedata['clue_id']= $this->input->post("clue_id");
        $this->_pagedata['tips']= $this->input->get("clue_id");
        $this->page("workorder/schedule.html");
    }
    //排期
    function schedule_add(){
        $schedule_time = $this->input->post("schedule_time");

        $clue_id = $this->input->post("clue_id");
        $result  = $this->workorder_model->schedule($clue_id,$schedule_time);
        if($result){
            redirect("/workorder/work_index");

        }else{
            redirect("/workorder/schedule?tips=提交失败");
        }
    }
    //工单导出
    function workorder_export(){
//        $limit              = $this->input->get('limit')?$this->input->get('limit'):10;
//        $offset             = $this->input->get('offset')?$this->input->get('offset'):0;
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
        $search_work_status = $this->input->get('search_work_status');
        $search_source      = $this->input->get('search_source');
        $start_time         = $this->input->get('search_start_time');
        $end_time           = $this->input->get('search_end_time');
        $equipment_id       = $this->input->get('search_equipment_id') ? $this->input->get('search_equipment_id') : "";


        $sort               = 'clue.submit_time';
        $order              = 'desc';

        $where = array("clue.pupr_status" => 2);
        if($search_name){
            $where['clue.name like']  = "%".$search_name."%";
        }
        if($search_address ){
            $where['clue.address like']  = "%".$search_address."%";
        }
        if($search_contacts ){
            $where['clue.contacts like']  = "%".$search_contacts."%";
        }
        if($search_phone ){
            $where['clue.phone like']  = "%".$search_phone."%";
        }
        if($search_re_contacts ){
            $where['clue.re_contacts like']  = "%".$search_re_contacts."%";
        }
        if($search_re_phone ){
            $where['clue.re_phone like']  = "%".$search_re_phone."%";
        }
        if($search_db_duty ){
            $where['clue.db_duty ']  = $search_db_duty;
        }
        if($search_work_status ){
            $where['clue.work_status ']  = $search_work_status;
        }
        if($search_source ){
            $where['clue.source like ']  = "%".$search_source."%";
        }
        if($start_time ){
            $where['clue.submit_time  >=']  =  strtotime($start_time);
        }
        if($end_time ){
            $where['clue.submit_time  <=']  =  strtotime($end_time);
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
        $rows = $this->business_model->getclue("clue_id,name,province,city,area,address,,equipment_name,equipment_phone,socket_status,ground_status,ladder_status,attention,install_time,db_duty,schedule_time,work_status,equipment_number",$where,$sort,$order,$limit="", $offset = "",$equipment_id);
//var_dump($rows);
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $columns = [
            'A' => ['width' => 10, 'title' => '序号', 'field' => 'clue_id',],
            'B' => ['width' => 15, 'title' => '点位名称', 'field' => 'name'],
            'C' => ['width' => 50, 'title' => '地址', 'field' => 'address'],
            'D' => ['width' => 10, 'title' => '装机联系人', 'field' => 'equipment_name'],
            'E' => ['width' => 15, 'title' => '装机联系人电话', 'field' => 'equipment_phone'],
            'F' => ['width' => 15, 'title' => '装机数量', 'field' => 'equipment_number'],
            'G' => ['width' => 10, 'title' => '是否有现成电源', 'field' => 'socket_status'],
            'H' => ['width' => 10, 'title' => '是否需要拉接线板', 'field' => 'ground_status'],
            'I' => ['width' => 10, 'title' => '周末是否可以装机', 'field' => 'ladder_status'],
            'J' => ['width' => 50, 'title' => '安装注意事项', 'field' => 'attention'],
            'K' => ['width' => 20, 'title' => '期望安装时间', 'field' => 'install_time'],
            'L' => ['width' => 20, 'title' => 'BD负责人', 'field' => 'db_duty'],
            'M' => ['width' => 20, 'title' => '装机时间', 'field' => 'schedule_time'],
            'N' => ['width' => 10, 'title' => '工单状态', 'field' => 'work_status']

        ];


        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle('装机工单' . date('YmdHis'));
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
//            $row['merchant_owned'] = $this->commercial_model->get_platform($row['merchant_owned'])['name'];
            //获取bd负责人
            $row['db_duty'] = $this->business_model->getUser($row['db_duty'])['alias'];
            $row['socket_status'] = self::$status[$row['socket_status']];
            $row['ground_status'] = self::$status[$row['ground_status']];
            $row['ladder_status'] = self::$status[$row['ladder_status']];
            $row['work_status']   = self::$work_status[$row['work_status']];

            if ($row['install_time']) {
                $row['install_time'] = date("Y-m-d H:i:s", $row['install_time']);
            }
            if ($row['schedule_time']) {
                $row['schedule_time'] = date("Y-m-d H:i:s", $row['schedule_time']);
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
            $line++;
        }

        $sheet->getStyle('A1:M1')->applyFromArray([
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

        $sheet->getStyle('A1:M' . ($line-1))->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('argb' => '000000')
                ]
            ]
        ]);

        $objPHPExcel->initHeader('装机工单' . date('Y-m-d'));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        die;
    }

    public function write_excel($rows){
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $columns = [
            'A' => ['width' => 10, 'title' => '序号', 'field' => 'clue_id',],
            'B' => ['width' => 15, 'title' => '点位名称', 'field' => 'name'],
            'C' => ['width' => 50, 'title' => '地址', 'field' => 'address'],
            'D' => ['width' => 10, 'title' => '装机联系人', 'field' => 'equipment_name'],
            'E' => ['width' => 15, 'title' => '装机联系人电话', 'field' => 'equipment_phone'],
            'F' => ['width' => 15, 'title' => '装机数量', 'field' => 'equipment_number'],
            'G' => ['width' => 10, 'title' => '是否有现成电源', 'field' => 'socket_status'],
            'H' => ['width' => 10, 'title' => '是否需要拉接线板', 'field' => 'ground_status'],
            'I' => ['width' => 10, 'title' => '周末是否可以装机', 'field' => 'ladder_status'],
            'J' => ['width' => 50, 'title' => '安装注意事项', 'field' => 'attention'],
            'K' => ['width' => 20, 'title' => '期望安装时间', 'field' => 'install_time'],
            'L' => ['width' => 20, 'title' => 'BD负责人', 'field' => 'db_duty'],
            'M' => ['width' => 20, 'title' => '装机时间', 'field' => 'schedule_time'],
            'N' => ['width' => 10, 'title' => '工单状态', 'field' => 'work_status']

        ];


        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle('装机工单' . date('YmdHis'));
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
//            $row['merchant_owned'] = $this->commercial_model->get_platform($row['merchant_owned'])['name'];
            //获取bd负责人
            $row['db_duty'] = $this->business_model->getUser($row['db_duty'])['alias'];
            $row['socket_status'] = self::$status[$row['socket_status']];
            $row['ground_status'] = self::$status[$row['ground_status']];
            $row['ladder_status'] = self::$status[$row['ladder_status']];
            $row['work_status']   = self::$work_status[$row['work_status']];

            if ($row['install_time']) {
                $row['install_time'] = date("Y-m-d H:i:s", $row['install_time']);
            }
            if ($row['schedule_time']) {
                $row['schedule_time'] = date("Y-m-d H:i:s", $row['schedule_time']);
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
            $line++;
        }

        $sheet->getStyle('A1:M1')->applyFromArray([
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

        $sheet->getStyle('A1:M' . ($line-1))->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('argb' => '000000')
                ]
            ]
        ]);

        $objPHPExcel->initHeader('装机工单' . date('Y-m-d'));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        die;
    }
    //获取所有的clue_id并导入excel
    public function get_clue_ids(){
        $clue_ids = $this->input->get('clue_ids');
        $clue_ids = explode(",",$clue_ids);
        $result = $this->db->from('clue')->where_in('clue_id',$clue_ids)->order_by('clue_id','asc')->get()->result_array();
        $this->write_excel($result);

    }
    //百度地图显示未完成的点位
    public function map(){
        $this->title = '点位分布概览';
        //pupr_status    work_status
        //查询提交工单并未完成的点位
        $result = $this->db->select('clue_id,name,province,city,area,address')->from('clue')->where('pupr_status',2)->where('work_status !=',4)->get()->result_array();
        foreach($result as $val){
            $province = $this->business_model->position($val['province']);
            $city = $this->business_model->position($val['city']);
            $area = $this->business_model->position($val['area']);
            $val['address'] = $province['AREANAME'] .$city['AREANAME'].$area['AREANAME'].$val['address'];
        }
        $this->_pagedata['list'] = $result;
        $this->_pagedata['work_status'] = self::$work_status;
        $this->page('workorder/map.html');
    }

    //搜索并显示工单到地图
    public function get_map(){

        $search_province = $this->input->get('search_province');
        $search_city = $this->input->get('search_city');
        $search_area = $this->input->get('search_area');
        $search_address = $this->input->get('search_address');
        $search_work_status = $this->input->get('search_work_status');
        $where = [];
        if($search_province){
            $where['province'] = $search_province;
        }
        if($search_city){
            $where['city'] = $search_city;
        }
        if($search_area){
            $where['area'] = $search_area;
        }
        if($search_address){
            $where1 = $search_address;
        }
        if($search_work_status){
            $where['work_status'] = $search_work_status;
        }
        $list = $this->db->select('clue_id,name,province,city,area,address')->from('clue')->where('pupr_status',2)->where($where)->
            like('address ',$where1)->get()->result_array();
        foreach($list as $val){
            $province = $this->business_model->position($val['province']);
            $city = $this->business_model->position($val['city']);
            $area = $this->business_model->position($val['area']);
            $val['address'] = $province['AREANAME'] .$city['AREANAME'].$area['AREANAME'].$val['address'];
        }
        $this->showJson($list);
    }





}