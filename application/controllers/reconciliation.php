<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Reconciliation extends MY_Controller
{

    public $box_type = array(
        'rfid-1' => '蚂蚁盒子RFID',
        'rfid-2' => '魔盒生产RFID',
        'scan-1' => '扫码',
        'vision-1'=>'视觉'
    );
    public $refer = array(
        'alipay' => '支付宝',
        'wechat' => '微信',
        'fruitday' => '天天果园',
        'gat'    => '关爱通',
        'sodexo' => '索迪斯',
        'sdy'    => '沙丁鱼',
        'jd'    => '京东',
    );
    function __construct() {
        parent::__construct();
        $this->load->model("order_model");
        $this->load->model("agent_model");
        $this->load->model('user_model');
        $this->load->model('card_model');
        $this->load->model("equipment_new_model");
        $this->load->model('commercial_model');
        $this->load->model('reconciliation_model');
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    public function index(){
        $Agent = $this->agent_model->get_own_agents($this->platform_id);
        $agent_level_list = $this->commercial_model->get_agent_level_list_pt($this->platform_id,1);
        $platform_list    = $this->commercial_model->get_agent_level_list_pt($this->platform_id,2);
        if($this->svip)
        {
            $agent_level_list = $this->commercial_model->get_agent_level_list($Agent,2);
            $platform_list = $this->commercial_model->get_agent_level_list($Agent,1);
        }

        $this->_pagedata['start_time'] = $this->input->get('uid')?'':date('Y-m-d 00:00:00');
        $this->_pagedata['end_time']   = $this->input->get('uid')?'':date('Y-m-d 23:59:59');
        $this->_pagedata['admin'] = $this->user_model->get_all_admin();
        $this->_pagedata['store_list'] = $this->equipment_new_model->get_store_list();
        $this->_pagedata['uid'] = $this->input->get('uid');
        $this->_pagedata['info']= $this->user_model->get_user_info($this->_pagedata['uid']);
        $this->_pagedata['platform_list']= $platform_list;
        $this->_pagedata['agent_level_list'] = $agent_level_list;
        $this->page('reconciliation/index.html');
    }

    public function order_detail($order_name){
        $this->c_db->from('order');
        $this->c_db->where(array('order_name'=>$order_name));
        $data['detail'] = $this->c_db->get()->row_array();

        $data['order_product_list'] = $this->order_model->get_order_product_new($order_name);
        $p_price = bcsub($data['detail']['good_money'], bcadd($data['detail']['discounted_money'], $data['detail']['card_money'], 2), 2);
        $data['order_product_list'] = $this->get_proportion($data['order_product_list'], $p_price, $data['detail']['modou'], $data['detail']['yue'], $data['detail']['money']);
        $data['order_pay'] = $this->order_model->get_order_pay($order_name);
        $order_discount_log = $this->order_model->get_order_discount_log($order_name);
        $tmp = '';
        foreach($order_discount_log['content'] as $k=>$v){
            $tmp .= '<p>'.$v['text'].':<span style="float:right">-¥'.bcadd($v['discount_money'], 0, 2).'</span></p>';
        }
        if($data['detail']['use_card']){
            $card_name = $this->card_model->get_card_info($data['detail']['use_card'], 'card_name');
            $tmp .= '<p>(<span style="color: red">优惠券</span>)'.$card_name.':<span style="float:right">-¥'.$data['detail']['card_money'].'</span></p>';
        }
        $tmp .= '<p>'.魔豆抵扣.':<span style="float:right">-¥'.$data['detail']['modou'].'</span></p>';
        $total_tmp = bcadd($data['detail']['modou'],bcadd($data['detail']['discounted_money'], $data['detail']['card_money'], 2),2);
        $tmp .= '<p>总优惠:<span style="float:right">-¥'.$total_tmp.'</span></p>';
        $data['detail']['discounted_money'] = $tmp;
        foreach ($data as $key => $value) {
            $this->Smarty->assign($key,$value);
        }
        $html = $this->Smarty->fetch('order/model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }

    public function order_label($order_name){
        $this->c_db->from('stock_log');
        $this->c_db->where(array('obj_no'=>$order_name,'type'=>'user'));
        $data['detail'] = $this->c_db->get()->row_array();

        $diff = json_decode($data['detail']['diff'],true);
        $data['detail']['add'] = join(',',$diff['add']);
        $data['detail']['del'] = join(',',$diff['del']);
        $data['detail']['same'] = join(',',$diff['same']);

        $this->Smarty->assign('detail',$data['detail']);
        $html = $this->Smarty->fetch('order/label.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }
    public function get_product(){
        $kw         = $_GET['matchInfo'];
        $matchCount = $_GET['matchCount']?$_GET['matchCount']:10;
        $where['platform_id'] = $this->platform_id;
        $this->c_db->from('product');
        $this->c_db->like('product_name', $kw);
        $this->c_db->limit($matchCount);
        $this->c_db->where($where);
        $list = $this->c_db->get()->result_array();
        $tmp = array();
        foreach($list as $k=>$v){
            $tmp[] = $v['id'].'|'.$v['product_name'];
        }
        $this->showJson($tmp);
    }

    public function array_is_empty($array){
        foreach($array as $k=>$v){
            if($v !== 0 && $v !== '' && $v !== false){
                return false;
            }
        }
        return true;
    }

    //对账单列表
    public function table(){
        //读取当前代理商下的所有对账列表
        $reconciliation_list = $this->reconciliation_model->get_list();
        if($this->svip)
        {
            $reconciliation_list = $this->reconciliation_model->get_list();
        }
        echo json_encode($reconciliation_list);
    }


    public function one_detail($box_no, $sale_date){
        if($sale_date){
            $where['o.order_time >='] = $sale_date.' 00:00:00';
            $where['o.order_time <'] = $sale_date.' 23:59:59';
        }
        if($box_no){
            $where['o.box_no'] = $box_no;
        }
        $where['op.pay_status'] = 1;
        $this->c_db->from('order o');
        $this->c_db->join('order_pay op', 'o.order_name=op.order_name');
        $this->c_db->where($where);
        $data['list'] = $this->c_db->get()->result_array();
        $data['box_no'] = $box_no;
        $data['sale_date'] = $sale_date;

        foreach ($data as $key => $value) {
            $this->Smarty->assign($key,$value);
        }
        $html = $this->Smarty->fetch('order/sale_model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }


    public function pay_api($order_name){
        $params['order_name'] = htmlspecialchars($order_name);
        $rs = $this->get_api_content( $params, '/api/order/pay_by_manual?order_name='.$order_name, 0);
        $rs = json_decode($rs, true);
        $this->showJson($rs);
    }

    //订单导出
    public function explore($list){
        $page       = $this->input->get('page');
        $store_list = $this->equipment_new_model->get_store_list_byCode();
        $equipment_list = $this->equipment_new_model->get_all_box_admin();//所有开启的盒子
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '用户')
            ->setCellValue('B1', '手机')
            ->setCellValue('C1', '补货仓')
            ->setCellValue('D1', '设备名称')
            ->setCellValue('E1', '订单编号')
            ->setCellValue('F1', '订单商品')
            ->setCellValue('G1', '单价')
            ->setCellValue('H1', '商品数量')
            ->setCellValue('I1', '支付方式')
            ->setCellValue('J1', '订单状态')
            ->setCellValue('K1', '下单时间')
            ->setCellValue('L1', '设备类型');
        $objPHPExcel->getActiveSheet()->setTitle('订单列表');
        $key = 2;
        $this->load->model('refer_model');
        $refer = $this->refer_model->all();
        foreach ($list as $k => $v) {
            $tmp = $this->user_model->get_user_info($v['uid']);
            $product_list = $this->order_model->get_order_product($v['order_name']);

            $pay = isset($refer[$v['refer']])?$refer[$v['refer']]:$v['refer'];

            $pay_type = "";
            if($v['order_status'] == self::ORDER_STATUS_DEFAULT){
                $pay_type =   "未支付";
            }elseif($v['order_status'] == self::ORDER_STATUS_CONFIRM){
                $pay_type =   "下单成功支付处理中";
            } elseif($v['order_status'] == self::ORDEY_STATUS_SUCC){
                $pay_type =   "已支付";
            }elseif($v['order_status'] == self::ORDER_STATUS_REFUND_APPLY){
                $pay_type =   "退款申请";
            }elseif($v['order_status'] == self::ORDER_STATUS_REFUND){
                $pay_type =   "退款完成";
            }elseif($v['order_status'] == self::ORDER_STATUS_REJECT){
                $pay_type =   "驳回申请";
            }
            $box_type_list = $this->box_type;
            $box_type = $box_type_list[$equipment_list[$v['box_no']]['type']];
            foreach($product_list as $kp=>$vp){
                $objPHPExcel->getActiveSheet()
                    ->setCellValue('A'.$key, $tmp['user_name'])
                    ->setCellValue('B'.$key, $tmp['mobile'])
                    ->setCellValue('C'.$key, $store_list[$equipment_list[$v['box_no']]['replenish_location']])
                    ->setCellValue('D'.$key, $equipment_list[$v['box_no']]['name'])
                    ->setCellValue('E'.$key, $v['order_name'])
                    ->setCellValue('F'.$key, $vp['product_name'])
                    ->setCellValue('G'.$key, $vp['price'])
                    ->setCellValue('H'.$key, $vp['qty'])
                    ->setCellValue('I'.$key, $pay)
                    ->setCellValue('J'.$key, $pay_type)
                    ->setCellValue('K'.$key, $v['order_time'])
                    ->setCellValue('L'.$key, $box_type);

                $key++;
            }
        }

        @set_time_limit(0);
        // Redirect output to a client’s web browser (Excel2007)
        $objPHPExcel->initHeader("订单导出列表{$page}");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }


    public function download_html($num){
        $limit = 5000;
        $page = ceil($num/$limit);
        $result = array();
        for($i=1;$i<=$page; $i++){
            $start = ($i-1)*$limit;
            $next = $i*$limit;
            $next = $next>$num?$num:$next;
            $result[$i]['text'] = '导出第'.$start.'-'.$next.'条订单';
            $result[$i]['url']  = '/order/table?is_explore=1&page='.$i.'&limit='.$limit.'&offset='.$start;
        }
        $this->Smarty->assign('list',$result);
        $html = $this->Smarty->fetch('order/download_model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }

}