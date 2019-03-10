<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Reconciliation extends MY_Controller
{


    function __construct() {
        parent::__construct();
        $this->load->model("agent_model");
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
        $string_platform = $string_agent = array();
        if(!empty($platform_list))
        {
            foreach($platform_list as $key=>$val)
            {
                if(!$val['platform_rs_id'])
                {
                    unset($platform_list[$key]);
                }
            }
            $platform_array = array_column($platform_list,'id');
        }
        if(!empty($agent_level_list))
        {
            $agent_array = array_column($agent_level_list,'id');
        }
        //商户
        $reconciliation_list = $this->reconciliation_model->get_list($platform_array,1);
        //代理商
        $agent_list = $this->reconciliation_model->get_list($agent_array,0);
        $data_list = array_merge((array)$reconciliation_list,(array)$agent_list);
        foreach($data_list as $key=>$val)
        {
            if($val['type'] == 1)
            {
                $owns = $this->agent_model->get_own_agents($val['shou_agent_id']);
                $owns['name'] = '代理商--'.$owns['name'];
            }else
            {
                $owns = $this->commercial_model->get_own_commercial($val['shou_platform_id']);
                $owns['name'] = '商户--'.$owns['name'];
            }
            $data_list[$key]['agent_commer_name'] = $owns['name'];
            $data_list[$key]['alipay_rate'] = $val['alipay_rate']?$val['alipay_rate']:0;
            $data_list[$key]['wechat_rate'] = $val['wechat_rate']?$val['wechat_rate']:0;
            $data_list[$key]['separate_rate'] = $val['separate_rate']?$val['separate_rate']:'100%';
        }

        $total = count($data_list);

        $this->_pagedata['start_time'] = $this->input->get('uid')?'':date('Y-m-d 00:00:00');
        $this->_pagedata['end_time']   = $this->input->get('uid')?'':date('Y-m-d 23:59:59');
        $this->_pagedata['platform_list']= $platform_list;
        $this->_pagedata['list']= $data_list;
        $this->_pagedata['total']= $total;
        $this->_pagedata['show_out_money']= 1;
        $this->_pagedata['agent_level_list'] = $agent_level_list;
        $this->page('reconciliation/index.html');
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
        $search['type'] = $this->input->post('search_type') ?$this->input->post('search_type') : 0;
        $search['start_time'] = $this->input->post('search_start_time')?$this->input->post('search_start_time'):date('Y-m-d 0:00:00');
        $search['end_time'] = $this->input->post('search_end_time')?$this->input->post('search_end_time'):date('Y-m-d 23:59:59');
        $search['agent_id'] = $this->input->post('search_agent')?$this->input->post('search_agent'):-1;
        $search['platform_id'] = $this->input->post('search_platform')?$this->input->post('search_platform'):-1;

        $where['type'] = $search['type'];
        $where['start_time'] = $search['start_time'];
        $where['end_time'] = $search['end_time'];
        if($search['agent_id'] >0)
        {
            $where['agent_id'] = $search['agent_id'];
        }else
        {
            $where['agent_id'] = $this->platform_id;
        }
        if($search['platform_id'] >0)
        {
            $where['platform_id'] = $search['platform_id'];
        }
        $agent_level_list = $this->commercial_model->get_agent_level_list_pt($this->platform_id,1);
        $platform_list    = $this->commercial_model->get_agent_level_list_pt($this->platform_id,2);
        if($this->svip)
        {
            //超级代理商级别
            $Agent = $this->agent_model->get_own_agents($this->platform_id);
            $agent_level_list = $this->commercial_model->get_agent_level_list($Agent,2);
            $platform_list = $this->commercial_model->get_agent_level_list($Agent,1);
        }
        $string_platform = $string_agent = array();
        if(!empty($platform_list))
        {
            foreach($platform_list as $key=>$val)
            {
                if(!$val['platform_rs_id'])
                {
                    unset($platform_list[$key]);
                }
            }
            $platform_array = array_column($platform_list,'id');
        }
        if(!empty($agent_level_list))
        {
            $agent_array = array_column($agent_level_list,'id');
        }
        $data_list = $this->reconciliation_model->get_search_reconLists($where,$platform_array,$agent_array);
        foreach($data_list as $key=>$val)
        {
            if($val['shou_agent_id'])
            {
                $owns = $this->agent_model->get_own_agents($val['shou_agent_id']);
                $owns['name'] = '代理商--'.$owns['name'];
            }else
            {
                $owns = $this->commercial_model->get_own_commercial($val['shou_platform_id']);
                $owns['name'] = '商户--'.$owns['name'];
            }
            $dakuan = $this->agent_model->get_own_agents($val['to_where_id']);
            $data_list[$key]['shoukuan'] = $owns['name'];
            $data_list[$key]['dakuan'] = $dakuan['name'];
            $data_list[$key]['alipay_rate'] = $val['alipay_rate']?$val['alipay_rate']:0;
            $data_list[$key]['wechat_rate'] = $val['wechat_rate']?$val['wechat_rate']:0;
            $data_list[$key]['separate_rate'] = $val['separate_rate']?$val['separate_rate']:'100%';
        }
        $this->_pagedata['start_time'] = $search['start_time']?$search['start_time']:date('Y-m-d 0:00:00');
        $this->_pagedata['end_time']   = $search['end_time']?$search['end_time']:date('Y-m-d 23:59:59');
        $this->_pagedata['platform_list']= $platform_list;
        $this->_pagedata['list']= $data_list;
        $this->_pagedata['show_type']= $search['type'];
        $this->_pagedata['search']= $search;
        $this->_pagedata['agent_level_list'] = $agent_level_list;
        $this->page('reconciliation/index.html');
    }



    //订单导出
    public function eq_table(){
        echo '<pre>';print_r($_GET);exit;
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

    /**
     * 切换出入账
     */
    public function change_recon()
    {
        $type_id = $this->input->post('type_id');
        $agent_level_list = $this->commercial_model->get_agent_level_list_pt($this->platform_id,1);
        $platform_list    = $this->commercial_model->get_agent_level_list_pt($this->platform_id,2);
        if($this->svip)
        {
            //超级代理商级别
            $Agent = $this->agent_model->get_own_agents($this->platform_id);
            $agent_level_list = $this->commercial_model->get_agent_level_list($Agent,2);
            $platform_list = $this->commercial_model->get_agent_level_list($Agent,1);
        }
        //出账
        $data['agent_level_list'] = $agent_level_list;
        $data['platform_list'] = $platform_list;
        $this->showJson(['status'=>'success','data'=>$data,'type'=>$type_id]);
    }

}