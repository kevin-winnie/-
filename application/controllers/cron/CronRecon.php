<?php

/**
 * Class Reconciliation
 * 每日自动化计算代理商系统账目
 */
class CronRecon extends CI_Controller{

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
                $other_sale = array();
                $other_sale_ = array();
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
                    //此处需读取该商户配置的费率
                    if(!empty($order_sale_data))
                    {
                        //按来源分组 支付宝 微信
                        foreach($order_sale_data as $k=>$v)
                        {
                            if($v['refer'] == 'alipay')
                            {
                                $order_sale_refer[$v['refer']] = $v;
                            }elseif($v['refer'] == 'wechat')
                            {
                                $order_sale_refer[$v['refer']] = $v;
                            }else
                            {
                                $other_sale['good_money'] += $v['good_money'];
                                $other_sale['money'] += $v['money'];
                                $other_sale['discounted_money'] += $v['discounted_money'];
                                $order_sale_refer['other'] = $other_sale;
                            }
                        }
                    }else
                    {
                        $order_sale_refer = array();
                    }
                    $data[$val['id']]['order_sale'] = $order_sale_refer;
                    //获取退款金额
                    $sql = " SELECT sum(b.really_money+b.yue) as refund_money,a.refer FROM cb_order AS a
                              LEFT JOIN cb_order_refund as b ON a.order_name = b.order_name
                              WHERE a.platform_id = '{$platform_id}' and b.refund_status = 4 and a.order_status = 4
                              and b.admin_time >= '{$start}' and b.admin_time <= '{$end}' GROUP BY a.refer";

                    $order_refund_data = $this->c_db->query($sql)->result_array();
                    if(!empty($order_refund_data))
                    {
                        foreach($order_refund_data as $k=>$v)
                        {
                            if(!in_array($v['refer'],['alipay','wechat']))
                            {
                                $other_sale_['refund_money'] += $v['good_money'];
                                $order_refund_refer['other'] = $other_sale_;
                            }else
                            {
                                $order_refund_refer[$v['refer']] = $v;
                            }
                        }
                    }else
                    {
                        $order_refund_refer = array();
                    }
                    $data[$val['id']]['order_refund'] = $order_refund_refer;
                    $data[1]['order_refund'] = array(
                        'alipay'=>array(
                          'refund_money'=>10,
                        ),
                    );
                }else
                {
                    $data[$val['id']]['order_sale'] = array();
                    $data[$val['id']]['order_refund'] = array();
                }
                //扣除退款后的实际收入到citybox账户的金额
                $data[$val['id']]['really_moeny']['alipay'] = bcsub($data[$val['id']]['order_sale']['alipay']['money'],$data[$val['id']]['order_refund']['alipay']['refund_money'],2);
                $data[$val['id']]['really_moeny']['wechat'] = bcsub($data[$val['id']]['order_sale']['wechat']['money'],$data[$val['id']]['order_refund']['wechat']['refund_money'],2);
                $data[$val['id']]['really_moeny']['other'] = bcsub($data[$val['id']]['order_sale']['other']['money'],$data[$val['id']]['order_refund']['other']['refund_money'],2);
            }
            //商户产生的收入全部到CITYBOX账户下，开始按分成比例、微信、支付宝费率进行分配
            $sql = " select * from p_agent";
            $agent_rs = $this->db->query($sql)->result_array();
            $agent_sale_data = array();
            if(!empty($agent_rs))
            {
                foreach($agent_rs as $key=>$val)
                {
                    $agent_id = $val['id'];
                    //该代理商下所有代理商id
                    $agent_array = $this->get_all_agent_data($agent_id);
                    //满足条件的商户
                    $commercial_array = $this->get_all_commercial_data($agent_array);
                    if(!empty($commercial_array))
                    {
                        foreach($data as $k=>$v)
                        {
                            if(in_array($k,$commercial_array))
                            {
                                $agent_sale_data[$agent_id]['alipay'] += $v['really_moeny']['alipay'];
                                $agent_sale_data[$agent_id]['wechat'] += $v['really_moeny']['wechat'];
                                $agent_sale_data[$agent_id]['other'] += $v['really_moeny']['other'];
                            }
                        }
                    }else
                    {
                        $agent_sale_data[$agent_id]['alipay'] = 0;
                        $agent_sale_data[$agent_id]['wechat'] = 0;
                        $agent_sale_data[$agent_id]['other'] = 0;
                    }

                }
            }
            //计算每个代理商的入账金额
            foreach($agent_sale_data as $key=>$val)
            {
                //获取下级代理商对象及直营商户对象
                $low_commercial_lists = $this->get_zhiying($key);
                $low_agent_lists = $this->get_low_agent($key);
                //出账金额

                //入账金额
                if($key == 1)
                {
                    $agent_sale_data[$key]['entry_money'] = array_sum($val);
                }

            }
        }
    }

    /**
     * @param $commercial_id 商户id
     * @return mixed
     * 读取商户配置表
     */
    public function get_zhiying($key)
    {
        $sql = " select a.id,a.alipay_account,a.alipay_realname,separate_rate,b.alipay_rate,b.wechat_rate from p_commercial as a
                LEFT JOIN  p_config_device  as b ON a.id = b.platform_id
                WHERE a.high_agent_id = '{$key}'";
        return $this->db->query($sql)->result_array();
    }

    /**
     * @param $commercial_id 商户id
     * @return mixed
     * 读取商户配置表
     */
    public function get_low_agent($key)
    {
        $sql = " select * from p_agent WHERE high_agent_id = '{$key}'";
        return $this->db->query($sql)->result_array();
    }

    /**
     * @param $commercial_id 商户id
     * @return mixed
     * 读取商户配置表
     */
    public function get_commercial_rate($commercial_id)
    {
        $sql = " select a.id,b.* from p_commercial as a
                LEFT JOIN p_config_device as b ON a.id = b.platform_id
                WHERE a.platform_rs_id = '{$commercial_id}'";
        return $this->db->query($sql)->row_array();
    }
    /**
     * @param $commercial_id 商户id
     * @return mixed
     * 读取商户配置表
     */
    public function get_agent_rate($agent_id)
    {
        $sql = " select * from p_agent WHERE id = '{$agent_id}'";
        return $this->db->query($sql)->row_array();
    }
    /**
     * @param $array
     * @return mixed
     */
    public function get_all_commercial_data($array)
    {
        $this->db->select('id');
        $this->db->from('commercial');
        $this->db->where_in('high_agent_id', $array);
        //获取所有商户
        $commercial_array = $this->db->get()->result_array();
        $commercial_array = array_filter(array_column($commercial_array,'id'));
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
        $info[]['id'] = $agent_id;
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