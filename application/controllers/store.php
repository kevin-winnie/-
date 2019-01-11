<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Store extends MY_Controller
{
    public $workgroup = 'equipment';

    function __construct()
    {
        parent::__construct();
        $this->load->model("store_model");
    }

    public function index(){
        $this->load->model("commercial_model");
        $this->_pagedata['commercial'] = $this->commercial_model->get_all_platforms();
        $this->page("store/index.html");
    }

    public function output($data)
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);die;
    }

    public function store_data()
    {
        $params = array_merge($_GET, $_POST);
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = isset($params['offset']) ? $params['offset'] : 0;
        $order_by = 's.id desc';

        //$this->output(['status' => 'n', 'msg' => '无效参数']);

        $where = [];

        if (isset($params['name'])) {
            if (preg_match('#^[A-Z]\d+$#', $params['name'])) {
                $where['s.code like'] = $params['name'] . "%";
            } else {
                $where['s.name like'] = "%" . $params['name'] . "%";
            }
        }

        if (isset($params['platform_id'])) {
            $where['s.platform_id'] = $params['platform_id'];
        }

        if (isset($params['is_valid'])) {
            $where['s.is_valid'] = $params['is_valid'];
        }

        if (isset($params['sort'])) {
            $order_by = $params['sort'] . ' ' . $params['order'];
        }

        $total = $this->store_model->store_data(compact('where'), true);
        $rows = $this->store_model->store_data(compact('where', 'limit', 'offset', 'order_by'));

        $this->output(compact('total', 'rows'));
    }

    public function editable(){
        $params = $this->input->post();
        if (!isset($params['field']) || !isset($params['id']) || !isset($params['value'])) {
            $this->output(['status'=>'n', 'msg' => '参数错误']);
        };

        if($params['field'] == 'code'){
            $store = $this->db->get_where('store', ['id' => (int)$params['id']])->row_array();
            if($this->store_model->check_code($params['value'], $store['platform_id'])){
                $this->output(['status'=>'n', 'msg' => '编码已经存在']);
            }
        }

        $this->store_model->do_editable('store', $params);
        $this->output(['status'=>'y', 'msg' => '更新成功']);
    }

    public function add()
    {
        $this->load->model("commercial_model");
        $this->_pagedata['commercial'] = $this->commercial_model->get_all_platforms();
        $this->_pagedata['code'] = $this->store_model->get_code();
        $this->display('store/add.html');
    }

    public function do_add()
    {
        $params = $this->input->post();
        $params = array_map('trim', $params);
        if(empty($params['name'])){
            $this->output(['status'=>'n', 'msg' => '仓名称不能为空']);
        }
        if($this->store_model->check_name($params['name'], $params['platform_id'])){
            $this->output(['status'=>'n', 'msg' => '仓名称已经存在']);
        }
        if(empty($params['code'])){
            $this->output(['status'=>'n', 'msg' => '编码不能为空']);
        }

        if($this->store_model->check_code($params['code'], $params['platform_id'])){
            $this->output(['status'=>'n', 'msg' => '编码已经存在']);
        }
        if(empty($params['platform_id']) || $params['platform_id'] <= 0){
            $this->output(['status'=>'n', 'msg' => '请选择平台']);
        }
        $id = $this->store_model->do_add([
            'name' => $params['name'],
            'code' => $params['code'],
            'platform_id' => $params['platform_id'],
            'is_valid' => (int)$params['is_valid'],
        ]);
        if($id > 0){
            $this->output(['status'=>'y', 'msg' => '保存成功']);
        }
        $this->output(['status'=>'n', 'msg' => '保存失败']);
    }
}