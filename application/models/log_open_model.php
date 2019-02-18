<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/3/30
 */

class Log_open_model extends MY_Model
{
    function __construct(){
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    function getOpenLogCount($where){
        $today_count = $this->c_db->select('count(*) as open_times,uid')->from("log_open")->where($where)->group_by('uid')->get()->result_array();
        return $today_count;
    }

    //获取当天开门次数
    function get_open_times($date = '', $end_date='', $platform_id=0,$array=array()){
        $date = $date?$date:date('Y-m-d 00:00:00');
        $where = array( 'open_time >'=>$date);
        if($end_date){
            $where['open_time <'] = $end_date;
        }
        if($platform_id && empty($array)){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("count(id) as open_num, count(DISTINCT(uid)) as open_user_num");
        $this->c_db->from('log_open');
        $this->c_db->where($where);
        if(!empty($array))
        {
            $this->c_db->where_in('platform_id', $array);
        }
        return $this->c_db->get()->row_array();
    }

    //获取当天开门次数
    function get_open_times_eq($date = '', $group_by='', $platform_id=0){
        $date = $date?$date:date('Y-m-d 00:00:00');
        $where = array( 'open_time >'=>$date);
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("count(id) as open_num, count(DISTINCT(uid)) as open_user, box_no");
        $this->c_db->from('log_open');
        $this->c_db->where($where);
        if($group_by){
            $this->c_db->group_by($group_by);
        }
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['box_no']] = $v;
        }
        return $result;
    }
    //获取当天开门次数
    function get_open_times_eq_day($date1 = '',$date2 = '', $group_by='', $platform_id=0){
        $where = array( 'open_time >='=>$date1,'open_time <='=>$date2);
        if($platform_id){
            $where['platform_id'] = $platform_id;
        }
        $this->c_db->select("count(id) as open_num, count(DISTINCT(uid)) as open_user, box_no");
        $this->c_db->from('log_open');
        $this->c_db->where($where);
        if($group_by){
            $this->c_db->group_by($group_by);
        }
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['box_no']] = $v;
        }
        return $result;
    }

    function getOpenLogCount_v2($where){
        $today_count = $this->c_db->select('count(*) as open_times,uid')->from("log_open")->where($where)->group_by('uid')->get()->result_array();
        return $today_count;
    }



    //获取订单用户数
    public function get_user_num($start_time, $end_time){
        $where = array( 'open_time >='=>$start_time, "open_time <="=>$end_time);
        $this->c_db->select(" count(DISTINCT(uid)) as user_num");
        $this->c_db->from('log_open');
        $this->c_db->where($where);
        $rs = $this->c_db->get()->row_array();
        return $rs['user_num'];
    }

    //获取当天开门次数 区分来源
    function get_open_times_refer($date = '', $end_date='',$platform_id){
        $date = $date?$date:date('Y-m-d 00:00:00');
        $where = " operation_id = 1 and open_time >= '{$date}' ";
        if($platform_id){
            $where .= " and cb_log_open.platform_id = {$platform_id}";
        }
        if($end_date){
            $where .= " and open_time <='{$end_date}'";
        }
        $sql = "select count(*) as open_num,if(refer!='wechat' and refer!='alipay', 'xother', refer) as refer_t from cb_log_open where $where group by refer_t";
        $count_sql = "select count(*) as total from cb_log_open where $where ";
        $rs = $this->c_db->query($sql)->result_array();
        $count = $this->c_db->query($count_sql)->row_array();
        $rs = $this->get_refer_name($rs);
        $data['rows'] = $rs;
        $data['total'] = $count['total'];
        return $data;
    }

    function get_refer_name($data){
        if(empty($data)){
            return array();
        }
        foreach($data as $k=>$v){
            if($v['refer_t'] == 'alipay'){
                $data[$k]['refer_name'] = '支付宝';
            }elseif($v['refer_t'] == 'wechat'){
                $data[$k]['refer_name'] = '微信';
            }else{
                $data[$k]['refer_name'] = '其他';
            }
        }
        return $data;
    }

}