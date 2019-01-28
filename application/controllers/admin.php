<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Admin extends MY_Controller {

    public $workgroup = 'admin';

    const LOCK_LIMIT_MAX = 5;
    const RESET_PWD = "123456##!";
    const SERVER_USER_PWD = "123456##_fday2015";

    private $stockNum = 20; //小于50显示库存警告

    function __construct() {
        parent::__construct();
        $this->load->model("Admin_model");
        $this->load->model('agent_model');
//        $this->load->model("admin_equipment_model");
    }

    function index() {
        $this->title = '欢迎来到代理商管理后台';
//      $this->_pagedata['list'] = $this->Admin_model->getLoginById ( $this->session->userdata('sess_admin_data') ["adminid"] );//最近十次登录查询
//      $this->_pagedata['list'] = $array;
        $this->page('admin/index.html');
    }

    function changepwd() {
        $this->title = '修改密码';
        $this->_pagedata ["tips"] = "";

        if ($this->input->post("submit")) {
            $old = $this->input->post("old");
            $new = $this->input->post("new");
            $newConfim = $this->input->post("newconfirm");
            $id = $this->session->userdata('sess_admin_data')["adminid"];
            if ($new != $newConfim) {
                $this->_pagedata["tips"] = "新密码确认失败";
            } else if (trim($old) == "" || trim($new) == "" || trim($newConfim) == "") {
                $this->_pagedata["tips"] = "填写不完整";
            } else {
                $tips = get_pwd_strength($new);
                if (!empty($tips)) {
                    $this->_pagedata["tips"] = $tips;
                } else {
                    if ($this->Admin_model->changePwd($id, $old, $new) != 0) {
                        $this->_pagedata["tips"] = "原密码错误";
                    } else {
                        $this->_pagedata["tips"] = "更新成功";
                    }
                }
            }
        }
        $this->page('admin/changepwd.html');
    }

    function login() {
        $name = trim(addslashes($this->input->post("name")));
        $pwd = addslashes($this->input->post("pwd"));
        $data['tips'] = "";
        if ($this->input->post("submit")) {
//            echo $name;
//            echo $pwd;
            if ($name && $pwd) {
                $admin = $this->Admin_model->getAdmin($name);
                if ($admin) {
                    if ($admin['lock_limit'] >= self::LOCK_LIMIT_MAX) {
                        $data['tips'] = '账户被冻结，请联系管理员！';
                        $this->load->view('admin/login', $data);
                    } else {
                        if ($admin['pwd'] == md5($pwd)) {
                            $this->Admin_model->updateLock($admin['id'], 0);
                            $sess_admin_data = array(
                                'adminid' => $admin['id'],
                                'adminname' => $admin['name'],
                                'adminalias' => empty($admin['alias']) ? $admin['name'] : $admin['alias'],
                                'adminflag' => explode(",", $admin['flag']),
                                'adminTimestamp' => time(),
                                'adminfirst'=> $admin['is_first'],
                                'is_s_admin'=>$admin['is_s_admin'],
                                'grade'=>$admin['grade'],
                                'platform_id'=>$admin['platform_id'],
                            );
                            $this->session->set_userdata('sess_admin_data', $sess_admin_data);

                            $requestIP = $this->input->ip_address();
                            $this->Admin_model->insertLogin($admin['id'], $requestIP);
                            $this->Admin_model->updateLoginTime($admin['id']);
                            redirect("/report/current");
                        } else {
                            $lock_limit = $admin['lock_limit'] + 1;
                            $lock_limit = $lock_limit >= 5 ? 5 : $lock_limit;
                            $this->Admin_model->updateLock($admin['id'], $lock_limit);
                            $data['tips'] = '输入用户名或密码有误！';
                            $this->load->view('admin/login', $data);
                        }
                    }
                } else {
                    $data['tips'] = '输入用户名或密码有误！';
                    $this->load->view('admin/login', $data);
                }
            } else {
                $data['tips'] = '请输入用户名或密码！';
                $this->load->view('admin/login', $data);
            }
        } else {
            $this->load->view('admin/login', $data);
        }
    }

    function upuser() {
        $id = $this->uri->segment(3);
        $this->currwork = 'admin/getuserlist';
        $this->title = '账号管理';
        $this->_pagedata["tips"] = "";
        $this->_pagedata["groupList"] = $this->Admin_model->getGroupList($this->platform_id);
        if ($this->input->post("submit")) {
            $alias = $this->input->post("alias");
            $groupid = $this->input->post("group");
            $id = $this->input->post("id");
            $stores = $this->input->post("store");
            $funcs = $this->input->post("func");
            $is_first = $this->input->post('is_first');
            $mobile = $this->input->post('mobile');
            $id_card = $this->input->post('id_card');
            $email = $this->input->post('email');
            $box_no = $this->input->post('box_no');
            $res = $this->Admin_model->upUser($id, $alias, $is_first, $mobile, $id_card, $email);
            if ($res) {
                if (!empty($groupid)) {
                    $this->Admin_model->delAdminGroup($id);
                    $this->Admin_model->inseerAdminGroup($id,$groupid);
                } else {
                    $this->Admin_model->delAdminGroup($id);
                }

                if (!empty($funcs)) {
                    $this->Admin_model->updateAdminFunc($id, $funcs);
                } else {
                    $this->Admin_model->delAdminFunc($id);
                }
                $this->_pagedata ["tips"] = "更新成功";
            }
        }
        //已有权限的设备列表
        $this->_pagedata ["id"] = $id;
        $this->_pagedata ["item"] = $this->Admin_model->getUser($id);
        $this->_pagedata ["groups"] = $this->Admin_model->getGroups($id);
        $this->page('admin/upuser.html');
    }

    function addserveruser(){
        $this->_pagedata["tips"] = "";

        if ($this->input->post("submit")) {
            $name = trim($this->input->post("name"));
            $names = explode(',', $name);
            if(empty($name)){
                $this->_pagedata["tips"] = "用户名不能为空";
            }else{
                $res = $this->Admin_model->insServerUser($names,self::SERVER_USER_PWD);
                if(!$res){
                    $this->_pagedata["tips"] = "请修改重复名称";
                }else{
                    $this->Admin_model->insGuanUser($names,self::SERVER_USER_PWD);
                    $this->_pagedata["tips"] = "新增成功";
                }
            }
        }
        $this->page('admin/addserveruser.html');
    }

    function adduser() {
        $this->title = '新增账号';
        $this->_pagedata["tips"] = "";
        $platfrom_id = $this->platform_id;
        $grouplist = $this->Admin_model->getGroupList_arr($platfrom_id);
        //所有下级代理商
        $Agentlist = $this->Admin_model->getAgentList($platfrom_id);
        $this->_pagedata["agentList"] = $Agentlist;
        $this->_pagedata["groupList"] = $grouplist;
        if ($this->input->post("submit")) {
            $name = $this->input->post("name");
            $alias = $this->input->post("alias");
            $mobile = $this->input->post("mobile");
            $id_card = $this->input->post("id_card");
            $email = $this->input->post("email");
            $pwd = $this->input->post("pwd");
            $pwdConfim = $this->input->post("pwdconfirm");
            $group = $this->input->post("group");
            $platfrom_id = $this->input->post("agent_platform");
            $stores = $this->input->post("store");
            $funcs = $this->input->post("func");


            if ($pwdConfim != $pwd) {
                $this->_pagedata ["tips"] = "两次密码输入不一致";
            } else if (trim($name) == "" || trim($pwd) == "" || trim($pwdConfim) == "" || empty($group)) {
                $this->_pagedata ["tips"] = "填写不完整";
            } else {
                $grade = 3;
                $admin_id = $this->Admin_model->insertAdmin($name, $pwd, $alias, $mobile, $id_card, $email,$grade,$platfrom_id);
                if ($admin_id > 0) {
//                    if (!empty($stores)) {
//                        $this->Admin_model->insertAdminStore($admin_id, $stores);
//                    }
//                    if (!empty($funcs)) {
//                        $this->Admin_model->insertAdminFunc($admin_id, $funcs);
//                    }
                    if (!empty($group)) {
                        $this->Admin_model->insertAdminGroup($admin_id, $group);
                    }
                    $this->_pagedata["tips"] = "新增成功";
                } else {
                    $this->_pagedata["tips"] = "用户名已存在";
                }
            }
        }

        $this->page('admin/adduser.html');
    }

    function addgroup() {
        $this->currwork = 'admin/getgrouplist';
        $this->title = '分组';
        $this->_pagedata["tips"] = "";
        $platform_id = $this->platform_id;
        if ($this->input->post("submit")) {
            $name = $this->input->post("name");
            if ($this->Admin_model->insertGroup($name,$platform_id) != 0) {
                $this->_pagedata["tips"] = "用户名已存在";
            } else {
                $this->_pagedata["tips"] = "新增成功";
            }
        }

        $this->page('admin/addgroup.html');
    }

    function addfunc() {
        $this->title = '功能';
        $this->_pagedata["tips"] = "";

        if ($this->input->post("submit")) {
            $name = $this->input->post("name");
            if ($this->Admin_model->insertFunc($name) != 0) {
                $this->splash('error', '添加失败');
            } else {
                $this->splash('succ', '添加成功');
            }
        }

        $this->display('admin/addfunc.html');
    }

    function deluser() {
        if ($this->input->get("aid")) {
            $this->Admin_model->delAdmin($this->input->get("aid"));
        }
        redirect("/admin/getuserlist");
    }

    function delgroup() {
        if ($this->input->get("gid")) {
            $this->Admin_model->delGroup($this->input->get("gid"));
        }
        redirect("/admin/getgrouplist");
    }

    function delfunc() {
        if ($this->input->get("id")) {
            $this->Admin_model->delFunc($this->input->get("id"));
        }
        redirect("/admin/getfunclist");
    }

    function getuserlist() {

        $platform_id = $this->platform_id;

        $search = $_POST;
        $search_group_id = -1;
        $search_is_lock = -1;
        $search_lock_limit = -1;
        $is_open = 0;


            $is_open = 1;

            if (isset($search['search_group_id'])) {
                if ($search['search_group_id'] !== '-1') {
                    $search_group_id = $search['search_group_id'];
                }
            } else {
                $search['search_group_id'] = "-1";
            }

            if (isset($search['search_lock_limit'])) {
                if ($search['search_lock_limit'] !== '-1') {
                    $search_lock_limit = $search['search_lock_limit'];
                }
            } else {
                $search['search_lock_limit'] = "-1";
            }

            if (isset($search['search_is_lock'])) {
                if ($search['search_is_lock'] !== '-1') {
                    $search_is_lock = $search['search_is_lock'];
                }
            } else {
                $search['search_is_lock'] = "-1";
            }

            //$name = trim($search['name'])?trim($search['name']):'';

            if (isset($search['name'])) {
                $name = trim($search['name']);
            } else {
                $search['name'] = '';
            }
            
            if (isset($search['alias'])) {
                $alias = trim($search['alias']);
            } else {
                $search['alias'] = '';
            }
            
            if (isset($search['mobile'])) {
                $mobile = trim($search['mobile']);
            } else {
                $search['mobile'] = '';
            }

            $this->title = '账号管理';
            $this->_pagedata['search'] = $search;
            $this->_pagedata['is_open'] = $is_open;
            $this->_pagedata['grouplist'] = $this->Admin_model->getGroupList($platform_id);
            $this->_pagedata ["list"] = $this->Admin_model->getUserList($search_group_id, $search_is_lock, $search_lock_limit,$name,$alias,$mobile,$platform_id);
            $this->page('admin/listuser.html');


    }

    function ajax_user() {
        $ids = $_POST['id'];
        if (empty($ids)) {
            return false;
        }
        $id = implode(',', $ids);

        $data['key'] = $_POST['key'];
        $data['val'] = $_POST['val'];

        $res = $this->Admin_model->update($id, $data);
        $ajaxReturn['code'] = !$res ? 0 : 1;
        $ajaxReturn['msg'] = !$res ? "更新失败，请重新尝试" : "更新成功";
        echo json_encode($ajaxReturn);
        exit;
    }

    function getgrouplist() {
        $platform_id = $this->platform_id;
        $this->title = '分组';
        $this->_pagedata ["list"] = $this->Admin_model->getGroupList($platform_id);
        $this->_pagedata ["agent_id"] = $this->platform_id;
        $this->page('admin/listgroup.html');
    }

    function getfunclist() {
        $this->title = '功能';
        $this->_pagedata ["list"] = $this->Admin_model->getFuncList();
        $this->page('admin/listfunc.html');
    }

    function getpermission() {
        $this->currwork = 'admin/getgrouplist';
        $this->title = '分组';
        $this->_pagedata["tips"] = "";

        //为商户主账号分配权限
        if($this->input->post("config"))
        {
            if ($this->input->post("submit")) {
                $flags = "";
                if ($this->input->post("check")) {
                    $flags = implode(",", $this->input->post("check"));
                }
                //判断是否为首次创建
                $admin_id =$this->input->post("admin_id");
                $platform_id =$this->input->post("platform_id");
                $is_exits = $this->Admin_model->master_group_flag($admin_id);
                if($is_exits)
                {
                    $this->Admin_model->update_agent_Flag($flags, $admin_id);
                    $tips = "更新成功";
                }else
                {
                    $flagData = [
                        'admin_id'=>$admin_id,
                        'flag'=>$flags,
                        'ctime'=>time(),
                        'platform_id'=>$platform_id,
                    ];
                    $this->Admin_model->insert_agent_Flag($flagData);
                    $tips = "分配成功";
                }
            }
            $this->rbac($platform_id,$tips);
        }

        if ($this->input->get("gid")) {
            $gid = $this->input->get("gid");
            $agent_id = $this->input->get("agent_id");
            $agent_rs = $this->agent_model->dump(array('id'=>$agent_id));
//            echo '<pre>';print_r($agent_rs);exit;
            if ($this->input->post("submit")) {
                $flags = "";
                if ($this->input->post("check")) {
                    $flags = implode(",", $this->input->post("check"));
                }
                $this->Admin_model->updateFlag($flags, $gid);
                $this->_pagedata["tips"] = "更新成功";
            }

            $modules = $this->function_class->getModulesXml("ModulesList");
            $options = $this->function_class->getModulesXml("OptionList");
            $modulesArr = "";
            foreach ($modules as $module) {
                $moduleArr = array();
                $moduleArr['nodeName'] = $module->nodeValue;
                foreach ($options as $option) {
                    $type = $option->getAttribute("type");
                    if ($type == $module->getAttribute("value")) {
                        $value = $option->getAttribute("value");
                        $name = $option->nodeValue;
                        $moduleArr['nodeValue'][] = array(
                            'name' => $name,
                            'value' => $value
                        );
                        //过滤非上海鲜动、海星宝的添加设备权限
                        if(!in_array($agent_rs['high_level'],[0,1]) && ($moduleArr['nodeName'] == '设备管理'))
                        {
                            unset($moduleArr['nodeValue'][0]);
                        }
                    }
                }
                $modulesArr[] = $moduleArr;
            }
            $this->_pagedata ["modulesArr"] = $modulesArr;
            $this->_pagedata ["gid"] = $gid;
            $this->_pagedata ["flag"] = $this->Admin_model->getFlag($gid)->flag;
        } else {
            redirect("/report/current");
        }

        $this->page('admin/permission.html');
    }

    function ajaxHandleGroup() {
        $ajaxReturn = array('code' => 0, 'msg' => '');
        $id = $this->input->post("id");
        if (empty($id)) {
            $ajaxReturn['msg'] = "非法请求!";
            echo json_encode($ajaxReturn);
            exit;
        }
        $ids = is_array($id) ? $id : array($id);
        $data['gid'] = $this->input->post("gid");
        if ($data['gid'] <= 0) {
            $ajaxReturn['msg'] = "非法请求!";
            echo json_encode($ajaxReturn);
            exit;
        }
        $res = $this->Admin_model->updateAdminGroup($ids, $data['gid']);
        $ajaxReturn['code'] = !$res ? 0 : 1;
        $ajaxReturn['msg'] = !$res ? "更新失败，请重新尝试" : "更新成功";
        echo json_encode($ajaxReturn);
        exit;
    }

    function ajaxResetPwd() {
        $ajaxReturn = array('code' => 0, 'msg' => '');
        $id = (int) $this->input->post("id");
        if (empty($id)) {
            $ajaxReturn['msg'] = "非法请求!";
            echo json_encode($ajaxReturn);
            exit;
        }
        $res = $this->Admin_model->updateAdminPwd($id, self::RESET_PWD);
        $ajaxReturn['code'] = !$res ? 0 : 1;
        $ajaxReturn['msg'] = !$res ? "更新失败，请重新尝试" : "更新成功";
        echo json_encode($ajaxReturn);
        exit;
    }

    function logout() {
        session_destroy();
        $this->session->sess_destroy();
        $this->load->view('admin/login');
    }

    function rbac($id = '',$tips)
    {
        if ($id){
            $this->_pagedata['id'] = $id;
        }
        if($tips)
        {
            $this->_pagedata['tips'] = $tips;
        }
        $modules = $this->function_class->getModulesXml_admin("ModulesList");
        $options = $this->function_class->getModulesXml_admin("OptionList");
        $modulesArr = "";

        foreach ($modules as $module) {
            $moduleArr = array();
            $moduleArr['nodeName'] = $module->nodeValue;
            foreach ($options as $option) {
                $type = $option->getAttribute("type");
                if ($type == $module->getAttribute("value")) {
                    $value = $option->getAttribute("value");
                    $name = $option->nodeValue;
                    $moduleArr['nodeValue'][] = array(
                        'name' => $name,
                        'value' => $value
                    );
                }
            }
            $modulesArr[] = $moduleArr;
        }

        //读取当前商户主账号的身份权限
        $admin = $this->Admin_model->get_master_admin($id);
        if(empty($admin))
        {
            $this->load->model('commercial_model');
            $where['high_agent_id'] = $this->platform_id;
            $this->_pagedata ["tips"] = '请先创建商户主账号';
            $this->_pagedata ["list"] = $this->commercial_model->getList("*", $where);
            $this->page('commercial/commercialList.html');exit;
        }
        $master_group_flag = $this->Admin_model->master_group_flag($admin['id']);
        if($master_group_flag)
        {
            $this->_pagedata ["flag"] = $master_group_flag['flag'];
        }
        $this->_pagedata ["modulesArr"] = $modulesArr;
        $this->_pagedata ["config"] = 1;
        $this->_pagedata ["admin_id"] = $admin['id'];
        $this->_pagedata ["platform_id"] = $admin['platform_id'];
        $this->page('admin/permission.html');

    }

}
