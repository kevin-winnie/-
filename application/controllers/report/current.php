<?php

class Current extends MY_Controller
{
    public $workgroup = 'report';
    const YESTERDAY_DATA_KEY = 'p_yesterday_bi_key4_';
    const TODAY_DATA_KEY = 'p_today_bi_key4';
    const TODAY_EQ_DATA_KEY = 'p_today_bi_eq_key3';
    const TODAY_PRODUCT_DATA_KEY = 'p_today_bi_product_key3';

    const HOUR_YES_DATA_KEY = 'p_hour_yes_data_key1';//昨天小时的key
    const HOUR_WEEK_DATA_KEY = 'p_hour_week_data_key1';//上周的小时key

    const CACHE_TIME = 600;//缓存时间
    const CACHE_HOUR = 3600;//缓存时间
    const CACHE_DAY  = 86400;//按天的缓存
    const CACHE_WEEK = 604800;//按周的缓存
    public $platform_id = 0;

    function __construct()
    {
        parent::__construct();
        $this->load->model('order_model');
        $this->load->model('log_open_model');
        $this->load->model('equipment_new_model');
        $this->load->model('equipment_label_model');
        $this->load->model('user_model');
        $this->load->model('order_refund_model');
        $this->load->model('deliver_model');
        $this->load->model('commercial_model');
        $this->load->model('equipment_stock_model');

        $this->load->library('phpredis');
        $this->c_db = $this->load->database('citybox_master', TRUE);
        $this->redis = $this->phpredis->getConn();
        $this->platform_id = $this->input->get('platform_id')>0?$this->input->get('platform_id'):0;
//        $this->agent_id = $this->input->get('agent_id')>0?$this->input->get('agent_id'):$this->platform_id;
    }

    public function index()
    {
        ini_set('memory_limit', '500M');
        @set_time_limit(60);
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
        $this->_pagedata['agent_id']  = $this->agent_id;
        $this->_pagedata['platform_id']  = $this->platform_id;
        $this->_pagedata['platform_list']= $platform_list;

        $this->_pagedata['order'] = $this->get_today_data();
        $this->_pagedata['yesterday_order'] = $this->get_yesterday_data();
        $this->_pagedata['last_order'] = $this->get_yesterday_data('-7');

        $hour_data = $this->get_today_hour_data();
        $yes_hour_data = $this->get_yes_hour_data();
        $week_hour_data = $this->get_week_hour_data();
        $this->_pagedata['hour'] = json_encode($yes_hour_data['key']);
        $param[0]['name'] = '实时单小时统计';
        $param[0]['type'] = 'line';
        $param[0]['smooth'] = 'true';
        $param[0]['data'] = $hour_data['value'];
        $param[0]['lineStyle']['normal']['color'] = '#FF4948';//线条颜色
        $param[0]['itemStyle']['normal']['color'] = '#FF4948';//点颜色

        $param[1]['name'] = '昨日同期单小时统计';
        $param[1]['type'] = 'line';
        $param[1]['smooth'] = 'true';
        $param[1]['data'] = $yes_hour_data['value'];
        $param[1]['lineStyle']['normal']['color'] = '#F7BA2A';//线条颜色
        $param[1]['itemStyle']['normal']['color'] = '#F7BA2A';//点颜色

        $param[2]['name'] = '上周1-5单小时平均值';
        $param[2]['type'] = 'line';
        $param[2]['smooth'] = 'true';
        $param[2]['data'] = $week_hour_data['value'];
        $param[2]['lineStyle']['normal']['color'] = '#12CE66';//线条颜色
        $param[2]['itemStyle']['normal']['color'] = '#12CE66';//点颜色
        $this->_pagedata['param'] = json_encode($param);

        $total_param[0]['name'] = '实时累计';
        $total_param[0]['type'] = 'line';
        $total_param[0]['smooth'] = 'true';
        $total_param[0]['data'] = $hour_data['total_result'];
        $total_param[0]['lineStyle']['normal']['color'] = '#FF4948';//线条颜色
        $total_param[0]['itemStyle']['normal']['color'] = '#FF4948';//点颜色

        $total_param[1]['name'] = '昨日同期累计';
        $total_param[1]['type'] = 'line';
        $total_param[1]['smooth'] = 'true';
        $total_param[1]['data'] = $yes_hour_data['total_result'];
        $total_param[1]['lineStyle']['normal']['color'] = '#F7BA2A';//线条颜色
        $total_param[1]['itemStyle']['normal']['color'] = '#F7BA2A';//点颜色

        $total_param[2]['name'] = '上周1-5累计平均值';
        $total_param[2]['type'] = 'line';
        $total_param[2]['smooth'] = 'true';
        $total_param[2]['data'] = $week_hour_data['total_result'];
        $total_param[2]['lineStyle']['normal']['color'] = '#12CE66';//线条颜色
        $total_param[2]['itemStyle']['normal']['color'] = '#12CE66';//点颜色
        $this->_pagedata['total_param'] = json_encode($total_param);
        $this->page('report/current/index.html');
    }

    //获取今天的实时数据
    public function get_today_data(){
        $key = self::TODAY_DATA_KEY.':'.$this->platform_id;
        $tmp = $this->redis->get($key);
        if($tmp){
            return json_decode($tmp, true);
        }
        $order = $this->order_model->get_day_order(null, null, $this->platform_id);//获取当前订单数和人数
        $order['money'] = floatval($order['money']);
        $order['discounted_money'] = floatval($order['discounted_money']);
        $order['qty'] = intval($order['qty']);
        $order['user_avg'] = floatval(bcdiv($order['money'], $order['user_num'], 2));//客单价
        $order['after_user_avg'] = floatval(bcdiv($order['good_money'], $order['user_num'], 2));//折前客单价
        $order['num_avg']  = floatval(bcdiv($order['money'], $order['num'], 2));//笔单价
        $open_log =  $this->log_open_model->get_open_times(null, null, $this->platform_id);//获取开门次数
        $order = array_merge($order, $open_log);
        $order['time'] =  date('Y-m-d H:i:s');
        //库存
        $stock = $this->equipment_stock_model->get_platform_stock($this->platform_id);
        $order['stock']   = intval($stock['stock']);
        $order['stock_p'] = intval($stock['stock_p']);
        $order['stock_avg'] = floatval(bcdiv($order['qty'], ($order['qty']+$order['stock']), 4) * 100).'%';
        $order['reg_user']  = $this->user_model->get_reg_by_pl(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'), $this->platform_id);
        $order['reg_user_new']  = $this->user_model->get_req_by_new($this->platform_id);
        $refund = $this->order_refund_model->get_total($this->platform_id);
        $order['refund_num'] = intval($refund['refund_num']);
        $order['three_refund_num'] = intval($refund['three_refund_num']);
        $order['deliver_up']   = $this->deliver_model->get_product_num(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'), 1, $this->platform_id);
        $order['deliver_down'] = $this->deliver_model->get_product_num(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'), 2, $this->platform_id);
        $eq_new = $this->equipment_new_model->get_eq_num_first(strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59')), $this->platform_id);//设备表查找
        $order_eq =  $this->equipment_new_model->get_eq_curr($this->platform_id);//从订单里面查找
        $order['new_eq'] = intval($order_eq+$eq_new);
        $this->redis->set($key, json_encode($order), self::CACHE_TIME);
        return $order;
    }

    //获取昨天的订单数据
    public function get_yesterday_data($days = '-1'){
        $key = self::YESTERDAY_DATA_KEY.$days.':'.$this->platform_id;
        $tmp = $this->redis->get($key);
        if($tmp){
            return json_decode($tmp, true);
        }
        $order = $this->order_model->get_day_order(date('Y-m-d 00:00:00', strtotime($days.' days')), date('Y-m-d 23:59:59', strtotime($days.' days')), $this->platform_id);//获取当前订单数和人数
        $order['money'] = floatval($order['money']);
        $order['discounted_money'] = floatval($order['discounted_money']);
        $order['qty']   = intval($order['qty']);
        $order['user_avg'] = floatval(bcdiv($order['money'], $order['user_num'], 2));//客单价
        $order['after_user_avg'] = floatval(bcdiv($order['good_money'], $order['user_num'], 2));//客单价
        $order['num_avg'] = floatval(bcdiv($order['money'], $order['num'], 2));//笔单价
        $open_log =  $this->log_open_model->get_open_times(date('Y-m-d 00:00:00', strtotime($days.' days')), date('Y-m-d 23:59:59', strtotime($days.' days')), $this->platform_id);//获取开门次数
        $order = array_merge($order, $open_log);
        $order['reg_user']  = $this->user_model->get_reg_by_pl(date('Y-m-d 00:00:00', strtotime($days.' days')), date('Y-m-d 23:59:59', strtotime($days.' days')), $this->platform_id);
        $stock = $this->order_model->get_platform_stock(date('Y-m-d', strtotime($days.' days')), $this->platform_id);
        $order['stock_avg'] = floatval(bcdiv($order['qty'], ($order['qty']+$stock), 4) * 100).'%';
        $order['deliver_up']   = $this->deliver_model->get_product_num(date('Y-m-d 00:00:00', strtotime($days.' days')), date('Y-m-d 23:59:59', strtotime($days.' days')), 1, $this->platform_id);
        $order['deliver_down'] = $this->deliver_model->get_product_num(date('Y-m-d 00:00:00', strtotime($days.' days')), date('Y-m-d 23:59:59', strtotime($days.' days')), 2, $this->platform_id);
        $order['new_eq'] = $this->equipment_new_model->get_eq_num_first(strtotime(date('Y-m-d 00:00:00', strtotime($days.' days'))), strtotime(date('Y-m-d 23:59:59', strtotime($days.' days'))), $this->platform_id);
        if(time()>strtotime(date('Y-m-d 08:00:00'))){//超过8点钟，则缓存剩余时间
            $time = $this->get_next_time();
        }else{
            $time = self::CACHE_TIME;//8点钟之前是60秒
        }
        $this->redis->set($key, json_encode($order), $time);
        return $order;
    }

    function get_next_time(){
        return strtotime(date('Y-m-d 23:59:59')) -time();
    }

    //获取今天按小时的数据
    public function get_today_hour_data(){
        $key = self::HOUR_YES_DATA_KEY.':'.date('Y-m-d').':'.$this->platform_id;
        $cache = $this->redis->get($key);
        if($cache){
            return json_decode($cache, true);
        }
        $rs = $this->order_model->get_hour_data(date('Y-m-d'), null, $this->platform_id);
        $hour = date('H');
        $result = $total_result = array();
        $tmp = 0;
        if($hour==0){
            return $result;
        }
        for($i=0; $i<$hour; $i++){
            $result[$i] = floatval($rs[$i]);
            $total_result[$i] = $tmp+floatval($rs[$i]);//累计的数据
            $tmp += floatval($rs[$i]);
        }
        $keys = array_keys($result);
        $result = array('key'=>$keys, 'value'=>$result, 'total_result'=>$total_result);
        $this->redis->set($key, json_encode($result), self::CACHE_TIME);
        return $result;
    }

    ///获取昨天按小时的数据
    public function get_yes_hour_data(){
        $m_date = date('Y-m-d', strtotime('-1 days'));
        $key = self::HOUR_YES_DATA_KEY.':'.$m_date.':'.$this->platform_id;
        $tmp = $this->redis->get($key);
        if($tmp){
            return json_decode($tmp, true);
        }else{
            $rs = $this->order_model->get_hour_data($m_date, null, $this->platform_id);
            $result = $total_result = array();
            $tmp = 0;
            for($i=0; $i<24; $i++){
                $result[$i] = floatval($rs[$i]);
                $total_result[$i] = $tmp+floatval($rs[$i]);//累计的数据
                $tmp += floatval($rs[$i]);
            }
            $keys = array_keys($result);
            $result = array('key'=>$keys, 'value'=>$result, 'total_result'=>$total_result);
            $this->redis->set($key, json_encode($result), self::CACHE_HOUR);
            return $result;
        }
    }

    //获取上周按小时计算的数据
    public function get_week_hour_data(){
        $week = date('N');
        if ($week == 1) {
            $m_date = date('Y-m-d',strtotime('-1 monday'));
        }else {
            $m_date = date('Y-m-d',strtotime('-2 monday'));
        }
        $f_date = date('Y-m-d',strtotime($m_date.' +4 days'));
        $key = self::HOUR_WEEK_DATA_KEY.':'.$m_date.":".$this->platform_id;
        $tmp = $this->redis->get($key);
        if($tmp){
            return json_decode($tmp, true);
        }else{
            $rs = $this->order_model->get_hour_data($m_date, $f_date, $this->platform_id);
            $result = $total_result = array();
            $tmp = 0;
            for($i=0; $i<24; $i++){
                $result[$i]       = bcdiv($rs[$i], 5, 2);//五天的平均值
                $total_result[$i] = bcdiv($tmp+floatval($rs[$i]), 5, 2); ;//累计的数据
                $tmp += floatval($rs[$i]);
            }
            $keys = array_keys($result);
            $result = array('key'=>$keys, 'value'=>$result, 'total_result'=>$total_result);
            $this->redis->set($key, json_encode($result), self::CACHE_WEEK);
            return $result;
        }
    }

    //获取设备销量排行, 5分钟缓存
    public function eq_table(){
        session_write_close();
        $sort       = $this->input->get('sort')?$this->input->get('sort'):'money';
        $order      = $this->input->get('order')?$this->input->get('order'):'desc';
        $limit      = $this->input->get('limit')?$this->input->get('limit'):30;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $key = self::TODAY_EQ_DATA_KEY.":".$this->platform_id;
        $tmp = $this->redis->get($key);
        if($tmp){
            $open_list = json_decode($tmp, true);
        }else{
            $order_list = $this->order_model->get_order_by_eq(null, 'box_no', $this->platform_id);
            $open_list  = $this->log_open_model->get_open_times_eq(null, 'box_no', $this->platform_id);
            $stock      = $this->equipment_stock_model->get_platform_eq_stock($this->platform_id);
            $all_name   = $this->equipment_new_model->get_eq_name_all();
            foreach($open_list as $k=>$v){
                $open_list[$k]['num']   = intval($order_list[$k]['num']);//订单数
                $open_list[$k]['money'] = floatval($order_list[$k]['money']);//实付金额
                $open_list[$k]['user_num'] = intval($order_list[$k]['user_num']);//支付用户数
                $open_list[$k]['num_avg']  = floatval(bcdiv($order_list[$k]['money'], $order_list[$k]['num'], 2));//笔单价
                $open_list[$k]['user_avg'] = floatval(bcdiv($order_list[$k]['money'], $order_list[$k]['user_num'], 2));//客单价
                $open_list[$k]['after_user_avg'] = floatval(bcdiv($order_list[$k]['good_money'], $order_list[$k]['user_num'], 2));//客单价
                $open_list[$k]['eq_name']  = $all_name[$k];
                $open_list[$k]['stock_avg']  = floatval(bcdiv($order_list[$k]['num'], ($order_list[$k]['num']+intval($stock[$k])), 4) * 100);
                $open_list[$k]['order_avg']  = floatval(bcdiv($order_list[$k]['num'], $v['open_num'], 4) * 100);
            }
            $this->redis->set($key, json_encode($open_list), self::CACHE_TIME);
        }

        $open_list = $this->array_sort($open_list, $sort, $order);
        $open_list = array_values($open_list);
        $total_money = $total_order = 0;
        foreach($open_list as $k=>$v){
            $open_list[$k]['key'] = $k+1;
            $open_list[$k]['stock_avg']  = $v['stock_avg'].'%';
            $open_list[$k]['order_avg']  = $v['order_avg'].'%';
            $total_money += floatval($v['money']);
            $total_order += intval($v['num']);
        }
        $total = count($open_list);
        $open_list = array_slice($open_list, $offset, $limit);
        $result = array(
            'total' => $total,
            'rows'  => $open_list,
            'avg_money' => floatval(bcdiv($total_money, $total, 2)),
            'avg_order' => floatval(bcdiv($total_order, $total, 2))
        );
        echo json_encode($result);
    }

    //获取设备销量排行, 時間段---
    public function eq_table_day(){
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="user.csv"');
        header('Cache-Control: max-age=0');
        $sort       = $this->input->get('sort')?$this->input->get('sort'):'stock_avg';
        $order      = $this->input->get('order')?$this->input->get('order'):'desc';
        $date1      = $this->input->get('date1');
        $date2      = $this->input->get('date2');
        $platform_id      = $this->input->get('platform_id');
        $order_list = $this->order_model->get_order_by_eq_day($date1,$date2, 'box_no', $platform_id);
        $open_list  = $this->log_open_model->get_open_times_eq_day($date1,$date2, 'box_no', $platform_id);
        $stock      = $this->equipment_stock_model->get_platform_eq_stock($platform_id);
        $all_name   = $this->equipment_new_model->get_eq_name_all();
        foreach($open_list as $k=>$v){
            $open_list[$k]['num']   = intval($order_list[$k]['num']);//订单数
            $open_list[$k]['money'] = floatval($order_list[$k]['money']);//实付金额
            $open_list[$k]['user_num'] = intval($order_list[$k]['user_num']);//支付用户数
            $open_list[$k]['num_avg']  = floatval(bcdiv($order_list[$k]['money'], $order_list[$k]['num'], 2));//笔单价
            $open_list[$k]['user_avg'] = floatval(bcdiv($order_list[$k]['money'], $order_list[$k]['user_num'], 2));//客单价
            $open_list[$k]['eq_name']  = $all_name[$k];
            $open_list[$k]['stock_avg']  = floatval(bcdiv($order_list[$k]['num'], ($order_list[$k]['num']+intval($stock[$k])), 4) * 100);
            $open_list[$k]['order_avg']  = floatval(bcdiv($order_list[$k]['num'], $v['open_num'], 4) * 100);
        }

        $open_list = $this->array_sort($open_list, $sort, $order);
        $open_list = array_values($open_list);
        $rs=[];
        foreach($open_list as $k=>$v){
            $rs[$k]['eq_name'] = $v['eq_name'];
            $rs[$k]['stock_avg']  = $v['stock_avg'].'%';
            $rs[$k]['order_avg']  = $v['order_avg'].'%';
        }

        /**/
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        // 输出Excel列名信息
        $head = array("设备名称","动销率","转化率");
        foreach ($head as $i => $v) {
            // CSV的Excel支持GBK编码，一定要转换，否则乱码
            $head[$i] = iconv('utf-8', 'gbk', $v);
        }
        // 将数据通过fputcsv写到文件句柄
        fputcsv($fp, $head);
        // 计数器
        $cnt = 0;
        // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;
        // 逐行取出数据，不浪费内存
        $count = count($rs);
        for($t=0;$t<$count;$t++) {

            $cnt ++;
            if ($limit == $cnt) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $cnt = 0;
            }
            $row = $rs[$t];
            foreach ($row as $i => $v) {
                $row[$i] = iconv('utf-8', 'gbk', $v);
            }
            fputcsv($fp, $row);
        }
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

    //商品销量排行
    public function product_table(){
        session_write_close();
        $sort  = $this->input->get('sort')?$this->input->get('sort'):'sale_num';
        $order = $this->input->get('order')?$this->input->get('order'):'desc';
        $limit      = $this->input->get('limit')?$this->input->get('limit'):30;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $cache_key  = self::TODAY_PRODUCT_DATA_KEY.':'.$this->platform_id;
        $tmp = $this->redis->get($cache_key);
        $where['o.order_status >'] = 0;
        $where['o.order_time >'] = date('Y-m-d 00:00:00');
        if($this->platform_id){
            $where['o.platform_id'] = $this->platform_id;
        }
        if($tmp){
            $list = json_decode($tmp, true);
        }else{
            $this->c_db->select('count(op.order_name) as order_num , sum(op.qty) as sale_num, sum(op.total_money) as sale_money, op.product_id as product_id , p.product_name, p.price as price, p.img_url ');
            $this->c_db->from('order_product op');
            $this->c_db->join("order o", 'op.order_name = o.order_name');
            $this->c_db->join("product p", 'p.id = op.product_id');
            $this->c_db->where($where);
            $this->c_db->group_by('op.product_id');
            $list = $this->c_db->get()->result_array();
            $stock = $this->equipment_stock_model->get_stock_product($this->platform_id);
            foreach($list as $k=>$v){
                $list[$k]['img_url'] = '//fdaycdn.fruitday.com/'.$v['img_url'];
                $list[$k]['stock_avg']  = floatval(bcdiv($v['sale_num'], ($v['sale_num']+$stock[$v['product_id']]), 4) * 100);
            }
            $this->redis->set($cache_key, json_encode($list), self::CACHE_TIME);
        }

        $list = $this->array_sort($list, $sort, $order);
        $total_stock_avg = 0;
        foreach($list as $k=>$v){
            $list[$k]['key'] = $k+1;
            $list[$k]['stock_avg'] = $v['stock_avg'].'%';
            $total_stock_avg += $v['stock_avg'];
        }
        $total = count($list);
        $list = array_slice($list, $offset, $limit);

        $result = array(
            'total' => $total,
            'rows' => $list,
            'avg_product' => floatval(bcdiv($total_stock_avg, $total, 2))
        );
        echo json_encode($result);

    }

    //判断用户的权限  26组的人 不能看全部数据
    public function get_admin_froup($admin_id){
        $this->c_db->from('s_admin_group');
        $this->c_db->where(array('admin_id'=>$admin_id));
        $rs = $this->c_db->get()->result_array();
        foreach($rs as $k=>$v){
            if($v['group_id'] == 26){
                return true;//属于26组
            }
        }
        return false;//不属于26组
    }

    //开门次数区分来源
    public function show_refer(){
        $platform_id = $this->platform_id;
        $this->load->model('log_open_model');
        $today = $this->log_open_model->get_open_times_refer(null, null,$platform_id);
        $start= date('Y-m-d 00:00:00', strtotime(' -1 days'));
        $end   = date('Y-m-d 23:59:59', strtotime(' -1 days'));
        $yesday = $this->log_open_model->get_open_times_refer($start, $end,$platform_id);
        $start_w = date("Y-m-d",strtotime("-7 days"));
        $end_w = date('Y-m-d 23:59:59', strtotime(' -7 days'));
        $yeswek = $this->log_open_model->get_open_times_refer($start_w, $end_w,$platform_id);
        $count['today'] = $today['total'];
        $count['yesday'] = $yesday['total'];
        $count['yeswek'] = $yeswek['total'];
        $this->Smarty->assign('today_detail',$today['rows']);
        $this->Smarty->assign('yesday_detail',$yesday['rows']);
        $this->Smarty->assign('yeswek_detail',$yeswek['rows']);
        $this->Smarty->assign('count',$count);
        $html = $this->Smarty->fetch('report/current/model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }
}