<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Dimerchants extends MY_Controller {
    public $workgroup = 'dimerchants';
    const LOCK_LIMIT_MAX = 5;
    const RESET_PWD = "123456##!";

    function __construct() {
        parent::__construct();
        $this->load->model('dimerchants_model');
    }
    function index(){
        $this->page('dimerchants/list.html');
    }
    function add(){
        $this->page('dimerchants/add.html');
    }
    function table(){
        $di_db = $this->load->database('dimerchants_master', TRUE);
        $limit = $this->input->get('limit') ? : 10;
        $offset = $this->input->get('offset') ? : 0;
        $search_merchant_name = $this->input->get('search_merchant_name') ? : '';
        $search_status = $this->input->get('search_status');
        $sql = "select * from p_dimerchants where 1=1 ";
        if ($search_merchant_name){
            $sql.= " and merchant_name like '%".$search_merchant_name."%' ";
        }
        if ($search_status){
            $sql.= " and status = '".$search_status."' ";
        }
        $sql .= "limit ".$offset.",".$limit;
        $rs = $this->db->query($sql)->result_array();
        foreach ($rs as &$v) {
            $v['created_time'] = date('Y-m-d H:i:s',$v['created_time']);
            $card_sql = "select count(id) as count_c from di_card where merchant_id = '".$v['id']."'";
            $card_query = $di_db->query($card_sql)->row_array();
            $v['card_count'] = $card_query['count_c'] ? $card_query['count_c'] : 0;
            $card_sql = "select count(id) as count_c from di_card where is_used = 1 and merchant_id = '".$v['id']."'";
            $card_query = $di_db->query($card_sql)->row_array();
            $v['validate_count'] = $card_query['count_c'] ? $card_query['count_c'] : 0;
            $v['status_name'] = $v['status'] == 1 ? '启用' : '停用';
            $v['action'] = '<a href = "/dimerchants/edit/'.$v['id'].'">修改</a> <a href = "/dimerchants/resetPassword/'.$v['id'].'">重置密码</a>';
        }
        $result = array(
            'total' => count($rs),
            'rows' => $rs
        );
        echo json_encode($result);
    }
    function edit(){
        $id = $this->uri->segment(3);
        $sql = "select * from p_dimerchants WHERE  id = $id";
        $rs = $this->db->query($sql)->row_array();
        $this->_pagedata['info'] = $rs;
        $this->page('dimerchants/add.html');
    }
    function merchantSave(){
        $id = $_POST['id'];
        $admin_name = $_POST['admin_name'] ? : '';
        if (!$id){
            $sql = "select * from p_dimerchants WHERE  admin_name = '".$admin_name."'";
            $rs = $this->db->query($sql)->row_array();
            if ($rs){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该登陆帐号已存在！");location.href = "/dimerchants/add";</script></head>';
            }
        }

        if($id){
            $this->db->update('p_dimerchants',$_POST,array('id'=>$_POST['id']));
            //todo 修改dimerchants帐号密码
        }
        else{
            $_POST['created_time'] = time();
            $_POST['created_admin'] = $this->operation_name;
            $password = $_POST['password1'];
            unset($_POST['password1']);
            unset($_POST['password2']);
            unset($_POST['id']);
            $this->db->insert('p_dimerchants',$_POST);
            $merchant_id = $this->db->insert_id();
            //todo 增加dimerchants帐号密码
            $di_db = $this->load->database('dimerchants_master', TRUE);
            $di_db->set_dbprefix('');
            $admin_data = array(
                'name'=>$admin_name,
                'pwd'=>md5($password),
                'ctime'=>time(),
                'alias'=>$_POST['contact_name'],
                'mobile'=>$_POST['contact_tel'],
                'merchant_id'=>$merchant_id,
                'status'=>1
            );
            $di_db->insert('s_admin',$admin_data);

        }
        redirect('/dimerchants/index');
    }

    function resetPassword(){
        $id = $this->uri->segment(3);
        $sql = "select * from p_dimerchants WHERE  id = $id";
        $rs = $this->db->query($sql)->row_array();
        if (!$rs){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该商户不存在！");location.href = "/dimerchants/index";</script></head>';
        }
        $di_db = $this->load->database('dimerchants_master', TRUE);
        $di_db->set_dbprefix('');
        $di_db->update('s_admin',array('pwd'=>md5(self::RESET_PWD)),array('merchant_id'=>$id));
        echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("重置成功！");location.href = "/dimerchants/index";</script></head>';
    }


}
