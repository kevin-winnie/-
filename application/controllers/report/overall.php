<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/7/26
 * Time: 下午3:00
 */

class Overall extends MY_Controller
{

    public $workgroup = 'report';
    const OVERALL_EQ_DATA_KEY = 'p_overall_eq_key4:';
    const OVERALL_P_DATA_KEY = 'p_overall_product_key2:';
    public $platform_id = 0;

    const CACHE_TIME = 86400;

    function __construct()
    {
        parent::__construct();
        $this->load->model('order_model');
        $this->load->model('log_open_model');
        $this->load->model('equipment_model');
        $this->load->model('bi_overall_model');
        $this->load->model('product_model');
        $this->load->model('commercial_model');
        $this->load->model('equipment_stock_model');
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
        $this->platform_id = $this->input->get('platform_id')?$this->input->get('platform_id'):-1;
        $this->agent_id = $this->input->get('agent_id')?$this->input->get('agent_id'):1;
    }

    public function index(){
        $agent_id = $this->agent_id;
        $agent_level_list = $this->commercial_model->get_agent_level_list_pt($this->platform_id,1);
        $platform_list    = $this->commercial_model->get_agent_level_list_pt($this->platform_id,2);
        if($this->svip)
        {
            $this->_pagedata['is_svip'] = 1;
            //代理商级别
            $Agent = $this->agent_model->get_own_agents($this->platform_id);
            $agent_level_list = $this->commercial_model->get_agent_level_list($Agent,2);
            $platform_list = $this->commercial_model->get_agent_level_list($Agent,1);
        }
        $this->_pagedata['agent_level_list'] = $agent_level_list;
        if($this->platform_id>0)
        {
            $agent_id = -1;
        }
        $this->_pagedata['agent_id']  = $agent_id;
        $this->_pagedata['platform_id']  = $this->platform_id;
        $this->_pagedata['platform_list']= $platform_list;
        $this->_pagedata['pd'] = date('Y-m-d', strtotime('-1 days'));//默认按日搜索数据
        $this->_pagedata['s_type'] = 0;//0:按天， 1:按周, 2:按月
        $this->page('report/overall/index.html');
    }


    function ajax_data(){
        $pd     = $this->input->post('pd');//按日搜索数据
        $s_type = $this->input->post('s_type');//0:按天， 1:按周, 2:按月
        $this->platform_id = $this->input->post('platform_id')?$this->input->post('platform_id'):-1;
        $commercial_array = $this->check_is_agent();
        if(!$pd && !$s_type){
            $this->showJson(array('status'=>'error', 'msg'=>'参数不全'));
        }
        $result['status'] = 'success';
        if($s_type==0){//按天
            $result['cu_data'] = $cu_data = $this->bi_overall_model->get_day_data($pd, $this->platform_id,$commercial_array);
            $last_day = date('Y-m-d', strtotime($pd) - 86400); //上一日
            $tong_day = date('Y-m-d', strtotime($pd) - 86400*7);//上周同期

            $last_data = $this->bi_overall_model->get_day_data($last_day, $this->platform_id,$commercial_array);
            $tong_data = $this->bi_overall_model->get_day_data($tong_day, $this->platform_id,$commercial_array);
            foreach($result['cu_data'] as $k=>$v){//如果分母是0，分子大于0 则直接100%
                $result['last_data'][$k] =    (intval($last_data[$k]) == 0 && $v>0)?100:bcdiv(($v-$last_data[$k]), $last_data[$k], 4)*100;//上一日
                $result['tong_data'][$k] =    (intval($tong_data[$k]) == 0 && $v>0)?100:bcdiv(($v-$tong_data[$k]), $tong_data[$k], 4)*100;//上周同期
            }
            $result['last_msg'] = '较前一日：';
            $result['tong_msg'] = '较上周同期：';

            //获取前30天的数据
            $param = array();
            for($i=21; $i>=0; $i--){
                $t_day = date('Y-m-d', strtotime($pd) - 86400*$i); //倒推30天数据
                $param[date('m-d', strtotime($t_day))] = $this->bi_overall_model->get_day_data($t_day, $this->platform_id,$commercial_array);
            }

        }elseif($s_type==1){//按周
            $result['cu_data'] = $cu_data = $this->bi_overall_model->get_week_data($pd, $this->platform_id,$commercial_array);
            $tmp = str_replace('周', '', $pd);
            $tmp = explode('第', $tmp);
            $tmp[1] = intval($tmp[1]);
            $year_week = $this->bi_overall_model->get_week($tmp[0]);
            $start_date = $year_week[$tmp[1]][0];
            $last_week = date('Y第W周', strtotime($start_date) - 86400*7); //上一周
            $last_data = $this->bi_overall_model->get_week_data($last_week, $this->platform_id,$commercial_array);


            $tong_week = intval($tmp[0]-1).'第'.$tmp[1].'周';//去年同期
            $tong_data = $this->bi_overall_model->get_week_data($tong_week, $this->platform_id,$commercial_array);

            foreach($result['cu_data'] as $k=>$v){//如果分母是0，分子大于0 则直接100%
                $result['last_data'][$k] =    (intval($last_data[$k]) == 0 && $v>0)?100:bcdiv(($v-$last_data[$k]), $last_data[$k], 4)*100;//上一日
                $result['tong_data'][$k] =    (intval($tong_data[$k]) == 0 && $v>0)?100:bcdiv(($v-$tong_data[$k]), $tong_data[$k], 4)*100;//上周同期
            }
            $result['last_msg'] = '较前一周：';
            $result['tong_msg'] = '较去年同期：';

            //获取前30天的数据
            $param = array();
            for($i=11; $i>=0; $i--){
                $t_week = date('Y第W周', strtotime($start_date) - 86400*7*$i); //倒推12周数据
                $param[$t_week] = $this->bi_overall_model->get_week_data($t_week, $this->platform_id,$commercial_array);
            }

        }elseif($s_type==2){

            $result['cu_data'] = $cu_data = $this->bi_overall_model->get_month_data($pd, $this->platform_id,$commercial_array);

            $last_month = date('Y-m', strtotime("$pd -1 months")); //上一月
            $last_data = $this->bi_overall_model->get_month_data($last_month, $this->platform_id,$commercial_array);


            $tong_month = date('Y-m', strtotime("$pd -12 months")); //去年同期
            $tong_data = $this->bi_overall_model->get_month_data($tong_month, $this->platform_id,$commercial_array);

            foreach($result['cu_data'] as $k=>$v){//如果分母是0，分子大于0 则直接100%
                $result['last_data'][$k] =    (intval($last_data[$k]) == 0 && $v>0)?100:bcdiv(($v-$last_data[$k]), $last_data[$k], 4)*100;//上一日
                $result['tong_data'][$k] =    (intval($tong_data[$k]) == 0 && $v>0)?100:bcdiv(($v-$tong_data[$k]), $tong_data[$k], 4)*100;//上周同期
            }
            $result['last_msg'] = '较前一月：';
            $result['tong_msg'] = '较去年同期：';

            //获取前30天的数据
            $param = array();
            for($i=11; $i>=0; $i--){
                $t_month = date('Y-m', strtotime("$pd -$i months")); //上一月; //倒推12月数据
                $param[$t_month] = $this->bi_overall_model->get_month_data($t_month, $this->platform_id,$commercial_array);
            }


        }else{
            $this->showJson(array('status'=>'error', 'msg'=>'暂不支持'));
        }

        $result['x_value'] = array_keys($param);

        foreach($param as $k=>$v){
            $y_order_money[$k] = $v['order_money'];
            $y_order_num[$k] = $v['order_num'];
            $y_order_user_avg[$k] = $v['order_user_avg'];
            $y_pay_avg[$k] = $v['pay_avg'];
            $y_open_num[$k] = $v['open_num'];
            $y_refund_money[$k] = $v['refund_money'];
            $y_refund_avg[$k] = $v['refund_avg'];
        }

        $result['y_order_money']['name'] = '支付金额';
        $result['y_order_money']['type'] = 'line';
        $result['y_order_money']['smooth'] = 'true';//lineStyle.normal.color
        $result['y_order_money']['lineStyle']['normal']['color'] = 'green';//线条颜色
        $result['y_order_money']['itemStyle']['normal']['color'] = 'green';//点颜色
        $result['y_order_money']['data'] = array_values($y_order_money);

        $result['y_order_num']['name'] = '支付订单数';
        $result['y_order_num']['type'] = 'line';
        $result['y_order_num']['smooth'] = 'true';
        $result['y_order_num']['data'] = array_values($y_order_num);

        $result['y_order_user_avg']['name'] = '客单价';
        $result['y_order_user_avg']['type'] = 'line';
        $result['y_order_user_avg']['smooth'] = 'true';
        $result['y_order_user_avg']['lineStyle']['normal']['color'] = 'blue';//线条颜色
        $result['y_order_user_avg']['itemStyle']['normal']['color'] = 'blue';//点颜色
        $result['y_order_user_avg']['data'] = array_values($y_order_user_avg);

        $result['y_pay_avg']['name'] = '支付转化率';
        $result['y_pay_avg']['type'] = 'line';
        $result['y_pay_avg']['smooth'] = 'true';
        $result['y_pay_avg']['lineStyle']['normal']['color'] = '#555555';//线条颜色
        $result['y_pay_avg']['itemStyle']['normal']['color'] = '#555555';//点颜色
        $result['y_pay_avg']['data'] = array_values($y_pay_avg);

        $result['y_open_num']['name'] = '开门次数';
        $result['y_open_num']['type'] = 'line';
        $result['y_open_num']['smooth'] = 'true';
        $result['y_open_num']['lineStyle']['normal']['color'] = '#996633';//线条颜色
        $result['y_open_num']['itemStyle']['normal']['color'] = '#996633';//点颜色
        $result['y_open_num']['data'] = array_values($y_open_num);

        $result['y_refund_money']['name'] = '实退金额';
        $result['y_refund_money']['type'] = 'line';
        $result['y_refund_money']['smooth'] = 'true';
        $result['y_refund_money']['lineStyle']['normal']['color'] = '#FFCC00';//线条颜色
        $result['y_refund_money']['itemStyle']['normal']['color'] = '#FFCC00';//点颜色
        $result['y_refund_money']['data'] = array_values($y_refund_money);

        $result['y_refund_avg']['name'] = '退款率';
        $result['y_refund_avg']['type'] = 'line';
        $result['y_refund_avg']['smooth'] = 'true';
        $result['y_refund_avg']['lineStyle']['normal']['color'] = '#2F4F4F';//线条颜色
        $result['y_refund_avg']['itemStyle']['normal']['color'] = '#2F4F4F';//点颜色
        $result['y_refund_avg']['data'] = array_values($y_refund_avg);

        $this->showJson($result);
    }
    function check_is_agent()
    {
        //代理商模式数据
        $agent_id = $this->agent_id;
        if($agent_id >0 && $this->platform_id == -1)
        {
            $commercial_list = $this->commercial_model->get_commercial_list_by_agent($agent_id);
            if(!empty($commercial_list))
            {
                foreach($commercial_list as $key=>$val)
                {
                    if(!$val['platform_rs_id'])
                    {
                        unset($commercial_list[$key]);
                    }
                }
            }
            $commercial_array = array_column($commercial_list,'platform_rs_id');
        }
        return $commercial_array;
    }

    public function eq_table(){
        session_write_close();
        $eq_name_like = $this->input->get('eq_name_like')?$this->input->get('eq_name_like'):'';
        $sort       = $this->input->get('sort')?$this->input->get('sort'):'sale_money';
        $order      = $this->input->get('order')?$this->input->get('order'):'desc';
        $limit      = $this->input->get('limit')?$this->input->get('limit'):30;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $start_date = $this->input->get('start_date')?$this->input->get('start_date'):date('Y-m-d', strtotime('-1 days'));
        $end_date   = $this->input->get('end_date')?$this->input->get('end_date'):date('Y-m-d', strtotime('-1 days'));
        $key = self::OVERALL_EQ_DATA_KEY.$start_date.':'.$end_date.'_platform_id:'.$this->platform_id;
        $commercial_array = $this->check_is_agent();
        if(!empty($commercial_array))
        {
            $key = self::OVERALL_EQ_DATA_KEY.$start_date.':'.$end_date.'_agent_id:'.$this->agent_id;
        }
        $tmp = $this->redis->get($key);
        if($tmp){
            $list = json_decode($tmp, true);
        }else{
            $list = $this->bi_overall_model->get_date_data($start_date, $end_date, $this->platform_id,$commercial_array);
            $this->redis->set($key, json_encode($list), self::CACHE_TIME);
        }
        $list = $this->array_sort($list, $sort, $order);
        $list = array_values($list);
        foreach($list as $k=>$v){
            if($eq_name_like!=''){//对设备名 进行模糊搜
                if(strpos($v['eq_name_t'], $eq_name_like) === false){
                    unset($list[$k]);
                    continue;
                }
            }
            $list[$k]['key'] = $k+1;
            $list[$k]['order_avg']       = $v['order_avg'].'%';
            $list[$k]['refund_avg']      = $v['refund_avg'].'%';
        }
        if(isset($_GET['is_explore']) && $_GET['is_explore'] == 1){
            return $this->explore_eq($list, $start_date.'至'.$end_date);
        }
        $total = count($list);
        $list = array_slice($list, $offset, $limit);
        $result = array(
            'total' => $total,
            'rows'  => $list,
            'start_date'=>$start_date,
            'end_date'=>$end_date
        );
        echo json_encode($result);
    }

    public function product_table(){
        session_write_close();
        $sort       = $this->input->get('sort')?$this->input->get('sort'):'sale_money';
        $order      = $this->input->get('order')?$this->input->get('order'):'desc';
        $limit      = $this->input->get('limit')?$this->input->get('limit'):30;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $start_date = $this->input->get('start_date')?$this->input->get('start_date'):date('Y-m-d', strtotime('-1 days'));
        $end_date   = $this->input->get('end_date')?$this->input->get('end_date'):date('Y-m-d', strtotime('-1 days'));
        $key = self::OVERALL_P_DATA_KEY.$start_date.':'.$end_date.'_platform_id:'.$this->platform_id;
        $commercial_array = $this->check_is_agent();
        if(!empty($commercial_array))
        {
            $key = self::OVERALL_P_DATA_KEY.$start_date.'_agent_id:'.$end_date.':'.$this->agent_id;
        }
        $tmp = $this->redis->get($key);
        if($tmp){
            $list = json_decode($tmp, true);
        }else{
            $list = $this->bi_overall_model->get_date_p_data($start_date, $end_date, $this->platform_id,$commercial_array);
            $product_ids = array();
            foreach($list as $k=>$v){
                $product_ids[] = $v['product_id'];
            }
            $product_tmp = $this->product_model->get_product_list($product_ids);
            foreach($list as $k=>$v){
                $list[$k]['product_name']   = $product_tmp[$v['product_id']]['product_name'];
                $list[$k]['price']          = $product_tmp[$v['product_id']]['price'];
                $list[$k]['product_name_p'] = $product_tmp[$v['product_id']]['product_name'];
                $list[$k]['class_name']     = $product_tmp[$v['product_id']]['class_name'];
                $list[$k]['class_parent']   = $product_tmp[$v['product_id']]['class_parent'];
                $list[$k]['purchase_price'] = $product_tmp[$v['product_id']]['purchase_price'];
            }
            $this->redis->set($key, json_encode($list), self::CACHE_TIME);
        }
        $list = $this->array_sort($list, $sort, $order);
        if(isset($_GET['is_explore']) && $_GET['is_explore'] == 1){
            return $this->explore_p($list, $start_date.'至'.$end_date);
        }
        foreach($list as $k=>$v){
            $list[$k]['key'] = $k+1;
        }
        $total = count($list);
        $list = array_slice($list, $offset, $limit);
        $result = array(
            'total' => $total,
            'rows'  => array_values($list),
            'start_date'=>$start_date,
            'end_date'=>$end_date
        );
        echo json_encode($result);
    }


    //对二维数组 进行排序
    public function array_sort($arr,$keys,$type='asc'){
        $tmp = array();
        foreach($arr as $k=>$v){
            $tmp[$k] = $v[$keys];
        }
        if($type == "asc"){
            array_multisort($arr, SORT_ASC, SORT_STRING, $tmp, SORT_ASC);
        }else{
            array_multisort($arr, SORT_DESC, SORT_STRING, $tmp, SORT_DESC);
        }
        return $arr;
    }

    //盒子数据导出
    public function explore_eq($list, $title=''){
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '设备名称')
            ->setCellValue('B1', '支付金额')
            ->setCellValue('C1', '支付订单数')
            ->setCellValue('D1', '客单价')
            ->setCellValue('E1', '支付转化率')
            ->setCellValue('F1', '开门次数')
            ->setCellValue('G1', '退款金额')
            ->setCellValue('H1', '退款率')
            ->setCellValue('I1', '设备管理员')
            ->setCellValue('J1', '日均支付金额')
            ->setCellValue('K1', '复购率')
            ->setCellValue('L1', '累计用户')
            ->setCellValue('M1', '运营时间')
            ->setCellValue('N1', '优惠券补贴')
            ->setCellValue('O1', '魔豆补贴')
            ->setCellValue('P1', '设备状态')
            ->setCellValue('Q1', '设备id')
            ->setCellValue('R1', '点位场景')
            ->setCellValue('S1', '设备等级')
            ->setCellValue('T1', '活动补贴')
            ->setCellValue('U1', '日均销售额')
        ;
        $objPHPExcel->getActiveSheet()->setTitle($title."盒子销售统计");
        foreach ($list as $k => $v) {
            $key = $k+2;
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$key, $v['eq_name'])
                ->setCellValue('B'.$key, number_format($v['sale_money'], 2))
                ->setCellValue('C'.$key, $v['order_num'])
                ->setCellValue('D'.$key, $v['user_avg'])
                ->setCellValue('E'.$key, $v['order_avg'])
                ->setCellValue('F'.$key, $v['open_num'])
                ->setCellValue('G'.$key, number_format($v['refund_money'], 2))
                ->setCellValue('H'.$key, $v['refund_avg'])
                ->setCellValue('I'.$key, $v['admin_name'])
                ->setCellValue('J'.$key, number_format($v['avg_money'], 2))
                ->setCellValue('K'.$key, $v['avg_user'].'%')
                ->setCellValue('L'.$key, $v['now_user'])
                ->setCellValue('M'.$key, $v['firstordertime'])
                ->setCellValue('N'.$key, $v['card_money'])
                ->setCellValue('O'.$key, $v['modou'])
                ->setCellValue('P'.$key, $v['status'])
                ->setCellValue('Q'.$key, $v['equipment_id'])
                ->setCellValue('R'.$key, $v['enterprise_scene'])
                ->setCellValue('S'.$key, $v['level'])
                ->setCellValue('T'.$key, number_format($v['discounted_money'], 2))
                ->setCellValue('U'.$key, number_format($v['avg_good_money'], 2))
            ;
        }

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $objPHPExcel->initHeader($title."盒子销售统计");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    //盒子数据导出
    public function explore_p($list, $title=''){
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '商品名称')
            ->setCellValue('B1', '销售商品件数')
            ->setCellValue('C1', '销售总金额')
            ->setCellValue('D1', '支付订单数')
            ->setCellValue('E1', '售价')
            ->setCellValue('F1', '一级类目')
            ->setCellValue('G1', '二级类目')
            ->setCellValue('H1', '进价');
        $objPHPExcel->getActiveSheet()->setTitle($title."商品销量统计");
        foreach ($list as $k => $v) {
            $key = $k+2;
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$key, $v['product_name'])
                ->setCellValue('B'.$key, $v['sale_qty'])
                ->setCellValue('C'.$key, $v['sale_money'])
                ->setCellValue('D'.$key, $v['order_num'])
                ->setCellValue('E'.$key, $v['price'])
                ->setCellValue('F'.$key, $v['class_parent'])
                ->setCellValue('G'.$key, $v['class_name'])
                ->setCellValue('H'.$key, $v['purchase_price']);
        }

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $objPHPExcel->initHeader($title."商品销量统计");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function show_refer(){
        $pd  = $this->input->post('pd');
        $type  = $this->input->post('type');
        $this->load->model('bi_overall_model');
        $open_num_refer = $this->bi_overall_model->count_refer($type,$pd,$this->platform_id);
        $this->Smarty->assign('total',$open_num_refer);
        $html = $this->Smarty->fetch('report/overall/model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }

}