<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Admin_setting extends MY_Controller
{
    public $workgroup = 'commercial';

    function __construct()
    {
        parent::__construct();
        $this->load->model("admin_setting_model");
        $this->load->model("commercial_model");
        $this->c_db = $this->load->database('citybox_master',true);
    }

    public function index()
    {
        $this->title = 'rbac后台权限设置';
        $this->page("admin_setting/index.html");
    }

    public function output($data)
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    }

    public function data()
    {
        $params = array_merge($_GET, $_POST);
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = isset($params['offset']) ? $params['offset'] : 0;
        $order_by = '';

        $where = [];

        if (isset($params['name'])) {
            if (preg_match('#^@([a-z._]+)(:+)([\s\S]+)$#', $params['name'], $m)) {
                $m[3] = trim($m[3]);
                if ($m[3]) {
                    if ($m[2] == ':') {
                        $where[$m[1]] = strpos($m[3], ',') ? array_map('trim', explode(',', $m[3])) : $m[3];
                    } else {
                        $where[$m[1] . ' like'] = "%" . $m[3] . "%";
                    }
                }
            } else {
                $where['name like'] = "%" . $params['name'] . "%";
            }
        }

        if (isset($params['sort'])) {
            $order_by = $params['sort'] . ' ' . $params['order'];
        }

        $total = $this->admin_setting_model->data(compact('where'), true);
        $rows = $this->admin_setting_model->data(compact('where', 'limit', 'offset', 'order_by'));

        $this->output(compact('total', 'rows'));
    }

    public function add($platform_id)
    {
        if (empty($platform_id))
        {
        echo '非法操作';
        die;
         }
        $commercial = $this->commercial_model->get_platform((int)$platform_id);
        if (empty($commercial)) {
            echo '商户不存在';
            die;
        }
        if(!$commercial['admin_name'])
        {
            echo '请先生成商户平台主账号';die;
        }
        $this->title = 'rbac后台权限设置: ' . $commercial['name'];
        $this->load->library('curl', null, 'http_curl');
        $params = array(
            'timestamp' => time() . '000',
            'source' => 'platform',
            'platform_id' => (int)$commercial['platform_rs_id']
        );

        $url = RBAC_URL . "apiAdminSetting/getSettingOpts";

        $params['sign'] = $this->create_platform_sign($params);
        $result = $this->http_curl->request($url, $params, 'POST');
        $opts = [];
        if ($result['response']) {
            $resp = json_decode($result['response'], true);
            if (json_last_error() === JSON_ERROR_NONE && $resp['code'] == 200) {
                $opts = $resp['data'];
            }
        }
        $this->_pagedata['opts'] = $opts;
        $this->_pagedata['platform_id'] = (int)$commercial['platform_rs_id'];
        $this->page('admin_setting/add.html');
    }

    public function save($platform_id)
    {
        $params = $this->input->post();
        $params = $params ? json_encode($params) : '';
        $menus = json_decode($params,true);
        $menus_string = implode(",",$menus['menu']);
        //获取主账号id
        $commercial = $this->commercial_model->get_platform_commercial((int)$platform_id);
        $admin_id = $this->admin_setting_model->get_admin_id($commercial['admin_name']);
        $this->load->library('curl', null, 'http_curl');
        $params = array(
            'timestamp' => time() . '000',
            'source' => 'platform',
            'setting' => $params,
            'platform_id' => (int)$platform_id
        );
        $url = RBAC_URL . "apiAdminSetting/save";

        $params['sign'] = $this->create_platform_sign($params);
        $result = $this->http_curl->request($url, $params, 'POST');
        if ($result['response']) {
            $resp = json_decode($result['response'], true);
            if (json_last_error() === JSON_ERROR_NONE && $resp['code'] == 200) {
                //同时为主账号更新该权限
                $data['flag'] = $menus_string;
                $is_exits = $this->admin_setting_model->is_exits($platform_id);
                $this->c_db->set_dbprefix('s_');
                if($is_exits)
                {
                    $this->c_db->update('group',$data,array('platform_id'=>$platform_id));
                    $id = $is_exits['id'];
                }else
                {
                    $data['name'] = '管理员';
                    $data['ctime'] = time();
                    $data['platform_id'] = $platform_id;
                    $this->c_db->insert('group', $data);
                    $id = $this->c_db->insert_id();
                }
                $admin_group['group_id'] = $id;
                $is_admin_exits = $this->admin_setting_model->is_admin_exits($admin_id);
                $this->c_db->set_dbprefix('cb_');
                if($is_admin_exits)
                {
                    $this->c_db->update('s_admin_group',$admin_group,array('admin_id'=>$admin_id));
                }else
                {
                    $admin_group['admin_id'] = $admin_id?$admin_id:9999;
                    $this->c_db->insert('s_admin_group', $admin_group);
                }
                $this->output(['status' => 'y', 'msg' => '保存成功']);
            }
        }
        $this->output(['status' => 'n', 'msg' => '保存失败', 'data' => $result]);
    }
}