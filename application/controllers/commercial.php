<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Commercial extends MY_Controller {
    public $img_http  = 'http://fdaycdn.fruitday.com/';
    public $workgroup = 'commercial';
    const LOCK_LIMIT_MAX = 5;
    const RESET_PWD = "123456##!";
    public $redis;
    private $com_redis_pre = 'comercial_';
    private $open_refer = [];
    private $config_type  = array(
            array('key'=>'wechat','name'=>"微信公众号"),
            array('key'=>'alipay','name'=>"支付宝公众号")
            );
    private $groups  = array();

    function __construct() {
        parent::__construct();
        $this->p_db = $this->load->database('platform_master', TRUE);
        $this->load->model('commercial_model');
        $this->load->model('icon_model');
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
        $this->load->library('curl',null,'http_curl');
        $this->load->helper('config');
        $this->_pagedata['config_types'] = $this->config_type;;


        $sql = "select * from p_param WHERE  `type` = 'group_code'";
        $rs = $this->db->query($sql)->result_array();
        $group = array();
        foreach ($rs as $v){
            $group[]= array('code'=>$v['key'],'name'=>$v['value']);
        }
        $this->groups = $group;
        $refer_list = $this->db->from('refer')->get()->result_array();
        $refers = [];
        foreach($refer_list as $v){
           $refers[$v['refer']] = $v['short_name'];
        }
        $this->open_refer = $refers;
    }
    function group(){
        $this->page('commercial/groupList.html');
    }
    function add_group(){
        $this->page('commercial/groupAdd.html');
    }
    function group_table(){
        $sql = "select * from p_param WHERE  `type` = 'group_code'";
        $rs = $this->db->query($sql)->result_array();
        foreach ($rs as &$v){
//            $v['action'] = '<a href = "/commercial/edit_group/'.$v['id'].'">修改</a> <a href = "/commercial/edit_group/'.$v['id'].'">删除</a>';
            $v['action'] = '<a href = "/commercial/edit_group/'.$v['id'].'">修改</a> ';
        }
        $result = array(
            'total' => count($rs),
            'rows' => $rs
        );
        echo json_encode($result);
    }
    function edit_group(){
        $id = $this->uri->segment(3);
        $sql = "select * from p_param WHERE  id = $id";
        $rs = $this->db->query($sql)->row_array();
        $this->_pagedata['info'] = $rs;
        $this->page('commercial/groupAdd.html');
    }
    function commercialGroupSave(){
        $_POST['type'] = 'group_code';
        if((int)$_POST['id']==0){
            $_POST['id'] = 0;
            $_POST['created_time'] = date('Y-m-d H:i:s');
            $this->db->insert('p_param',$_POST);
        }
        else{
            $this->db->update('p_param',$_POST,array('id'=>$_POST['id']));
        }
        redirect('/commercial/group');
    }
    function commercialList(){
            $search = $this->input->post();
            if (!empty($search['name'])) {
                $where['name like '] = '%'.trim($search['name'].'%');
            }

            if (!empty($search['mobile'])) {
                $where['phone'] = trim($search['mobile']);
            }
            $where['high_agent_id'] = $this->platform_id;
            $platform_list    = $this->commercial_model->get_agent_level_list_pt($this->platform_id,2);
            if($this->svip)
            {

                $this->_pagedata['is_svip'] = 1;
                //代理商级别
                $Agent = $this->agent_model->get_own_agents($this->platform_id);
                $platform_list = $this->commercial_model->get_agent_level_list($Agent,1);
                $agent_level_list = $this->commercial_model->get_agent_level_list($Agent,2);
            }
            foreach($platform_list as $key=>$val)
            {
                $res = $this->commercial_model->get_agent_by_commercial($val['high_agent_id']);
                $platform_list[$key]['agent_name'] = $res['name'];
            }
            $this->title = '商户列表';
            $this->_pagedata['search'] = $search;
            $this->_pagedata ["list"] = $platform_list;
            $this->_pagedata ["agent_list"] = $agent_level_list;
            //需要看到所有商户及下级代理的商户数
            $this->page('commercial/commercialList.html');
    }

    //设置商户的缓存数据
    private function setCommInfo($id,$data){
        $this->redis->hSet($this->com_redis_pre.$id,'update_time',date("Y-m-d H:i:s"));
        $this->redis->hSet($this->com_redis_pre.$id,'need_deliver',$data['need_deliver']);
        $this->redis->hSet($this->com_redis_pre.$id,'need_product',$data['need_product']);
        $this->redis->hSet($this->com_redis_pre.$id,'ali_appid',$data['ali_appid']);
        $this->redis->hSet($this->com_redis_pre.$id,'ali_secret',$data['ali_secret']);
        $this->redis->hSet($this->com_redis_pre.$id,'pay_succ_tpl_id',$data['pay_succ_tpl_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'pay_fail_tpl_id',$data['pay_fail_tpl_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'refund_tpl_id',$data['refund_tpl_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'notify_tpl_id',$data['notify_tpl_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'pay_user_id',$data['pay_user_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'pay_cent',$data['pay_cent']);

        $this->redis->hSet($this->com_redis_pre.$id,'wechat_appid',$data['wechat_appid']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_secret',$data['wechat_secret']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_mchid',$data['wechat_mchid']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_key',$data['wechat_key']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_planid',$data['wechat_planid']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_pay_succ_tpl_id',$data['wechat_pay_succ_tpl_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_pay_fail_tpl_id',$data['wechat_pay_fail_tpl_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_refund_tpl_id',$data['wechat_refund_tpl_id']);
        $this->redis->hSet($this->com_redis_pre.$id,'wechat_notify_tpl_id',$data['wechat_notify_tpl_id']);

        $this->redis->hSet($this->com_redis_pre.$id,'status',$data['status']?$data['status']:1);
    }

    function ajax_commercial() {
        $ids = $_POST['id'];
        if (empty($ids)) {
            return false;
        }
        $id['id'] =  $ids;

        $data[$_POST['key']] = $_POST['val'];

        $res = $this->commercial_model->update($data, $id);
        $this->commercial_model->setCommInfo($id);
        $ajaxReturn['code'] = !$res ? 0 : 1;
        $ajaxReturn['msg'] = !$res ? "更新失败，请重新尝试" : "更新成功";
        echo json_encode($ajaxReturn);
        exit;
    }

    function commercialUpdate($id){
//        var_dump($_POST);die;
        if($this->input->post("submit")) {
            $post = $this->input->post();
            $old_commercial = $this->commercial_model->dump(array('id'=>$post['id']));
            $old_is_separate = $old_commercial['is_separate'];
            $old_separate_refer = $old_commercial['separate_refer'];
            $old_separate_refer_percent = $old_commercial['separate_refer_percent'];
            $old_alipay_account = $old_commercial['alipay_account'];
            $old_alipay_realname = $old_commercial['alipay_realname'];

            $refer_list = $this->db->from('refer')->get()->result_array();
            $refers = [];
            foreach($refer_list as $k=>$v){
                $refers[$v['refer']] = round($post['separate_refer_percent'][$k],2);
            }
            $data = array(
                'name'=>$post['name'],
                'short_name'=>$post['short_name'],
                'contacts'=>$post['contacts'],
                'phone'=>$post['phone'],
                'address'=>$post['address'],
                'need_deliver'=>$post['need_deliver'],
                'need_product'=>$post['need_product'],
                'ali_appid'=>$post['ali_appid'],
                'ali_secret'=>$post['ali_secret'],
                'pay_succ_tpl_id'=>$post['pay_succ_tpl_id'],
                'pay_fail_tpl_id'=>$post['pay_fail_tpl_id'],
                'refund_tpl_id'=>$post['refund_tpl_id'],
                'notify_tpl_id'=>$post['notify_tpl_id'],
                'pay_user_id'=>$post['pay_user_id'],
                'pay_cent'=>$post['pay_cent'],
                'wechat_appid'=>$post['wechat_appid'],
                'wechat_secret'=>$post['wechat_secret'],
                'wechat_mchid'=>$post['wechat_mchid'],
                'wechat_key'=>$post['wechat_key'],
                'wechat_planid'=>$post['wechat_planid'],
                'wechat_pay_succ_tpl_id'=>$post['wechat_pay_succ_tpl_id'],
                'wechat_pay_fail_tpl_id'=>$post['wechat_pay_fail_tpl_id'],
                'wechat_refund_tpl_id'=>$post['wechat_refund_tpl_id'],
                'wechat_notify_tpl_id'=>$post['wechat_notify_tpl_id'],
                'kf_tel' =>$post['kf_tel'],
                'msg_title'=>$post['msg_title'],
                'img_banner'=>$post['img_banner'],
                'qr_logo'=>$post['qr_logo'],
                'is_separate'=>$post['is_separate'],
                'separate_refer'=>json_encode($post['separate_refer']),
                'separate_refer_percent'=>json_encode($refers),
                'alipay_account'=>$post['alipay_account'],
                'alipay_realname'=>$post['alipay_realname'],
                'wechat_name'=>$post['wechat_name'],
                'alipay_name'=>$post['alipay_name'],
                'status' => $post['status']
            );
            //icon新增
            $icon_info = array(
                'platform_id'=>$post['id'],
                'icon1_path'=>$post['icon1_path'],
                'icon1_name'=>$post['icon_name_1'],
                'icon1_url'=>$post['icon_url_1'],
                'icon2_name'=>$post['icon_name_2'],
                'icon2_path'=>$post['icon2_path'],
                'icon2_url'=>$post['icon_url_2'],
            );
            //判断为新增还是修改
            $sql = "select * from p_icon WHERE  platform_id = {$post['id']}";
            $info = $this->db->query($sql)->row_array();
            if($info){
                $this->icon_model->update($icon_info,array('platform_id'=>$post['id']));
            }else{
                $this->db->insert('p_icon',$icon_info);
            }
            $rs = $this->commercial_model->update($data,array('id'=>$post['id']));
            $id = $post['id'];
            if($rs){
                $this->_pagedata["tips"] = "保存成功";

                $data = array(
                    'refer'=>json_encode($post['refer']),
                    'common_pr'=>$post['common_pr'],
                    'error_msg'=>$post['error_msg'],
                    'error_url'=>$post['error_url'],
                    'use_yue'=>$post['use_yue'],
                    'group_code'=>$post['group_code'],
                );
                $data['platform_id'] = $post['id'];
                $data['config_ids'] = json_encode($post['config']);
                $data['last_update'] = date('Y-m-d H:i:s');
                $data['wechat_rate'] = $post['wechat_rate'];
                $data['alipay_rate'] = $post['alipay_rate'];
                $data['separate_account'] = $post['separate_account'];
                $data['separate_rate'] = $post['separate_rate'];
                if($post['config_id']){
                    $this->db->update('p_config_device',$data,array('id'=>$post['config_id']));
                }else{
                    $data['create_time'] = date('Y-m-d H:i:s');
                    $this->db->insert('p_config_device',$data);
                }

                //添加修改分账配置记录
                if ($old_is_separate != $post['is_separate'] || $old_separate_refer != json_encode($post['separate_refer']) || $old_separate_refer_percent != json_encode($refers) || $old_alipay_account != $post['alipay_account'] || $old_alipay_realname != $post['alipay_realname']){
                    $log_data = array(
                        'old_is_separate'=>$old_is_separate,
                        'old_separate_refer'=>$old_separate_refer,
                        'old_separate_refer_percent'=>$old_separate_refer_percent,
                        'old_alipay_account'=>$old_alipay_account,
                        'old_alipay_realname'=>$old_alipay_realname,
                        'is_separate'=>$post['is_separate'],
                        'separate_refer'=>json_encode($post['separate_refer']),
                        'separate_refer_percent'=>json_encode($refers),
                        'alipay_account'=>$post['alipay_account'],
                        'alipay_realname'=>$post['alipay_realname'],
                        'adminname'=>$this->operation_name,
                        'created_time'=>time(),
                    );
                    $this->db->insert('p_commercial_separate_log',$log_data);
                }

                $this->commercial_model->setCommInfo($id);
            }else{
                $this->_pagedata["tips"] = "保存失败";
            }
        }
        $commercialInfo = $this->commercial_model->dump(array('id'=>$id));
        $iconInfo = $this->icon_model->select_icon($id);
        $sql = "select * from p_config_device WHERE  platform_id = {$id}";
        $info = $this->db->query($sql)->row_array();

        if($info){
            $config_ids = json_decode($info['config_ids'],1);
            $this->_pagedata['config_id'] = $info['id'];

            $config = $info;
            $config['refer'] = json_decode($info['refer'],1);
        }
        $this->_pagedata['config_ids'] = $config_ids;
        if(! $config['common_pr'] ){
            $config['common_pr'] = BOX_API_URL.'/public/p.html?d=DEVICEID';
        }
        $sql = "select * from p_config";
        $configs = $this->db->query($sql)->result_array();
        $this->_pagedata['configs'] = array();

        foreach ($configs as $v){
            foreach ($config_ids as $c=>$cv){
                if(intval($v['id']) == intval($cv) ){
                     $v['checked'] = true;
                }
            }
            if($v['type'] == 'wechat'){
                $this->_pagedata['configs'][$v['type']]['rate'] = $info['wechat_rate'];
            }else{
                $this->_pagedata['configs'][$v['type']]['rate'] = $info['alipay_rate'];
            }

            $this->_pagedata['configs'][$v['type']][] = $v;
        }

        $this->_pagedata["info"] = $commercialInfo;
        $this->_pagedata["config_info"] = $config;
        $this->_pagedata['iconinfo'] = $iconInfo;
        $open_refer_check = array();
        foreach ($this->open_refer as $rk=> $rf){
            if(in_array($rk,$config['refer'])){
                $open_refer_check[$rk] = true;
            }else{
                $open_refer_check[$rk] = false;
            }
        }
        $separate_refer_check = array();
        $commercialInfo['separate_refer'] = json_decode($commercialInfo['separate_refer'],1);
        foreach ($this->open_refer as $rk=> $rf){
            if(in_array($rk,$commercialInfo['separate_refer'])){
                $separate_refer_check[$rk] = true;
            }else{
                $separate_refer_check[$rk] = false;
            }
        }
        $this->load->model('equipment_model');
        $this->_pagedata['qr_common_url'] = Equipment_model::QR_COMMON_URL;
        $this->_pagedata["groups"] = $this->groups;
        $this->_pagedata["open_refer"] = $this->open_refer;
        $this->_pagedata["open_refer_check"] = $open_refer_check;
        $this->_pagedata['separate_refer_check'] = $separate_refer_check;
        $this->_pagedata['separate_refer_percent'] = json_decode($commercialInfo['separate_refer_percent'],1);
        $this->_pagedata['img_http']   = $this->img_http;

        $this->page('commercial/commercialUpdate.html');
    }

    function commercialAdd(){
        if($this->input->post("submit")){
            $post = $this->input->post();
            $datas = array(
                'name'=>$post['name'],
                'short_name'=>$post['short_name'],
                'contacts'=>$post['contacts'],
                'phone'=>$post['phone'],
                'address'=>$post['address'],
                'need_deliver'=>$post['need_deliver'],
                'need_product'=>$post['need_product'],
                'ali_appid'=>$post['ali_appid'],
                'ali_secret'=>$post['ali_secret'],
                'pay_succ_tpl_id'=>$post['pay_succ_tpl_id'],
                'pay_fail_tpl_id'=>$post['pay_fail_tpl_id'],
                'refund_tpl_id'=>$post['refund_tpl_id'],
                'notify_tpl_id'=>$post['notify_tpl_id'],
                'pay_user_id'=>$post['pay_user_id'],
                'pay_cent'=>$post['pay_cent'],
                'wechat_appid'=>$post['wechat_appid'],
                'wechat_secret'=>$post['wechat_secret'],
                'wechat_mchid'=>$post['wechat_mchid'],
                'wechat_key'=>$post['wechat_key'],
                'wechat_planid'=>$post['wechat_planid'],
                'wechat_pay_succ_tpl_id'=>$post['wechat_pay_succ_tpl_id'],
                'wechat_pay_fail_tpl_id'=>$post['wechat_pay_fail_tpl_id'],
                'wechat_refund_tpl_id'=>$post['wechat_refund_tpl_id'],
                'wechat_notify_tpl_id'=>$post['wechat_notify_tpl_id'],
                'refer'=>json_encode($post['refer']),
                'error_msg'=>$post['error_msg'],
                'error_url'=>$post['error_url'],
                'use_yue'=>$post['use_yue'],
                'kf_tel' =>$post['kf_tel'],
                'msg_title'=>$post['msg_title'],
                'img_banner'=>$post['img_banner'],
                'qr_logo'=>$post['qr_logo'],
                'wechat_name'=>$post['wechat_name'],
                'alipay_name'=>$post['alipay_name'],
                'high_agent_id'=>$this->platform_id
            );
            $rs = $this->commercial_model->insert($datas);
            if($rs){
                //icon新增
                $icon_info = array(
                    'platform_id'=>$rs,
                    'icon1_path'=>$post['icon1_path'],
                    'icon1_name'=>$post['icon_name_1'],
                    'icon1_url'=>$post['icon_url_1'],
                    'icon2_name'=>$post['icon_name_2'],
                    'icon2_path'=>$post['icon2_path'],
                    'icon2_url'=>$post['icon_url_2'],
                );
                $this->db->insert('p_icon',$icon_info);
                $data = array(
                    'refer'=>json_encode($post['refer']),
                    'common_pr'=>$post['common_pr'],
                    'error_msg'=>$post['error_msg'],
                    'error_url'=>$post['error_url'],
                    'use_yue'=>$post['use_yue'],
                    'group_code'=>$post['group_code'],
                );
                $data['platform_id'] = $rs;
                $data['config_ids'] = json_encode($post['config']);
                $data['last_update'] = date('Y-m-d H:i:s');
                $data['create_time'] = date('Y-m-d H:i:s');
                $data['wechat_rate'] = $post['wechat_rate'];
                $data['alipay_rate'] = $post['alipay_rate'];
                $data['separate_account'] = $post['separate_account'];
                $data['separate_rate'] = $post['separate_rate'];
                $this->db->insert('p_config_device',$data);
                refresh_config_cache();
                //去PLATFORM平台添加该商户  并且创建配置
                unset($data['separate_account']);
                unset($data['separate_rate']);
                unset($datas['high_agent_id']);
                $platform_rs_id = $this->commercial_model->platform_insert($datas);
                $icon_info['platform_id'] = $platform_rs_id;
                $data['platform_id'] = $platform_rs_id;
                $this->p_db->insert('p_icon',$icon_info);
                $this->p_db->insert('p_config_device',$data);

                $this->db->set('platform_rs_id',$platform_rs_id);
                $this->db->where('id', $rs);  //agent里面的商户id
                $this->db->update('commercial');
                $this->commercial_model->setCommInfo($rs);
                $this->_pagedata["tips"] = "新增成功";
            }else{
                $this->_pagedata["tips"] = "新增失败";
            }
            redirect('commercial/commercialList');
        }
        $this->load->model('equipment_model');
        $this->_pagedata['qr_common_url'] = Equipment_model::QR_COMMON_URL;
        $this->_pagedata["open_refer"] = $this->open_refer;
        $this->_pagedata["groups"] = $this->groups;
        $sql = "select * from p_config";
        $configs = $this->db->query($sql)->result_array();
        $this->_pagedata['configs'] = array();
        foreach ($configs as $v){
            $this->_pagedata['configs'][$v['type']][] = $v;
        }
        $this->page('commercial/commercialAdd.html');
    }

    function ajaxGenSadmin(){   //demo  接口
        $id = $this->input->post('id');
        $rs = $this->commercial_model->dump(array('id'=>$id));
        if(!empty($rs)){
            $params = array(
                'timestamp'         => time() . '000',
                'source'    => 'platform',
                'name'        => 'box_'.mt_rand(100,999).date('Ymdhis'),
                'alisa' =>$rs['contacts'],
                'mobile' =>$rs['phone'],
                'platform_id'=>$rs['platform_rs_id']
            );
            $url = RBAC_URL."apiAdmin/addSuperOne";

            $params['sign'] = $this->create_platform_sign($params);

            $options['timeout'] = 100;
            $result = $this->http_curl->request($url, $params, 'POST', $options);
            if(json_decode($result['response'],1)['code']==200){
                //插入一条记录到s_admin供权限分配
                $data = array(
                    'name'        => $params['name'],
                    'pwd' => 'admin123456',
                    'groupid'=>0,
                    'grade'=>99,
                    'platform_id'=>$params['platform_id']
                );
                $this->load->model('admin_model');
                $this->admin_model->insertAdmin($data['name'], $data['pwd'], $data['alias'], $data['mobile'], '', $data['email'] ,$data['grade'],$data['platform_id']);
                $this->commercial_model->update(array('admin_name'=>$params['name']),array('id'=>$id));
                echo $result['response'];
            }
        }else{
            echo json_encode(array('code'=>300,'msg'=>'错误的商户编号'));
        }
        exit;
    }

    function ajaxResetPwd(){   //demo  接口
        $id = $this->input->post('id');
        $rs = $this->commercial_model->dump(array('id'=>$id));

        if(!empty($rs)){
            $pwd = md5('abc12345##!');
            $this->c_db = $this->load->database('citybox_master',true);
            $sql = " update s_admin set pwd = '{$pwd}' WHERE name = '{$rs['admin_name']}' and platform_id = '{$rs['platform_rs_id']}'";
            $rs = $this->c_db->query($sql);
            if($rs){
                echo json_encode(array('code'=>200,'msg'=>'重置成功'));
            }
        }else{
            echo json_encode(array('code'=>300,'msg'=>'错误的商户编号'));
        }
        exit;
    }
    
    function exportProduct(){   //demo  接口
        $this->load->model('product_model');
        $platform_id = $this->input->post('id');
        //获取商品列表
        $products = $this->product_model->getList();
        
        if(!empty($products)){
            $params = array(
                'timestamp'         => time() . '000',
                'source' => 'platform',
                'platform_id' => $platform_id,
                'products'=>$products
            );
            $url = RBAC_URL."apiProducts/exportProduct";
    
            $params['sign'] = $this->create_platform_sign($params);
    
            $options['timeout'] = 0;
            $result = $this->http_curl->request($url, $params, 'POST', $options);
            if(json_decode($result['response'],1)['code']==200){
                echo $result['response'];
            }
        }else{
            echo json_encode(array('code'=>300,'msg'=>'商品库为空！'));
        }
        exit;
    }
    
    function exportProduct2(){   //demo  接口
        $this->load->model('product_model');
        $platform_id = 1;
        //获取商品列表
        $where = array('id'=>2);
        $products = $this->product_model->getList($where);
    
        if(!empty($products)){
            $params = array(
                'timestamp'         => time() . '000',
                'source' => 'platform',
                'platform_id' => $platform_id,
                'products'=>$products
            );
            $url = RBAC_URL."apiProducts/exportProduct";
    
            $params['sign'] = $this->create_platform_sign($params);
    
            $options['timeout'] = 0;
            $result = $this->http_curl->request($url, $params, 'POST', $options);
            if(json_decode($result['response'],1)['code']==200){
                echo $result['response'];
            }
        }else{
            echo json_encode(array('code'=>300,'msg'=>'商品库为空！'));
        }
        exit;
    }
    

    //demo   刷新缓存的接口
    function test(){
        $id = $this->input->post('id');
        $id = 1;
        $params = array(
            'timestamp'         => time() . '000',
            'source'    => 'api',
            'id'        => $id,
        );
        $url = PLATFORM_HOST_URL."commercial/update_cache";

        $params['sign'] = $this->create_platform_host_sign($params);
        var_dump($params);

        $options['timeout'] = 100;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        var_dump($result);
        if(json_decode($result['response'],1)['code']==200){
            echo $result['response'];
        }

        exit;
    }
    
    function test2(){
        $params = array(
            'timestamp'         => time() . '000',
            'source' => 'platform',
            'platform_id' => 999,
            'products'=>array('1'=>1)
        );
        $url = "http://stagingcityboxadmin.fruitday.com/api/apiProducts/exportProduct";
        
        $params['sign'] = $this->create_platform_sign($params);
        
        $options['timeout'] = 0;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        var_dump($result);
        if(json_decode($result['response'],1)['code']==200){
            echo $result['response'];
        }
    }
    
    public function test3(){
        echo $this->redis->hGet($this->com_redis_pre.'7','status');
    }
    
    public function test4(){
        $this->redis->hSet($this->com_redis_pre.'7','status',1);
        echo 'success';
    }



    function test_get_cache(){
        echo $this->redis->hGet($this->com_redis_pre.'1','update_time');
    }

    function separate(){
        $this->page('commercial/separate.html');
    }
    function separate_table(){
        $sql = "select t1.*,t2.name as commercial_name from p_separate t1 left join p_commercial t2 on t2.id = t1.platform_id order by id desc";
        $rs = $this->db->query($sql)->result_array();
        foreach ($rs as $k=>&$v){
            if ($v['status'] == 1){
                $v['status_name'] = '已分账';
            } elseif ($v['status'] == 0){
                $v['status_name'] = '未分账';
            } elseif ($v['status'] == -1){
                $v['status_name'] = '分账失败';
            }
        }
        $result = array(
            'total' => count($rs),
            'rows' => $rs
        );
        echo json_encode($result);
    }

    /**
     * 删除icon
     */
    public function delete_icon(){
        $platform_id = $this->input->post('platform_id');
        $icon_id = $this->input->post('icon_id');
        $affect_row = $this->icon_model->delete_icon($platform_id,$icon_id);
        if($affect_row == 1){
            $this->showJson(array('status'=>'success', 'msg'=>'清除成功'));
        }else{
            $this->showJson(array('status'=>'error', 'msg'=>'清除失败'));
        }
    }


}
