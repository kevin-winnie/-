<?php

/**
 * Class Reconciliation
 * 每日自动化计算代理商系统账目
 */
class CronReconciliation extends CI_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->ci = &get_instance();
        $this->load->model('order_model');
        $this->load->model('equipment_model');
        $this->load->model('deliver_model');
        $this->c_db = $this->load->database('citybox_master',true);
    }

    /**
     * 自动化同步脚本 先把每个商户的数据计算到数据库
     */
    public function  index()
    {

        $time =  '2017-12-16'; //date('Y-m-d',time())
        $start = date('Y-m-d',strtotime('-1 days',strtotime($time)));
        $end = $time;
        $sql = " select id,platform_rs_id from p_commercial";
        $rs = $this->db->query($sql)->result_array();
        $data = array();
        if(!empty($rs))
        {
            foreach($rs as $key=>$val)
            {
                //数据源  商户的订单数据  已支付、退款中、退款完成、退款驳回
                if($val['platform_rs_id'])
                {
                    $platform_id = $val['platform_rs_id'];
                    //根据platform_id去Admin平台获取订单数据
                    $sql = "SELECT a.refer,sum(a.good_money) AS good_money,sum(a.money + a.yue) AS money,
	                      sum(a.discounted_money + a.card_money) AS discounted_money FROM cb_order AS a
		                  WHERE a.platform_id = '{$platform_id}' AND a.order_time >= '{$start}' AND a.order_time <= '{$end}'
	                      AND a.order_status IN (1, 3, 4, 5) GROUP BY a.refer";

                    $order_sale_data = $this->c_db->query($sql)->result_array();
                    foreach($order_sale_data as $k=>$v)
                    {
                        if($v['refer'] == 'alipay')
                        {

                        }elseif($v['refer'] == 'wechat')
                        {

                        }else
                        {

                        }
                    }
                    $data[$val['platform_rs_id']]['order_sale'] = $order_sale_data;
                    //获取退款金额
                    $sql = " SELECT sum(b.really_money+b.yue) as refund_money,a.refer FROM cb_order AS a
                              LEFT JOIN cb_order_refund as b ON a.order_name = b.order_name
                              WHERE a.platform_id = '{$platform_id}' and b.refund_status = 4 and a.order_status = 4
                              and b.admin_time >= '{$start}' and b.admin_time <= '{$end}' GROUP BY a.refer";

                    $order_refund_data = $this->c_db->query($sql)->result_array();
                    $data[$val['platform_rs_id']]['order_refund'] = $order_refund_data;

                }
            }
        }
        echo '<pre>';print_r($data);exit;
        //根据代理商计算分成 销售额、退款金额、实际营收、出账金额、入账金额   入账金额>=出账金额
        //获取每个代理商下面的代理商、商户数据
        $agent_sale_data = array();
        $sql = " select * from p_agent";
        $agent_rs = $this->db->query($sql)->result_array();
        if(!empty($agent_rs))
        {
            foreach($agent_rs as $key=>$val)
            {
                $agent_id = $val['id'];
                //所有代理商id
                $agent_array = $this->get_all_agent_data($agent_id);
                //满足条件的商户
                if(!empty($agent_array))
                {
                    $commercial_array = $this->get_all_commercial_data($agent_array);
                    foreach($data as $k=>$v)
                    {
                        if(in_array($k,$commercial_array))
                        {
                            $agent_sale_data[$agent_id][$k][] = $v;
                        }
                    }
                }
            }
        }
        echo '<pre>';print_r($agent_sale_data);exit;
    }

    /**
     * @param $array
     * @return mixed
     */
    public function get_all_commercial_data($array)
    {
        $this->db->select('platform_rs_id');
        $this->db->from('commercial');
        $this->db->where_in('high_agent_id', $array);
        //获取所有商户
        $commercial_array = $this->db->get()->result_array();
        $commercial_array = array_column($commercial_array,'platform_rs_id');
        return $commercial_array;
    }
    /**
     * @param $agent_id
     * @return array
     */
    public function get_all_agent_data($agent_id)
    {
        //该代理商下级代理商
        $sql = " select id from p_agent as a WHERE a.high_agent_id = '{$agent_id}'";
        $rs = $this->db->query($sql)->result_array();
        $sql = " select * from p_agent WHERE id != '{$agent_id}'";
        $member = $this->db->query($sql)->result_array();
        $res = array();
        foreach($rs as $key=>$val)
        {
            $res[] = $this->GetTeamMember($member,$val['id']);
        }
        foreach($res as $key=>$val)
        {
            foreach($val as $k=>$v)
            {
                $info[] = $v;
            }
        }
        $info = array_merge((array)$rs,(array)$info);
        //获取到该用户下所有的代理商id
        $agent_array = array_unique(array_column($info,'id'));
        return $agent_array;
    }

    /*
*2.获取某个会员的无限下级方法
*$members是所有会员数据表,$mid是用户的id
*/
    function GetTeamMember($members,$mid) {
        $Teams=array();//最终结果
        $mids=array($mid);//第一次执行时候的用户id
        do {
            $othermids=array();
            $state=false;
            foreach ($mids as $valueone) {
                foreach ($members as $key => $valuetwo) {
                    if($valuetwo['high_agent_id']==$valueone){
                        $info['id'] = $valuetwo['id'];
                        $Teams[]= $info;//找到我的下级立即添加到最终结果中
                        $othermids[]=$valuetwo['id'];//将我的下级id保存起来用来下轮循环他的下级
                        array_splice($members,$key,1);//从所有会员中删除他
                        $state=true;
                    }
                }
            }
            $mids=$othermids;//foreach中找到的我的下级集合,用来下次循环
        } while ($state==true);

        return $Teams;
    }
}