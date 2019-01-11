<?php

class Bi_overall_model extends MY_Model
{
    function __construct(){
        parent::__construct();
        $this->load->model('order_model');
        $this->load->model('log_open_model');
        $this->redis = $this->phpredis->getConn();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    //获取天数据
    public function get_day_data($date, $platform_id=0){
        $where = array('bi_date'=>$date);
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("SUM(order_money) as order_money ,SUM(good_money) as good_money , SUM(order_num) as order_num, SUM(open_num) as open_num,SUM(alipay_open_num) as alipay_open_num,SUM(wechat_open_num) as wechat_open_num,SUM(other_open_num) as other_open_num, SUM(refund_money) as refund_money, SUM(refund_num) as refund_num, SUM(order_unpaid) as order_unpaid, SUM(card_money) as card_money, SUM(modou) as modou, SUM(discounted_money) as discounted_money, SUM(good_money) as good_money");
        $this->c_db->from('bi_overall');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();

        $rs['order_money'] = $rs['order_money']>0?$rs['order_money']:0;
        $rs['good_money'] = $rs['good_money']>0?$rs['good_money']:0;
        $rs['order_num']   = $rs['order_num']>0?$rs['order_num']:0;
        $rs['open_num']    = $rs['open_num']>0?$rs['open_num']:0;
        $rs['alipay_open_num']    = $rs['alipay_open_num']>0?$rs['alipay_open_num']:0;
        $rs['wechat_open_num']    = $rs['wechat_open_num']>0?$rs['wechat_open_num']:0;
        $rs['other_open_num']    = $rs['other_open_num']>0?$rs['other_open_num']:0;
        $rs['refund_money']= $rs['refund_money']>0?$rs['refund_money']:0;
        $rs['refund_num']  = $rs['refund_num']>0?$rs['refund_num']:0;
        $rs['order_unpaid']= $rs['order_unpaid']>0?$rs['order_unpaid']:0;
        $rs['card_money']  = $rs['card_money']>0?$rs['card_money']:0;
        $rs['modou']       = $rs['modou']>0?$rs['modou']:0;
        $rs['discounted_money']= $rs['discounted_money']>0?$rs['discounted_money']:0;
        $rs['good_money']  = $rs['good_money']>0?$rs['good_money']:0;
        $user_num = $this->order_model->get_order_user($date.' 00:00:00', $date.' 23:59:59', $platform_id);//订单用户数
        $rs['order_user_avg'] = floatval(bcdiv($rs['order_money'], $user_num, 2));//客单价
        $rs['after_order_user_avg'] = floatval(bcdiv($rs['good_money'], $user_num, 2));//折前客单价
        $rs['pay_avg'] = bcdiv($rs['order_num'], $rs['open_num'], 4)*100;//支付转化率  订单除未支付数 除以 开门次数
        $rs['refund_avg'] = bcdiv($rs['refund_num'], $rs['order_num'], 4)*100;//退款率
        return $rs;
    }


    //根据周来获取数据
    public function get_week_data($week, $platform_id=0){
        $where = array('bi_week'=>$week);
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("SUM(order_money) as order_money,SUM(good_money) as good_money, SUM(order_num) as order_num, SUM(open_num) as open_num,SUM(alipay_open_num) as alipay_open_num,SUM(wechat_open_num) as wechat_open_num,SUM(other_open_num) as other_open_num, SUM(refund_money) as refund_money, SUM(refund_num) as refund_num, SUM(order_unpaid) as order_unpaid, SUM(card_money) as card_money, SUM(modou) as modou, SUM(discounted_money) as discounted_money, SUM(good_money) as good_money");
        $this->c_db->from('bi_overall');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();
        foreach($rs as $k=>$v){
            $rs[$k] = $v?$v:0;
        }
        $week = str_replace('周', '', $week);
        $week = explode('第', $week);//
        $week[1] = intval($week[1]);
        $year_week = $this->get_week($week[0]);
        $start_date = $year_week[$week[1]][0];
        $end_date = $year_week[$week[1]][1];

        $user_num = $this->order_model->get_order_user($start_date.' 00:00:00', $end_date.' 23:59:59', $platform_id);//订单用户数
        $rs['order_user_avg'] = floatval(bcdiv($rs['order_money'], $user_num, 2));//客单价
        $rs['after_order_user_avg'] = floatval(bcdiv($rs['good_money'], $user_num, 2));//折前客单价
        $rs['pay_avg'] = bcdiv($rs['order_num'], $rs['open_num'], 4)*100;//支付转化率
        $rs['refund_avg'] = bcdiv($rs['refund_num'], $rs['order_num'], 4)*100;//退款率
        return $rs;
    }

    //根据月来获取数据
    public function get_month_data($month, $platform_id=0){
        $where = array('bi_month'=>$month);
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("SUM(order_money) as order_money,SUM(good_money) as good_money, SUM(order_num) as order_num, SUM(open_num) as open_num,SUM(alipay_open_num) as alipay_open_num,SUM(wechat_open_num) as wechat_open_num,SUM(other_open_num) as other_open_num, SUM(refund_money) as refund_money, SUM(refund_num) as refund_num, SUM(order_unpaid) as order_unpaid, SUM(card_money) as card_money, SUM(modou) as modou, SUM(discounted_money) as discounted_money, SUM(good_money) as good_money");
        $this->c_db->from('bi_overall');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();
        foreach($rs as $k=>$v){
            $rs[$k] = $v?$v:0;
        }
        $start_time = date('Y-m-01 00:00:00', strtotime($month));
        $end_time  = date('Y-m-t 23:59:59', strtotime($month));

        $user_num = $this->order_model->get_order_user($start_time, $end_time, $platform_id);//订单用户数
        $rs['order_user_avg'] = floatval(bcdiv($rs['order_money'], $user_num, 2));//客单价
        $rs['after_order_user_avg'] = floatval(bcdiv($rs['good_money'], $user_num, 2));//折前客单价
        $rs['pay_avg'] = bcdiv($rs['order_num'], $rs['open_num'], 4)*100;//支付转化率
        $rs['refund_avg'] = bcdiv($rs['refund_num'], $rs['order_num'], 4)*100;//退款率
        return $rs;
    }


    function get_week($year) {
        $year_start = $year . "-01-01";
        $year_end = $year . "-12-31";
        $startday = strtotime($year_start);
        if (intval(date('N', $startday)) != '1') {
            $startday = strtotime("next monday", strtotime($year_start)); //获取年第一周的日期
        }
        $year_mondy = date("Y-m-d", $startday); //获取年第一周的日期

        $endday = strtotime($year_end);
        if (intval(date('W', $endday)) == 1) {
            $endday = strtotime("last sunday", strtotime($year_end));
        }

        $num = intval(date('W', $endday));
        for ($i = 1; $i <= $num; $i++) {
            $j = $i -1;
            $start_date = date("Y-m-d", strtotime("$year_mondy $j week "));

            $end_day = date("Y-m-d", strtotime("$start_date +6 day"));

            $week_array[$i] = array ($start_date, $end_day);
        }
        return $week_array;
    }

    //通过统计表获取订单数据
    function get_date_data($start_date, $end_date, $platform_id=0){
        $where = array('sale_date >='=>$start_date, 'sale_date <='=>$end_date);
        $where_user = array('sale_date'=>date('Y-m-d', strtotime('-1 days')));
        $eq_where = '';
        if($platform_id){
            $where['platform_id'] = $platform_id;
            $where_user['platform_id'] = $platform_id;
            $eq_where = ' and platform_id='.$platform_id;
        }

        $this->c_db->select("SUM(sale_money) as sale_money,SUM(good_money) as good_money, avg(sale_money) as avg_money, SUM(sale_qty) as sale_qty, SUM(open_num) as open_num, SUM(order_num) as order_num, SUM(refund_num) as refund_num, SUM(refund_money) as refund_money,SUM(user_num) as user_num, SUM(total_user_num) as total_user_num, SUM(card_money) as card_money, SUM(modou) as modou, SUM(discounted_money) as discounted_money, avg(good_money) as avg_good_money, box_no");
        $this->c_db->from('order_sale');
        $this->c_db->where($where);
        $this->c_db->group_by('box_no');
        $tmp = $this->c_db->get()->result_array();
        $order = array();
        foreach($tmp as $k=>$v){
            $order[$v['box_no']] = $v;
        }
        //获取最新的 累计用户数

        $this->c_db->select(" total_user_num, box_no");
        $this->c_db->from('order_sale');
        $this->c_db->where($where_user);
        $this->c_db->group_by('box_no');
        $tmp = $this->c_db->get()->result_array();
        $now_user = array();
        foreach($tmp as $k=>$v){
            $now_user[$v['box_no']] = $v['total_user_num'];
        }

        //管理员列表
        $sql = "select id,`alias` from s_admin ";
        $tmp_list = $this->c_db->query($sql)->result_array();
        $admin_list = array();
        foreach($tmp_list as $k=>$v){
            $admin_list[$v['id']] = $v['alias'];
        }
        //盒子列表
        $sql = 'select `equipment_id`,`platform_id`,`name`, admin_id, firstordertime,status,enterprise_scene,`level` from cb_equipment where status > 0 '.$eq_where;
        $box_list = $this->c_db->query($sql)->result_array();
        $result = array();
        $all_user = $this->order_model->get_order_user_num_all( $start_date.' 00:00:00', $end_date.' 23:59:59');
        foreach($box_list as $k=>$v){
            $order_num  = intval($order[$v['equipment_id']]['order_num']);
            $open_num   = intval($order[$v['equipment_id']]['open_num']);
            $sale_money = floatval($order[$v['equipment_id']]['sale_money']);
            $good_money = floatval($order[$v['equipment_id']]['good_money']);
            $avg_money  = floatval($order[$v['equipment_id']]['avg_money']);
            $user_num   = floatval($order[$v['equipment_id']]['user_num']);
            $total_user_num  = floatval($order[$v['equipment_id']]['total_user_num']);
            $card_money  = floatval($order[$v['equipment_id']]['card_money']);
            $modou       = floatval($order[$v['equipment_id']]['modou']);
            $eq_status = '';
            if($v['status']==0){
                $eq_status = '停用';
            }elseif($v['status']==1){
                $eq_status = '启用';
            }elseif($v['status']==99){
                $eq_status = '报废';
            }
            $result[$v['equipment_id']]['eq_name']  = $v['name'];
            $result[$v['equipment_id']]['sale_money'] = $sale_money;
            $result[$v['equipment_id']]['good_money'] = $good_money;
            $result[$v['equipment_id']]['order_num']  = $order_num;
            $result[$v['equipment_id']]['user_avg']   = floatval(bcdiv($sale_money, intval($all_user[$v['equipment_id']]), 2));//客单价
            $result[$v['equipment_id']]['after_user_avg']   = floatval(bcdiv($good_money, intval($all_user[$v['equipment_id']]), 2));//折前客单价
            $result[$v['equipment_id']]['order_avg']  = bcdiv($order_num, $open_num, 4)*100;//支付转化率
            $result[$v['equipment_id']]['open_num']   = $open_num;//开门次数
            $result[$v['equipment_id']]['refund_avg']   = (bcdiv(intval($order[$v['equipment_id']]['refund_num']), $order_num, 4)*100);//退款率
            $result[$v['equipment_id']]['refund_money'] = floatval($order[$v['equipment_id']]['refund_money']);//退款金额
            $result[$v['equipment_id']]['admin_name']   = $admin_list[$v['admin_id']];
            $result[$v['equipment_id']]['avg_money']    = $avg_money;
            $result[$v['equipment_id']]['avg_user']     = bcdiv($user_num, $total_user_num, 2)*100;
            $result[$v['equipment_id']]['now_user']     = floatval($now_user[$v['equipment_id']]);
            $result[$v['equipment_id']]['firstordertime']= $v['firstordertime']?date('Y-m-d H:i:s', $v['firstordertime']):'';
            $result[$v['equipment_id']]['card_money']    = $card_money;
            $result[$v['equipment_id']]['modou']         = $modou;
            $result[$v['equipment_id']]['status']        = $eq_status;
            $result[$v['equipment_id']]['discounted_money']= floatval($order[$v['equipment_id']]['discounted_money']);
            $result[$v['equipment_id']]['enterprise_scene']= $v['enterprise_scene'];
            $result[$v['equipment_id']]['level']         = $v['level'];
            $result[$v['equipment_id']]['equipment_id']  = $v['equipment_id'];
            $result[$v['equipment_id']]['avg_good_money']= floatval($order[$v['equipment_id']]['avg_good_money']);
        }
        return $result;
    }

    //统计获取商品排行
    function get_date_p_data($start_date, $end_date, $platform_id=0){
        $where = array('bi_date >='=>$start_date, 'bi_date <='=>$end_date);
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("SUM(sale_money) as sale_money, SUM(sale_qty) as sale_qty, SUM(order_num) as order_num, SUM(stock) as stock, product_id");
        $this->c_db->from('bi_overall_product');
        $this->c_db->where($where);
        $this->c_db->group_by('product_id');
        $tmp = $this->c_db->get()->result_array();
        $result = array();
        foreach($tmp as $k=>$v){
            $result[$v['product_id']] = $v;
        }
        return $result;
    }

    //统计开门来源次数
    function count_refer($type,$pd,$platform_id){
        switch($type){
            case 0:
                $where['bi_date'] = $pd;
                break;
            case 1:
                $where['bi_week'] = $pd;
                break;
            case 2:
                $where['bi_month'] = $pd;
                break;
        }
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("SUM(open_num) as open_num,,SUM(alipay_open_num) as alipay_open_num,SUM(wechat_open_num) as wechat_open_num,SUM(other_open_num) as other_open_num");
        $this->c_db->from('bi_overall');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();
        $result['open_refer'] = $this->get_refer_name($rs);
        $result['open_num'] = $rs['open_num'];
        return $result;
    }

    function get_refer_name($data){
        if(empty($data)){
            return array();
        }
        $result = array();
        unset($data['open_num']);
        foreach($data as $k=>$v){
            if($k == 'alipay_open_num'){
                $result[$k]['refer_name'] = '支付宝';
            }elseif($k == 'wechat_open_num'){
                $result[$k]['refer_name'] = '微信';
            }elseif($k == 'other_open_num'){
                $result[$k]['refer_name'] = '其他';
            }
            $result[$k]['num'] = $v;
        }
        return $result;
    }

}