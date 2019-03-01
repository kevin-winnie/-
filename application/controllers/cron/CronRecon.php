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
        $this->load->model('agent_model');
        $this->load->model('commercial_model');
        $this->load->model('equipment_model');
        $this->load->model('deliver_model');
        $this->c_db = $this->load->database('citybox_master',true);
    }

    /**
     * 自动化同步脚本 先把每个商户的数据计算到数据库
     */
    public function  index()
    {

        $time =  '2019-02-27'; //date('Y-m-d',time())
        $start = date('Y-m-d',strtotime('-1 days',strtotime($time)));
        $end = $time;
        $sql = " select a.id,a.platform_rs_id,a.alipay_account,a.alipay_realname,a.separate_rate,b.alipay_rate,b.wechat_rate from p_commercial as a
                  LEFT JOIN p_config_device as b ON a.id = b.platform_id
                  ";
        $rs = $this->db->query($sql)->result_array();
        $data = array();
        if(!empty($rs))
        {

            foreach($rs as $key=>$val)
            {
                $order_sale_refer = array();
                $order_refund_refer = array();
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
                }else
                {
                    $data[$val['id']]['order_sale'] = array();
                    $data[$val['id']]['order_refund'] = array();
                }
                //扣除退款后的实际收入到citybox账户的金额
                $data[$val['id']]['really_moeny']['alipay'] = bcsub($data[$val['id']]['order_sale']['alipay']['money'],$data[$val['id']]['order_refund']['alipay']['refund_money'],2);
                $data[$val['id']]['really_moeny']['wechat'] = bcsub($data[$val['id']]['order_sale']['wechat']['money'],$data[$val['id']]['order_refund']['wechat']['refund_money'],2);
                $data[$val['id']]['really_moeny']['other'] = bcsub($data[$val['id']]['order_sale']['other']['money'],$data[$val['id']]['order_refund']['other']['refund_money'],2);
                $data[$val['id']]['really_moeny']['dis_money'] = bcadd(bcadd($data[$val['id']]['order_sale']['alipay']['discounted_money'],$data[$val['id']]['order_sale']['wechat']['discounted_money'],2),$data[$val['id']]['order_sale']['other']['discounted_money'],2);
                $data[$val['id']]['really_moeny']['refund_money'] = bcadd(bcadd($data[$val['id']]['order_refund']['alipay']['refund_money'],$data[$val['id']]['order_refund']['wechat']['refund_money'],2),$data[$val['id']]['order_refund']['other']['refund_money'],2);
                $data[$val['id']]['really_moeny']['money'] = bcadd(bcadd($data[$val['id']]['order_sale']['alipay']['money'],$data[$val['id']]['order_sale']['wechat']['money'],2),$data[$val['id']]['order_sale']['other']['money'],2);
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
                                $agent_sale_data[$agent_id]['money'] += ($v['order_sale']['alipay']['money']+$v['order_sale']['wechat']['money']+$v['order_sale']['other']['money']);
                                $agent_sale_data[$agent_id]['refund_money'] += ($v['order_refund']['alipay']['refund_money']+$v['order_refund']['wechat']['refund_money']+$v['order_refund']['other']['refund_money']);
                                $agent_sale_data[$agent_id]['dis_money'] += ($v['order_sale']['alipay']['discounted_money']+$v['order_sale']['wechat']['discounted_money']+$v['order_sale']['wechat']['discounted_money']);
                            }
                        }
                    }else
                    {
                        $agent_sale_data[$agent_id]['alipay'] = 0;
                        $agent_sale_data[$agent_id]['wechat'] = 0;
                        $agent_sale_data[$agent_id]['other'] = 0;
                        $agent_sale_data[$agent_id]['money'] = 0;
                        $agent_sale_data[$agent_id]['refund_money'] = 0;
                        $agent_sale_data[$agent_id]['dis_money'] = 0;
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
                //直营商户的出账金额
                foreach($low_commercial_lists as $k1=>$v1)
                {
                    $v1['alipay_rate'] = (float)$v1['alipay_rate']/100;
                    $v1['wechat_rate'] = (float)$v1['wechat_rate']/100;
                    $v1['separate_rate'] = (float)$v1['separate_rate']/100;
                    $alipay = round($data[$v1['id']]['really_moeny']['alipay']*(1-$v1['alipay_rate'])*$v1['separate_rate'],2);
                    $wechat = round($data[$v1['id']]['really_moeny']['wechat']*(1-$v1['wechat_rate'])*$v1['separate_rate'],2);
                    $other = round($data[$v1['id']]['really_moeny']['other']*$v1['separate_rate'],2);
                    $all = bcadd(bcadd($alipay,$wechat,2),$other,2);
                    if($all > 0)
                    {
                        //该代理商应出给该商户的金额
                        $agent_sale_data[$key]['commercial'][$v1['id']]['out_money'] = $all;
                        //该直营商户的入账金额
                        $commercial_sale_data[$v1['id']]['entry_money'] = $all;
                    }
                }

                foreach($low_agent_lists as $k2=>$v2)
                {
                    $v2['alipay_rate'] = (float)$v2['alipay_rate']/100;
                    $v2['wechat_rate'] = (float)$v2['wechat_rate']/100;
                    $v2['separate_rate'] = (float)$v2['separate_rate']/100;
                    $alipay = round($agent_sale_data[$v2['id']]['alipay']*(1-$v2['alipay_rate'])*$v2['separate_rate'],2);
                    $wechat = round($agent_sale_data[$v2['id']]['wechat']*(1-$v2['wechat_rate'])*$v2['separate_rate'],2);
                    $other = round($agent_sale_data[$v2['id']]['other']*$v2['separate_rate'],2);
                    $all_1 = bcadd(bcadd($alipay,$wechat,2),$other,2);
                    if($all_1 > 0)
                    {
                        $agent_sale_data[$key]['agent'][$v2['id']]['out_money'] = $all_1;
                        //下级代理商的入账金额
                        $agent_sale_data[$v2['id']]['entry_money'] = $all_1;
                    }
                }
                //入账金额

                if($key == 1)
                {
                    $agent_sale_data[$key]['entry_money'] = bcadd(bcadd($val['alipay'],$val['wechat'],2),$val['other'],2);
                }
            }
            //商户---组装数据(仅有入账金额)
            $msg = '';
            foreach($commercial_sale_data as $key=>$val)
            {
                $r_commercial = $this->commercial_model->get_own_commercial_config($key);
                $insert_data = array
                (
                    'type'=>0,
                    'start_time'=>$start,
                    'end_time'=>$end,
                    'acount_id'=>$r_commercial['alipay_account'],
                    'acount_name'=>$r_commercial['alipay_realname'],
                    'money'=>$data[$key]['really_moeny']['money'],
                    'refund_money'=>$data[$key]['really_moeny']['refund_money'],
                    'dis_money'=>$data[$key]['really_moeny']['discounted_money'],
                    //收款商户
                    'agent_commer_id'=>$key,
                    'realy_money'=>$data[$key]['really_moeny']['alipay']+$data[$key]['really_moeny']['wechat']+$data[$key]['really_moeny']['other'],
                    'separate_rate'=>$r_commercial['separate_rate'],
                    'wechat_rate'=>$r_commercial['wechat_rate'],
                    'alipay_rate'=>$r_commercial['alipay_rate'],
                    'out_money'=>0,
                    'in_money'=>$val['entry_money'],
                    //打款代理商
                    'to_where_id'=>$r_commercial['high_agent_id'],
                );
                 $this->db->insert('reconciliation', $insert_data);
                $msg .= '商户'.$key.'插入成功;';
            }
            //代理商---组装数据
            foreach($agent_sale_data as $key=>$val)
            {
                $r_agent = $this->agent_model->get_own_agents($key);

                if(!empty($val['commercial']))
                {
                    foreach($val['commercial'] as $k1=>$v1)
                    {
                        $insert_data_s = array
                        (
                            'type'=>1, //打款类型 商户
                            'start_time'=>$start,
                            'end_time'=>$end,
                            'acount_id'=>$r_agent['separate_account'],
                            'acount_name'=>$r_agent['separate_name'],
                            'money'=>$val['money'],
                            'refund_money'=>$val['refund_money'],
                            'dis_money'=>$val['discounted_money'],
                            //收款代理商
                            'agent_commer_id'=>$key,
                            'realy_money'=>$val['alipay']+$val['wechat']+$val['other'],
                            'separate_rate'=>$val['separate_rate'],
                            'wechat_rate'=>$r_agent['wechat_rate'],
                            'alipay_rate'=>$r_agent['alipay_rate'],
                            'out_money'=>$v1['out_money'],
                            'in_money'=>$val['entry_money'],
                            //打款代理商
                            'to_where_id'=>$r_agent['high_agent_id'],
                        );
                        $this->db->insert('reconciliation', $insert_data_s);
                    }
                }

                if(!empty($val['agent']))
                {
                    foreach($val['agent'] as $k2=>$v2)
                    {
                        $insert_data_s_1 = array
                        (
                            'type'=>1, //打款类型 代理商
                            'start_time'=>$start,
                            'end_time'=>$end,
                            'acount_id'=>$r_agent['separate_account'],
                            'acount_name'=>$r_agent['separate_name'],
                            'money'=>$val['money'],
                            'refund_money'=>$val['refund_money'],
                            'dis_money'=>$val['discounted_money'],
                            //收款代理商
                            'agent_commer_id'=>$key,
                            'realy_money'=>$val['alipay']+$val['wechat']+$val['other'],
                            'separate_rate'=>$val['separate_rate'],
                            'wechat_rate'=>$r_agent['wechat_rate'],
                            'alipay_rate'=>$r_agent['alipay_rate'],
                            'out_money'=>$v2['out_money'],
                            'in_money'=>$val['entry_money'],
                            //打款代理商
                            'to_where_id'=>$r_agent['high_agent_id'],
                        );
                        $this->db->insert('reconciliation', $insert_data_s_1);
                    }
                }
                $msg .= '代理商'.$key.'插入成功;';
            }
            echo $msg;
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