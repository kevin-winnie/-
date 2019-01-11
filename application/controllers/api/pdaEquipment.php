<?php

/**
 * Created by PhpStorm.
 * User: sunyitao
 * Date: 2017/6/21
 * Time: 下午5:54
 */


class pdaEquipment extends CI_Controller
{
    protected $response;
    const LOCK_LIMIT_MAX = 5;
    protected  $eq_type = array(
        array('code'=>'rfid-1','name'=>"rfid-1[蚂蚁盒子RFID]",'type'=>'type'),
        array('code'=>'rfid-2','name'=>"rfid-2[自己生产RFID]",'type'=>'type'),
        array('code'=>'rfid-3','name'=>"rfid-3[数字RFID]",'type'=>'type'),
        array('code'=>'rfid-4','name'=>"rfid-4[无膜RFID]",'type'=>'type'),
        array('code'=>'rfid-5','name'=>"rfid-5[无膜RFID-数烨]",'type'=>'type'),
        array('code'=>'rfid-6','name'=>"rfid-6[数字RFID-数烨]",'type'=>'type'),
        array('code'=>'scan-1','name'=>"scan-1[扫码]",'type'=>'type'),
        array('code'=>'vision-1','name'=>"vision-1[视觉]",'type'=>'type'),
        array('code'=>'vision-2','name'=>"vision-2[视觉-数烨]",'type'=>'type'),
        array('code'=>'vision-3','name'=>"vision-3[静态视觉]",'type'=>'type'),
        array('code'=>'coffee-1','name'=>"coffee-1[咖啡设备-沙丁鱼]",'type'=>'type'),
    );
    protected $isOptional =  [
      //  'type'=> ['name'=>'类型','isOptional'=>true],
        'equipment_id'=> ['name'=>'设备id','isOptional'=>true],
        'box'=> ['name'=>'箱体','isOptional'=>false],
        'router'=> ['name'=>'路由器','isOptional'=>true],
        'monitor'=> ['name'=>'显示器','isOptional'=>false],
        'writer'=> ['name'=>'读写器','isOptional'=>true],
        'camera'=> ['name'=>'摄像头','isOptional'=>false],
        'sm_number'=> ['name'=>'SIM卡号','isOptional'=>false]
    ];
    function __construct()
    {
        parent::__construct();
        $this->load->model("equipment_model");
        $this->load->model("admin_model");
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();

    }
    //app登录
    function  login(){

        $name = trim(addslashes($this->input->post("name")));
        $pwd  = addslashes($this->input->post("pwd"));
        if ($name && $pwd) {
            $admin = $this->admin_model->getAdmin($name);

            if ($admin) {
                if ($admin['lock_limit'] >= self::LOCK_LIMIT_MAX ) {

                    $this->err_response("账户被冻结，请联系管理员");
                } else {

                    if ($admin['pwd'] == md5($pwd)) {

                        $this->admin_model->updateLock($admin['id'], 0);
                        $sess_admin_data = array(
                            'adminid' => $admin['id'],
                            'adminname' => $admin['name'],
                            'adminalias' => empty($admin['alias']) ? $admin['name'] : $admin['alias'],
                            'adminflag' => explode(",", $admin['flag']),
                            'adminTimestamp' => time(),
                            'adminfirst'=> $admin['is_first'],
                            'adminLevel'=> $admin['level'],
                            'adminPlatformId'=> $admin['platform_id'],
                        );
                        $this->session->set_userdata('sess_admin_data', $sess_admin_data);
                        $token = session_id();
                        $requestIP = $this->input->ip_address();
                        $this->admin_model->insertLogin($admin['id'], $requestIP);
                        $this->admin_model->updateLoginTime($admin['id']);
                        $this->db->query("update s_admin set token='".$token."' where name = '".$name."'");
                        $data = array("token"=>$token,"adminalias"=>$sess_admin_data['adminalias']);
                        $this->succ_response("登录成功",$data);

                    } else {
                        $lock_limit = $admin['lock_limit'] + 1;
                        $lock_limit = $lock_limit >= 5 ? 5 : $lock_limit;
                        $this->admin_model->updateLock($admin['id'], $lock_limit);

                        $this->err_response("输入用户名或密码有误");
                    }
                }
            } else {

                $this->err_response("输入用户名或密码有误");
            }
        } else {
            $this->err_response('用户名或密码不能为空');

        }

    }



//公共部分

    //验签PLATFORM
    protected function validate_citybox() {
        foreach ($_SERVER as $key => $value) {
            if ('HTTP_' == substr($key, 0, 5)) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }
        $request_sign = isset($headers['SIGN']) ? $headers['SIGN'] : '';
        unset($headers['SIGN']);
        if($headers['TOKEN']==""){
            unset($headers['TOKEN']);
        }
        ksort($headers);
        $query = '';
        foreach($headers as $k => $v) {
            if ($k == 'TOKEN' || $k == 'PACKAGE' || $k == 'VERSION' || $k == 'MOBILEDEVICE' || $k=="TS"){

                $query .= strtolower($k) . strtolower($v);
            }
        }
        $validate_sign = strtoupper(sha1($query."lkjflkdjsalfjdlsajflkjdsaf"));
        if ($validate_sign == $request_sign) {
            $bool = true;
        } else {
            $data = array($request_sign,$validate_sign,$query);
            $this->err_response("签名错误",$data);
            $bool = false;
        }
        return $bool;
    }
    //判断值是否未空
    protected function is_null($val,$msg){

        if(empty($val)){
            echo json_encode(array("code"=>300,"msg"=>$msg),JSON_UNESCAPED_UNICODE);die;
        }
    }
    protected function succ_response($msg,$data=""){
        echo json_encode(array("code"=>200,"msg"=>$msg,'data'=> $data),JSON_UNESCAPED_UNICODE);die;
    }

    protected  function err_response($msg,$data=""){

        echo json_encode(array("code"=>300,"msg"=>$msg,'data'=> $data),JSON_UNESCAPED_UNICODE);die;
    }

    protected function sess_admin_data($token)
    {
        session_write_close();
        session_id($token);
        session_start();

        $sess_admin_data = $this->session->userdata('sess_admin_data');

        if($sess_admin_data == ""){
            echo json_encode(array("code"=>250,"msg"=>"token失效"),JSON_UNESCAPED_UNICODE);die;
        }
        $admin_token = $this->db->query("select token from s_admin  where name = '".$sess_admin_data['adminname']."'")->row_array();
//            var_dump($admin_token);die;
        if($admin_token['token'] != $_SERVER['HTTP_TOKEN'] ){
            echo json_encode(array("code"=>250,"msg"=>"账号在另一台设备登录"),JSON_UNESCAPED_UNICODE);die;
        }
        return $sess_admin_data;



    }

//公共部分
    //退出
    function logout(){
        $token   = $_SERVER['HTTP_TOKEN'];
        $this->is_null($token,"token为空");
        $this->validate_citybox();
        session_id($token);
        session_destroy();
        $this->session->sess_destroy();
        $this->succ_response('退出成功');
    }
    /*
     * 接收数据
     */
    function get_data($params){
        if(!is_array($params)){
            $params = json_decode($params,1);
        }
        $data = [];
        foreach($params as $val){
            $data[$val['type']] = $val['code'];
        }
        return $data;
    }
/**
 * 组装设备添加
 *
 */
   public function assemble_add(){
//
//       $token   = $_SERVER['HTTP_TOKEN'];
//       $this->is_null($token,"token为空");
//       $sess_admin_data = $this->sess_admin_data($token);
       $params = $this->input->post('facility_list');
       $data = $this->get_data($params);
       var_dump($data);die;
     //var_dump($data);die;
       $this->is_null($data['equipment_id'],"设备id为空");
       foreach($data as $k=>$v){
           $row = $this->db->from('assemble_equipment')->where($k,trim($v))->get()->row_array();
           if($row){
               $name = $this->isOptional['equipment_id']['name'];

               $this->err_response('此'.$name.'已组装添加');

           }
       }

       $data['admin_id'] = $sess_admin_data["adminid"];
       $data['create_time'] = time();
//print_r($data);die;
       $res = $this->equipment_model->add_assemble($data);
       if($res){
           $this->succ_response('组装添加成功');
       }else{
           $this->err_response('组装添加失败');
       }
   }
   /**
    * 获取组装设备信息
    *
    */
    public function get_assemble_equipment(){
        $token   = $_SERVER['HTTP_TOKEN'];
        $this->is_null($token,"token为空");
        $sess_admin_data = $this->sess_admin_data($token);
        $equipment_id = $this->input->post('equipment_id');
        $this->is_null($equipment_id,"设备id为空");
        $row = $this->db->select('id,type,equipment_id,box,router,monitor,writer,camera,sm_number')->from('assemble_equipment')->where('equipment_id',trim($equipment_id))->get()->row_array();
        $type = ['equipment_id'=>'设备id','box'=>'箱体','type'=>'类型','router'=>'路由器','monitor'=>'显示器','writer'=>'读写器','camera'=>'摄像头','sm_number'=>'SIM卡号','id'=>'id'];
        foreach($row as $key=>$val){
            $data[$key]['code'] =$val ? $val : "";
            $data[$key]['type'] =$key;
            $data[$key]['name'] = $type[$key] ? $type[$key] : "";
            $data[$key]['isOptional'] = $this->isOptional[$key]['isOptional'];
        }
        $data =  array_values($data);
        if($row){
            $this->succ_response('获取成功',$data);
        }else{
            $this->err_response('获取失败');
        }

    }
    //添加表单哪些是必填项和选填
    public function getoptional(){
      //  箱体、显示器、摄像头、SIM卡 选填
        $token   = $_SERVER['HTTP_TOKEN'];
        $this->is_null($token,"token为空");
        $sess_admin_data = $this->sess_admin_data($token);
       $array = $this->isOptional;
        foreach($array as $key=>$val){
            $data[$key]['code'] ="";
            $data[$key]['type'] =$key;
            $data[$key]['name'] = $val['name'];
            $data[$key]['isOptional'] = $val['isOptional'];
        }
        $type = $this->eq_type;
       // var_dump($type);die;
        $data =  array_values($data);

        $this->succ_response('获取成功',['data'=>$data,'type'=>$type]);
    }
    /**
     * 组装设备编辑
     *
     */
    public function assemble_update(){
        $token   = $_SERVER['HTTP_TOKEN'];
        $this->is_null($token,"token为空");
        $sess_admin_data = $this->sess_admin_data($token);
        $params = $this->input->post('facility_list');
        $data = $this->get_data($params);
        //var_dump($data);die;
        $this->is_null($data['equipment_id'],"设备id为空");
        $id = $data['id'];
        unset($data['id']);
        $row = $this->db->from('assemble_equipment')->where('id',$id)->get()->row_array();
        //var_dump($row);die;
        $log = true;
        foreach($data as $k=>$v){

            if ($row[$k] != $v) {
                $result = $this->db->from('assemble_equipment')->where($k, trim($v))->get()->row_array();
                if ($result) {
                    $log = false;
                    break;
                }
            }
        }
        if($log == true){
            $res = $this->db->where('id',$id)->update('assemble_equipment',$data);
        }

        if($res){
            $this->succ_response('编辑成功');
        }else{
            $this->err_response('编辑失败');
        }
    }

}