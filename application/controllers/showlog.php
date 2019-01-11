<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Showlog extends MY_Controller
{
    public $workgroup = 'showlog';
    public $platform_list = array();
    function __construct() {
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master', TRUE);
        $this->load->model("showlog_model");
        $this->load->model("user_model");
        $this->load->model("commercial_model");
        $this->load->model('equipment_model');
        $this->load->model('equipment_new_model');
        $platform_list= $this->commercial_model->getList("*");
        foreach ($platform_list as $v ){
            $this->platform_list[$v['id']] = $v['name'];
        }
    }

    public function open_door(){
        $this->_pagedata['is_show_name'] = 1;
        $this->page('showlog/open_door.html');
    }

    public function pay_log(){
        $this->page('showlog/pay_log.html');
    }

    public function log_abnormal(){
        $where = array('status'=>1);
        $this->_pagedata['platform_list'] = $this->commercial_model->getList("*", $where);
        $this->page('showlog/log_abnormal.html');
    }

    public function log_stock(){
        $this->page('showlog/log_stock.html');
    }

    public function log_device_receive(){
        $this->page('showlog/log_device_msg.html');
    }

    public function log_request(){
        $this->page('showlog/log_request.html');
    }

    public function log_api(){
        $this->page('showlog/log_api.html');
    }
    public function device_status(){
        $this->page('showlog/device_status.html');
    }

    public function log_device_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $search_device_id     = $this->input->get('search_device_id');
        $search_param  = $this->input->get('search_param');
        $search_type = $this->input->get('search_type');
        $search_msg_id = $this->input->get('search_msg_id');
        $search_pay_time = $this->input->get('search_pay_time');


        $where = array();
        if($search_pay_time){
            $where['receive_time >'] = $search_pay_time;
        }else{
            $where['receive_time >'] = date("Y-m-d 00:00:00");
        }
        if($search_device_id){
            $where['device_id like'] = "%".$search_device_id."%";
        }
        if($search_param){
            $where['param like'] = "%".$search_param."%";
        }
        if($search_msg_id){
            $where['msg_id like'] = "%".$search_msg_id."%";
        }

        if($search_type){
            $where['msg_type'] = $search_type;
        }else{
//            $where['msg_type'] = 'open_door';
        }

        $this->c_db->from('receive_box_log');
        $this->c_db->where($where);
        $this->c_db->order_by('id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();
        foreach ($list as &$v){
            switch ($v['msg_type']){
                case 'box_info':
                    $v['msg_type'] = '设备信息';break;
                case 'stock':
                    $v['msg_type'] = '盘点';break;
                case 'open_door':
                    $v['msg_type'] = '开门';break;
                case 'close_door':
                    $v['msg_type'] = '关门';break;
                case 'heart':
                    $v['msg_type'] = '心跳';break;
                case 'power':
                    $v['msg_type'] = '电源';break;
                case 'over_time_close_door':
                    $v['msg_type'] = '超时自动关门';break;

                default:
                    $v['msg_type'] = '其他';
                    break;
            }
            switch ($v['status']){
                case 'close':
                    $v['status'] = '已响应';break;
                case 'ignore':
                    $v['status'] = '忽略';break;
                case 'wait':
                    $v['status'] = '待处理';break;
                default:
                    $v['status'] = '其他';
                    break;
            }
        }
        $this->c_db->select("id");
        $this->c_db->from('receive_box_log');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }

    public function log_stock_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $search_device_id     = $this->input->get('search_device_id');
        $search_old  = $this->input->get('search_old');
        $search_type = $this->input->get('search_type');
        $search_new = $this->input->get('search_new');
        $search_pay_time = $this->input->get('search_pay_time');


        $where = array();
        if($search_pay_time){
            $where['create >'] = $search_pay_time;
        }
        if($search_device_id){
            $where['device_id like'] = "%".$search_device_id."%";
        }
        if($search_new){
            $where['stock like'] = "%".$search_new."%";
        }
        if($search_old){
            $where['old like'] = "%".$search_old."%";
        }

        if($search_type){
            $where['type'] = $search_type;
        }
        $this->c_db->from('stock_log');
        $this->c_db->where($where);
        $this->c_db->order_by('id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();

        foreach ($list as &$v){
            switch ($v['type']){
                case 'user':
                    $v['type'] = '用户购买';break;
                case 'admin':
                    $v['type'] = '上下货';break;
                default:
                    $v['type'] = '其他';
                    break;
            }
        }
        $this->c_db->select("id");
        $this->c_db->from('stock_log');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }


    public function log_request_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $search_device_id     = $this->input->get('search_device_id');
        $search_param = $this->input->get('param');
        $search_type = $this->input->get('search_type');
        $search_resopne = $this->input->get('search_resopne');
        $search_pay_time = $this->input->get('search_pay_time');


        $where = array();
        if($search_pay_time){
            $where['req_time >'] = $search_pay_time;
        }
        if($search_device_id){
            $where['box_no like'] = "%".$search_device_id."%";
        }
        if($search_param){
            $where['req_body like'] = "%".$search_param."%";
        }
        if($search_resopne){
            $where['response like'] = "%".$search_resopne."%";
        }

        if($search_type){
            $where['req_type'] = $search_type;
        }

        $this->c_db->from('request_msg_log');
        $this->c_db->where($where);
        $this->c_db->order_by('id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();
        foreach ($list as &$v){
            switch ($v['req_type']){
                case 'device_info_url':
                    $v['req_type'] = '请求售货机信息';break;
                case 'stock_url':
                    $v['req_type'] = '请求盘点';break;
                case 'open_door_url':
                    $v['req_type'] = '请求开门';break;
                default:
                    $v['req_type'] = '其他';
                    break;
            }
        }
        $this->c_db->select("id");
        $this->c_db->from('request_msg_log');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }
    public function open_log_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $mobile     = $this->input->get('search_mobile');
        $open_time  = $this->input->get('search_open_time');
        $close_time = $this->input->get('search_close_time');
        $operation_id = $this->input->get('search_operation_id');
        $box_param['province'] = $this->input->get('search_province')?$this->input->get('search_province'):0;
        $box_param['city'] = $this->input->get('search_city');
        $box_param['area'] = $this->input->get('search_area');
        $box_param['address'] = $this->input->get('search_address');
        $box_param['name']    = $this->input->get('search_name');
        $box_param['equipment_id']    = $this->input->get('search_equipment_id');

        $search_box = $this->equipment_new_model->get_box_no($box_param, 'equipment_id');
//        $where['platform_id'] = $this->platform_id;
        $where = array();
        $user_id_arr = array();
        if($mobile){
            $this->load->model('user_model');
            $user_id_arr = $this->user_model->get_user_id_by_mobile($mobile);
            if(!empty($user_id_arr)){
                if(is_numeric($user_id_arr)){
                    $user_id_arr = array($user_id_arr);
                }
            }else{
                $user_id_arr = array(-1);
            }
        }
        if($open_time){
            $where['open_time >'] = $open_time;
        }
        if($close_time){
            $where['close_time <'] = $close_time;
        }
        if($operation_id){
            $where['operation_id'] = $operation_id;
        }
        if(empty($search_box)){
//            $where['box_no'] = '';
        }
        $this->c_db->from('log_open');
        $this->c_db->where($where);
        if(!empty($search_box)){
            $this->c_db->where_in('box_no', $search_box);
        }
        if(!empty($user_id_arr)){
            $this->c_db->where_in('uid', $user_id_arr);
        }
        $this->c_db->order_by('id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();
        if($_GET['is_explore'] == 1){
            return $this->explore($list);//共用筛选条件 导出
        }
        foreach ($list as $k => $v) {
            $tmp = $this->equipment_new_model->get_box_no(array('equipment_id'=>$v['box_no']), 'name');
            $list[$k]['box_no'] = '('.$tmp[0].')'.$v['box_no'];
            $list[$k]['mobile'] = $this->user_model->get_user_info($v['uid'], 'mobile', 'mobile');
            $list[$k]['operation'] = $v['operation_id']==1?'下单':'上下架';
            $list[$k]['platform'] =$this->platform_list[$v['platform_id']];
        }
        $this->c_db->from('log_open');
        $this->c_db->where($where);
        if(!empty($search_box)){
            $this->c_db->where_in('box_no', $search_box);
        }
        if(!empty($user_id_arr)){
            $this->c_db->where_in('uid', $user_id_arr);
        }
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }
    public function log_api_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $search_uri     = $this->input->get('search_uri');
        $search_param  = $this->input->get('search_param');
        $search_type = $this->input->get('search_type');
        $search_response = $this->input->get('search_response');
        $search_pay_time = $this->input->get('search_pay_time');


        $where = array();
        if($search_pay_time){
            $where['time >'] = strtotime($search_pay_time);
        }else{
            $where['time >'] = strtotime(date("Y-m-d 00:00:00"));
        }
        if($search_uri){
            $where['uri like'] = "%".$search_uri."%";
        }
        if($search_param){
            $where['params like'] = "%".$search_param."%";
        }
        if($search_response){
            $where['response like'] = "%".$search_response."%";
        }

        if($search_type){
            $where['method'] = $search_type;
        }


        $this->c_db->from('logs');
        $this->c_db->where($where);
        $this->c_db->order_by('id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();
        foreach ($list as &$v){
            $v['time']  = date("Y-m-d H:i:s",$v['time']);
        }
        $this->c_db->from('logs');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }

    //支付日志
    public function pay_log_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $mobile     = $this->input->get('search_mobile');
        $pay_time   = $this->input->get('search_pay_time');
        $pay_status = $this->input->get('search_pay_status');
        $pay_type = $this->input->get('search_pay_type');
        $order_name = $this->input->get('search_order_name');
        $search_trade_no= $this->input->get('search_trade_no');
        $pay_comment= $this->input->get('search_pay_comment');
        $pay_no     = $this->input->get('search_pay_no');

//        $where['o.platform_id'] = $this->platform_id;
        $where = array();
        if($mobile){
            $userinfo = $this->showlog_model->get_user_id_by_mobile($mobile);
            $where['o.uid'] = $userinfo['id'];
        }
        if($pay_time){
            $where['op.pay_time >'] = $pay_time;
        }
        if(isset($_GET['search_pay_status']) && $pay_status!=-1){
            $where['op.pay_status'] = $pay_status;
        }
        if(isset($_GET['search_pay_type']) && $pay_type!=-1){
            $where['op.pay_type'] = $pay_type;
        }
        if($order_name){
            $where['o.order_name'] = $order_name;
        }
        if($pay_comment){
            $where['op.pay_comment like'] = "%".$pay_comment."%";
        }
        if($search_trade_no){
            $where['op.trade_no like'] = "%".$search_trade_no."%";
        }
        if($pay_no){
            $where['op.pay_no'] = $pay_no;
        }
        $this->c_db->from('order_pay op');
        $this->c_db->join('order o' , 'o.order_name=op.order_name');
        $this->c_db->where($where);
        $this->c_db->order_by('op.id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();
        foreach ($list as $k => $v) {
            if($v['pay_status'] == 0){
                $list[$k]['pay_status'] = '待支付';
            }elseif($v['pay_status'] == 1){
                $list[$k]['pay_status'] = '支付成功';
            }elseif($v['pay_status'] == 2){
                $list[$k]['pay_status'] ='<a class="label label-danger" >支付失败</a>';
            }elseif($v['pay_status'] == 3){
                $list[$k]['pay_status'] = '部分支付';
            }elseif($v['pay_status'] == 4){
                $list[$k]['pay_status'] = '下单成功支付处理中';
            }
            if($v['pay_type'] == 1){
                $list[$k]['pay_type'] = '支付宝免密';
            }elseif($v['pay_type'] == 2){
                $list[$k]['pay_type'] = '微信免密';
            }elseif($v['pay_type'] == 3){
                $list[$k]['pay_type'] = '天天果园';
            }elseif($v['pay_type'] == 4){
                $list[$k]['pay_type'] = '支付宝手动';
            } elseif($v['pay_type'] == 5){
                $list[$k]['pay_type'] = '微信手动';
            }elseif($v['pay_type'] == 6){
                $list[$k]['pay_type'] = '关爱通免密';
            }elseif($v['pay_type'] == 7){
                $list[$k]['pay_type'] = '关爱通手动';
            }
            if($v['pay_status'] == 0){
                $list[$k]['opreate'] = '<button type="button" class="btn btn-danger" onclick="go_pay(\''.$v['order_name'].'\')">查询订单支付</button>';
            }else{
                $list[$k]['opreate'] = '';
            }
            $list[$k]['platform'] =$this->platform_list[$v['platform_id']];
        }
        $this->c_db->select('count(op.id) as total');
        $this->c_db->from('order_pay op');
        $this->c_db->join('order o' , 'o.order_name=op.order_name');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total['total'],
            'rows' => $list
        );
        echo json_encode($result);
    }


    /*
     * free 空闲 、
     * scan 已扫码、未开门
     * busy 门已打开，用户购物中
     * stock 用户已关门，等待推送盘点消息
     * free 接受到盘点消息、售货机置为空闲状态
     * */
    public function abnormal_log_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $name       = $this->input->get('search_name');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $log_type   = $this->input->get('search_log_type');
        $platform_id= $this->input->get('search_platform_id');
        $box_param['province'] = $this->input->get('search_province')?$this->input->get('search_province'):0;
        $box_param['city'] = $this->input->get('search_city');
        $box_param['area'] = $this->input->get('search_area');
        $box_param['address'] = $this->input->get('search_address');
        $box_param['name'] = $name;
        $box_param['equipment_id'] = $this->input->get('search_equipment_id');


        $box_param['type'] = $this->input->get('search_type');
        $box_param['platform_id']= $platform_id;
        $search_box = $this->equipment_new_model->get_box_no($box_param, 'equipment_id');
        $where = array();
        if($start_time){
            $where['addTime >'] = $start_time.' 00:00:00';
        }
        if($end_time){
            $where['addTime <'] = $end_time.' 23:59:59';
        }
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        if($log_type){
            $where['log_type'] = $log_type;
        }
//        if(empty($search_box)){
//            $where['box_no'] = '';
//        }
        $this->c_db->from('log_abnormal');
        $this->c_db->where($where);
        if(!empty($search_box)){
            $this->c_db->where_in('box_no', $search_box);
        }
        $this->c_db->order_by('id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();
        //$query_sql = $this->c_db->last_query();
        foreach ($list as $k => $v) {
            if($v['log_type'] == 1){
                $list[$k]['log_type'] = '商品增多';
            }elseif($v['log_type'] == 2){
                $list[$k]['log_type'] = '开关门状态异常';
            }elseif($v['log_type'] == 3){
                $list[$k]['log_type'] ='支付不成功';
            }elseif($v['log_type'] == 4){
                $list[$k]['log_type'] ='商品绑定异常';
            }elseif($v['log_type'] == 5){
                $list[$k]['log_type'] ='零售机心跳异常';
            }elseif($v['log_type'] == 6){
                $list[$k]['log_type'] ='盘点差异巨大';
            }elseif($v['log_type'] == 7) {
                $list[$k]['log_type'] = '消息重复推送';
            }elseif($v['log_type'] == 8){
                $list[$k]['log_type'] ='硬件异常告警';
            }elseif($v['log_type'] == 9){
                $list[$k]['log_type'] ='不稳定标签';
            }
            $tmp = json_decode($v['content'], true);
            if(is_array($tmp)){
                $tmp_c = '';
                foreach($tmp as $vt){
                    $tmp_c .= '商品:'.$vt['product_name']."多出".$vt['qty'].'份;';
                }
                $list[$k]['content_n'] = $tmp_c;
            }else{
                $list[$k]['content_n'] = str_replace('busy', '<span style="color: red;">门已打开，用户购物中</span>', $v['content']);
            }
            $tmp = $this->equipment_new_model->get_box_no(array('equipment_id'=>$v['box_no']), 'name');//盒子搜索
            $list[$k]['name']   = $tmp[0].'('.$v['box_no'].')';
            $list[$k]['platform'] =$this->platform_list[$v['platform_id']];
        }
        $this->c_db->from('log_abnormal');
        $this->c_db->where($where);
        if(!empty($search_box)){
            $this->c_db->where_in('box_no', $search_box);
        }
        $total = $this->c_db->get()->num_rows();

        $this->c_db->from('log_abnormal a');
        $this->c_db->select('count(distinct box_no) cou');
        $this->c_db->where($where);
        if(!empty($search_box)){
            $this->c_db->where_in('a.box_no', $search_box);
        }
        $total_eq = $this->c_db->get()->row()->cou;
        $total_log_type=[];
        if(!$log_type) {
            $this->c_db->from('log_abnormal a');
            //$this->db->select('count(a.id) coum,log_type');
            $this->c_db->select('count(distinct box_no) coum,log_type');
            $where1 = array();
            if ($start_time) {
                $where1['a.addTime >'] = $start_time . ' 00:00:00';
            }
            if ($end_time) {
                $where1['a.addTime <'] = $end_time . ' 23:59:59';
            }
            if($platform_id){
                $where1['platform_id'] = $platform_id;
            }
            $this->c_db->where($where1);
            if (!empty($search_box)) {
                $this->c_db->where_in('a.box_no', $search_box);
            }
            $this->c_db->group_by('a.log_type');
            $total_log_type = $this->c_db->get()->result_array();
        }
        $result = array(
            'total' => $total,      //总数量
            'total_eq'=>$total_eq,  //设备数量
            'rows' => $list,         //列表
            'log_type_list'=>$total_log_type
        );
        echo json_encode($result);
    }
    //导出下载
    public function abnormal_list_export()
    {
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $name       = $this->input->get('search_name');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $log_type   = $this->input->get('search_log_type');
        $platform_id= $this->input->get('search_platform_id');
        $isTotal = $this->input->get('isTotal');
        $box_param['province'] = $this->input->get('search_province')?$this->input->get('search_province'):0;
        $box_param['city'] = $this->input->get('search_city');
        $box_param['area'] = $this->input->get('search_area');
        $box_param['address'] = $this->input->get('search_address');
        $box_param['name'] = $name;
        $box_param['equipment_id'] = $this->input->get('search_equipment_id');
        $box_param['type'] = $this->input->get('search_type');
        $box_param['platform_id']= $platform_id;
        $search_box = $this->equipment_new_model->get_box_no($box_param, 'equipment_id');
        $where = array();
        if($start_time){
            $where['addTime >'] = $start_time.' 00:00:00';
        }
        if($end_time){
            $where['addTime <'] = $end_time.' 23:59:59';
        }
        if($log_type){
            $where['log_type'] = $log_type;
        }
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->from('log_abnormal');
        $this->c_db->where($where);
        if(!empty($search_box)){
            $this->c_db->where_in('box_no', $search_box);
        }
        $this->c_db->order_by('id desc');
        $params = [];
        $params['pagesize'] = 10000;
        if($isTotal){
            $total = $this->c_db->select('count(*) as cou')->get()->row()->cou;
            $start = 1;
            $list = [];
            while ($total > $start) {
                $list[] = [
                    'start' => $start,
                    'end' => $params['pagesize'] + $start - 1,
                ];
                $start += $params['pagesize'];
            }
            echo json_encode(['status' => 'y', 'data' => $list]);
            die;
        }else{
            $params['offset'] = !empty($offset) ? $offset - 1 : 0;
            $this->c_db->limit($params['pagesize'], $params['offset']);
            $list = $this->c_db->get()->result_array();
            foreach ($list as $k => $v) {
                if($v['log_type'] == 1){
                    $list[$k]['log_type'] = '商品增多';
                }elseif($v['log_type'] == 2){
                    $list[$k]['log_type'] = '开关门状态异常';
                }elseif($v['log_type'] == 3){
                    $list[$k]['log_type'] ='支付不成功';
                }elseif($v['log_type'] == 4){
                    $list[$k]['log_type'] ='商品绑定异常';
                }elseif($v['log_type'] == 5){
                    $list[$k]['log_type'] ='零售机心跳异常';
                }elseif($v['log_type'] == 6){
                    $list[$k]['log_type'] ='盘点差异巨大';
                }elseif($v['log_type'] == 7) {
                    $list[$k]['log_type'] = '消息重复推送';
                }elseif($v['log_type'] == 8){
                    $list[$k]['log_type'] ='硬件异常告警';
                }elseif($v['log_type'] == 9){
                    $list[$k]['log_type'] ='不稳定标签';
                }
                $tmp = json_decode($v['content'], true);
                if(is_array($tmp)){
                    $tmp_c = '';
                    foreach($tmp as $vt){
                        $tmp_c .= '商品:'.$vt['product_name']."多出".$vt['qty'].'份;';
                    }
                    $list[$k]['content_n'] = $tmp_c;
                }else{
                    $list[$k]['content_n'] = str_replace('busy', '<span style="color: red;">门已打开，用户购物中</span>', $v['content']);
                }
                $tmp = $this->equipment_new_model->get_box_no(array('equipment_id'=>$v['box_no']), 'name','address');//盒子搜索
                $list[$k]['name']   = $tmp[0].'('.$v['box_no'].')';
                $list[$k]['address']   = $tmp[1];
            }
            $this->load->library('csv');
            Csv::download('异常日志'.date('ymd').'.csv', $list, [
                'name' => '设备名称',
                'address' => '地址',
                'addTime' => '异常发生时间',
                'uid' => '用户id',
                'log_type' => '日志类型',
                'content_n' => '异常详情',
                'is_send_warn' => '是否发送告警'
            ]);
        }
    }
    public function device_status_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $search_device_id     = $this->input->get('search_device_id');
        $search_param = $this->input->get('search_param');
        $search_type = $this->input->get('search_type');
        $search_scene = $this->input->get('search_scene');
        $search_resopne = $this->input->get('search_response');
        $search_pay_time = $this->input->get('search_pay_time');


        $where = array();
        if($search_pay_time){
            $where['last_update >'] = $search_pay_time;
        }
        if($search_device_id){
            $where['box_id like'] = "%".$search_device_id."%";
        }
        if($search_param){
            $where['user_id'] = "".$search_param."";
        }
        if($search_resopne){
            $where['refer like'] = "%".$search_resopne."%";
        }

        if($search_type){
            $where['status'] = $search_type;
        }

        if($search_scene){
            $where['use_scene'] = $search_scene;
        }


        $this->c_db->from('box_status');
        $this->c_db->where($where);
        $this->c_db->order_by('id desc');
        $this->c_db->limit($limit,$offset);
        $list = $this->c_db->get()->result_array();
        foreach ($list as &$value){
            $value['heart_status'] = get_device_heart_status_by_redis($value['box_id']);
        }
        $this->c_db->select("id");
        $this->c_db->from('box_status');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }



    public function send_device_info($device_id){
        $params['box_id'] = htmlspecialchars($device_id);
        $rs = $this->get_api_content( $params, '/api/device/get_info?box_id='.$device_id, 0);

        if(strpos($rs,'timed out')){
            //请求超时
            $rs =  array('state'=>array('tips'=>'请求接口返回超时'));
        }else{
            $rs = json_decode(json_decode($rs));
        }
        $this->showJson($rs);
    }
    public function send_pandian($device_id){
        $params['box_id'] = htmlspecialchars($device_id);
        $rs = $this->get_api_content( $params, '/api/device/stock?box_id='.$device_id, 0);
        if(strpos($rs,'timed out')){
            //请求超时
            $rs =  array('state'=>array('tips'=>'请求接口返回超时'));
        }else{
            $rs = json_decode(json_decode($rs));
        }
        $this->showJson($rs);
    }
    public function send_update_status($device_id){
        $this->load->model('box_status_model');
        $set = array('status'=>'free','use_scene'=>'custom');
        $where = array('box_id'=>trim($device_id));
        $rs = $this->box_status_model->updateStatus($set,$where);
        $ret =  array('state'=>array('tips'=>'更新失败'));
        if($rs){
            $ret =  array('state'=>array('tips'=>'更新成功，刷新看看'));
        }
        $this->showJson($ret);
    }
    public function download_html($num){
        $limit = 5000;
        $page = ceil($num/$limit);
        $result = array();
        for($i=1;$i<=$page; $i++){
            $start = ($i-1)*$limit;
            $next = $i*$limit;
            $next = $next>$num?$num:$next;
            $result[$i]['text'] = '导出第'.$start.'-'.$next.'条日志';
            $result[$i]['url']  = '/showlog/open_log_table?is_explore=1&page='.$i.'&limit='.$limit.'&offset='.$start;
        }
        $this->Smarty->assign('list',$result);
        $html = $this->Smarty->fetch('order/download_model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }

    public function explore($list){
        $list = array_values($list);
        $page       = $this->input->get('page');
        $equipment_list = $this->equipment_new_model->get_all_box();//所有开启的盒子
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '用户id')
            ->setCellValue('B1', '用户名')
            ->setCellValue('C1', '手机')
            ->setCellValue('D1', '设备id')
            ->setCellValue('E1', '设备名称')
            ->setCellValue('F1', '开启时间')
            ->setCellValue('G1', '关闭时间')
            ->setCellValue('H1', '生成订单编号')
            ->setCellValue('I1', '开门方式')
            ->setCellValue('J1', '操作类型');
        $objPHPExcel->getActiveSheet()->setTitle('开门日志'.$page);
        $key = 2;
        foreach ($list as $k => $v) {
            $tmp = $this->user_model->get_user_info($v['uid']);
            $operation_id = $v['operation_id']==1?'下单':'上下架';
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$key, $v['uid'])
                ->setCellValue('B'.$key, $tmp['user_name'])
                ->setCellValue('C'.$key, $tmp['mobile'])
                ->setCellValue('D'.$key, $v['box_no'])
                ->setCellValue('E'.$key, $equipment_list[$v['box_no']]['name'])
                ->setCellValue('F'.$key, $v['open_time'])
                ->setCellValue('G'.$key, $v['close_time'])
                ->setCellValue('H'.$key, $v['order_name'])
                ->setCellValue('I'.$key, $v['refer'])
                ->setCellValue('J'.$key, $operation_id);
            $key++;
        }

        @set_time_limit(0);
        // Redirect output to a client’s web browser (Excel2007)
        $objPHPExcel->initHeader("开门日志{$page}");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    //es 里面查询
    public function access_log(){
        $this->page('showlog/access_log.html');
    }

    //es table
    public function access_log_table(){
        $params['uri']    = $this->input->get('search_uri')?$this->input->get('search_uri'):'';
        $params['params'] = $this->input->get('search_params')?$this->input->get('search_params'):'';
        $params['offset'] = $this->input->get('offset')?$this->input->get('offset'):0;
        $params['size']   = $this->input->get('limit')?$this->input->get('limit'):10;
        $params['start']  = $this->input->get('search_start')?$this->input->get('search_start'):'';
        $params['end']    = $this->input->get('search_end')?$this->input->get('search_end'):'';
        $params['response']= $this->input->get('search_response')?$this->input->get('search_response'):'';
        $rs = $this->get_api_content($params, 'api/es_client/get_access_log', 1);
        $rs = json_decode($rs, true);
        $list = array();
        foreach($rs['hits']['hits'] as $k=>$v){
            $list[$k] = $v['_source'];
            $list[$k]['response'] = json_encode(json_decode($v['_source']['response'], true), JSON_UNESCAPED_UNICODE);
        }
        $result = array(
            'total' => $rs['hits']['total']?$rs['hits']['total']:0,
            'rows'  => $list
        );
        echo json_encode($result, JSON_UNESCAPED_UNICODE);

    }
}