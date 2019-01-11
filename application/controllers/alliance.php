<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Alliance extends MY_Controller {
    public $workgroup = 'commercial';
    private $open_refer = array();
    private  $secret_cityboxapi = '48eU7IeTJ6zKKDd1';
    function __construct() {
        parent::__construct();
        $this->load->model('commercial_model');
        $this->open_refer = array('alipay'=>'支付宝','wechat'=>'微信');
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }
    public function index(){
        $this->page('alliance/index.html');
    }

    public function table()
    {
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $sql = "select * from p_alliance order by id desc limit {$offset},{$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list  as &$value){
            $alipay_config = json_decode($value['alipay_config'],1);
            $wechat_config = json_decode($value['wechat_config'],1);
//            var_dump($configs);
            $value['alipay_config'] = "";
            foreach ($alipay_config as $k=>$v){
                $value['alipay_config'] .=$k.'->'.$v."<br/>";
            }
            $value['wechat_config'] = "";
            foreach ($wechat_config as $k=>$v){
                $value['wechat_config'] .=$k.'->'.$v."<br/>";
            }
            $platform = $this->commercial_model->get_platform($value['platform_id']);
            $value['platform_name'] = $platform['name'];
        }
        $sql = "select count(id) as num from p_alliance ";
        $total = $this->db->query($sql)->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);
    }

    public function add()
    {
        $where = array('status'=>1);
        $commercial_list = $this->commercial_model->getList("*", $where);
        $this->_pagedata['commercial_list'] = $commercial_list;
        $this->_pagedata["open_refer"] = $this->open_refer;
        $this->page('alliance/add.html');
    }
    public function edit()
    {
        $where = array('status'=>1);
        $commercial_list = $this->commercial_model->getList("*", $where);
        $this->_pagedata['commercial_list'] = $commercial_list;
        $this->_pagedata["open_refer"] = $this->open_refer;
        $id = $this->uri->segment(3);
        $sql = "select * from p_alliance WHERE  id = {$id}";
        $info = $this->db->query($sql)->row_array();
        $config['refer'] = json_decode($info['refer'],1);
        foreach ($this->open_refer as $rk=> $rf){
            if(in_array($rk,$config['refer'])){
                $open_refer_check[$rk] = true;
            }else{
                $open_refer_check[$rk] = false;
            }
        }
        $this->_pagedata["open_refer_check"] = $open_refer_check;
        $this->_pagedata['config'] = $info;
        $this->page('alliance/add.html');
    }
    function save(){
        $_POST['last_update'] = date('Y-m-d H:i:s');
        $_POST['refer'] = json_encode($_POST['refer']);
        $is_exist = $this->checkAlliance($_POST['contact_tel'],$_POST['id']);
        if ($is_exist == true){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该联系方式已存在！");history.back();</script></head>';
            exit;
        }
        //判断当前商户是否走的分账模式
        $commercial_model = $this->commercial_model->get_platform($_POST['platform_id']);
        if ($commercial_model['is_separate'] == 1){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该商户启用了分账，无法配置加盟商！请走门店模式！");history.back();</script></head>';
            exit;
        }
        $_POST['login_username'] = 'jms_'.$_POST['contact_tel'];
        if((int)$_POST['id']==0){
            $_POST['created_time'] = date('Y-m-d H:i:s');
            //调用发送密码接口
            $pwd = rand(100000, 999999);
            $_POST['login_pwd'] = md5($pwd);
            $params = array(
                'mobile'=> $_POST['contact_tel'],
                'pwd'=> $pwd
            );
            //        var_dump($params);
            $sign = $this->create_sign_cbapi($params);
            //        echo $sign.'<br>';

            $headers = array("sign:$sign","platform:admin");


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
            curl_setopt($ch, CURLOPT_URL, "http://cityboxapi.fruitday.com/api/account/send_alliance_pwd?mobile=".$_POST['contact_tel']."&pwd=".$pwd."");
            //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $this->db->insert('p_alliance',$_POST);
            $insert_id = $this->db->insert_id();
        }
        else{
            $this->db->update('p_alliance',$_POST,array('id'=>$_POST['id']));
            $insert_id = $_POST['id'];
        }
        //往s_admin表中插入一个虚拟帐号
        $this->c_db->dbprefix = '';
        $admin = $this->c_db->select('id')->from('s_admin')->where(array(
            'name'=>'jms_'.$insert_id
        ))->get()->row_array();
        if (!$admin){
            $this->c_db->insert('s_admin',array('name'=>'jms_'.$insert_id,'pwd'=>md5('jms_login'),'ctime'=>time(),'alias'=>'加盟商'.$insert_id,'platform_id'=>$_POST['platform_id']));
        } else {
            $this->c_db->update('s_admin',array('platform_id'=>$_POST['platform_id']),array('id'=>$admin['id']));
        }
        //refresh_config_cache();
        redirect('/alliance/index');
    }

    function checkAlliance($contact_tel,$id){
        $sql = "select id from p_alliance where contact_tel = '".$contact_tel."'";
        if ($id){
            $sql .=" and id <>".$id;
        }
        $info = $this->db->query($sql)->row_array();
        if ($info){
            return true;
        } else {
            return false;
        }
    }

    function refresh_config(){
        refresh_config_cache();
        die("succ");
    }
    function del_pd_config(){
        $id = $this->uri->segment(3);
        $sql = "delete from p_config_device WHERE  id = {$id}";
        $this->db->query($sql);
        refresh_config_cache();
        die("succ");
    }
    public function create_sign_cbapi($params){
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_cityboxapi), 0, -1) . 'w');
        return $sign;
    }

    public function resetPassword(){
        $id = $_POST['id'];
        $sql = "select * from p_alliance where id = '".$id."'";
        $info = $this->db->query($sql)->row_array();
        if (!$info){
            $result = array(
                'status' => false,
                'msg' => '不存在该加盟商！'
            );
            echo json_encode($result);
        }
        $mobile = $info['contact_tel'];
        //调用发送密码接口
        $pwd = rand(100000, 999999);
        $_POST['login_pwd'] = md5($pwd);
        $params = array(
            'mobile'=> $mobile,
            'pwd'=> $pwd
        );
        //        var_dump($params);
        $sign = $this->create_sign_cbapi($params);
        //        echo $sign.'<br>';

        $headers = array("sign:$sign","platform:admin");


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
        curl_setopt($ch, CURLOPT_URL, "http://cityboxapi.fruitday.com/api/account/send_alliance_pwd?mobile=".$mobile."&pwd=".$pwd."");
        //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if ($result->status == true){
            $this->db->update('p_alliance',$_POST,array('id'=>$_POST['id']));
            $return_result = array(
                'status' => true,
            );
            echo json_encode($return_result);
        } else {
            $return_result = array(
                'status' => false,
                'msg' => $result->message,
            );
            echo json_encode($return_result);
        }
    }
}
