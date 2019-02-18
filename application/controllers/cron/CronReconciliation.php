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
                    $commercial_rate = $this->get_commercial_rate($platform_id);
                    if(!empty($order_sale_data))
                    {
                        //按来源分组 支付宝 微信
                        foreach($order_sale_data as $k=>$v)
                        {
                            if($v['refer'] == 'alipay')
                            {
                                $order_sale_refer[$v['refer']] = $v;
                                $order_sale_refer[$v['refer']]['really_money'] = round($v['money']*(1-$commercial_rate['alipay_rate']),2);
                            }elseif($v['refer'] == 'wechat')
                            {
                                $order_sale_refer[$v['refer']] = $v;
                                $order_sale_refer[$v['refer']]['really_money'] = round($v['money']*(1-$commercial_rate['wechat_rate']),2);
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
                    $data[$val['platform_rs_id']]['order_sale'] = $order_sale_refer;
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
                    $data[$val['platform_rs_id']]['order_refund'] = $order_refund_refer;

                }
            }
        }
        //根据代理商计算分成 销售额、退款金额、补贴金额 、实际营收、出账金额、入账金额   入账金额>=出账金额
        //获取每个代理商下面的代理商、商户数据
        $agent_sale_data = array();
        $sql = " select * from p_agent";
        $agent_rs = $this->db->query($sql)->result_array();
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
                                $agent_sale_data[$agent_id][$k] = $v;
                            }
                        }
                    }else
                    {
                        $agent_sale_data[$agent_id] = array();
                    }

            }
        }
        //获取到每个代理商的销售额、退款金额、优惠金额--计算实际营收、出账金额、入账金额
        $data_money = array();
        foreach($agent_sale_data as $key=>$val)
        {
            if($val)
            {
                foreach($val as $k=>$v)
                {
                    if(!empty($v['order_sale']))
                    {
                        foreach($v['order_sale'] as $k1=>$v1)
                        {
                            $data_money[$key]['money'] += $v1['money'];
                            $data_money[$key]['discounted_money'] += $v1['discounted_money'];
                            $data_money[$key]['really_money'] += $v1['really_money'];
                        }
                    }else
                    {
                        $data_money[$key]['money'] += 0;
                        $data_money[$key]['discounted_money'] += 0;
                        $data_money[$key]['really_money'] += 0;
                    }

                    if(!empty($v['order_refund']))
                    {
                        foreach($v['order_refund'] as $k2=>$v2)
                        {
                            $data_money[$key]['refund_money'] += $v2['refund_money'];
                        }
                    }else
                    {
                        $data_money[$key]['refund_money'] += 0;
                    }
                }
            }else
            {
                $data_money[$key]['money'] = 0;
                $data_money[$key]['discounted_money'] = 0;
                $data_money[$key]['really_money'] = 0;
            }
            $sql = " select * from p_agent WHERE id = '{$key}'";
            $res = $this->db->query($sql)->row_array();
            $data_money[$key]['high_level'] =  $res['high_level'];
            $data_money[$key]['high_agent_id'] =  $res['high_agent_id'];
            $data_money[$key]['separate_rate'] =  $res['separate_rate'];
        }
        //计算代理商分成 按每级代理商的提成比例-->从顶级代理商开始
        $all_moeny = array();
        for ($x=0; $x<=5; $x++) {
            foreach($data_money as $key=>$val)
            {
                //从1级到5级
                if($val['high_level'] == $x)
                {
                    //1.先计算直营商户的分成
                    $zhiying_commercial = $this->get_zhiying($key);
                    foreach($zhiying_commercial as $k=>$v)
                    {
                        //此处需读取该商户配置的提成比例
                        $commercial_rate = $this->get_commercial_rate($v);
                        if(!empty($data[$v]['order_sale']))
                        {
                            foreach($data[$v]['order_sale'] as $k1=>$v1)
                            {
                                $all_moeny[$key]['com'][$v]['money'] += $v1['money'];
                            }
                        }else
                        {
                            $all_moeny[$key]['com'][$v]['money'] += 0;
                        }

                        if(!empty($data[$v]['order_refund']))
                        {
                            foreach($data[$v]['order_refund'] as $k2=>$v2)
                            {
                                $all_moeny[$key]['com'][$v]['refund_money'] += $v1['refund_money'];
                            }
                        }else
                        {
                            $all_moeny[$key]['com'][$v]['refund_money'] += 0;
                        }
                        $all_moeny[$key]['com'][$v]['rel_money'] = bcsub($all_moeny[$key]['com'][$v]['money'],$all_moeny[$key]['com'][$v]['refund_money'],2);
                        $com_ticheng = $commercial_rate['separate_rate']?$commercial_rate['separate_rate']:1;
                        $all_moeny[$key]['com'][$v]['ticheng_rel_money'] = $all_moeny[$key]['com'][$v]['rel_money']*$com_ticheng;
                    }

                    //2.计算下级代理分成
                    $n = $x + 1;
                    foreach($data_money as $k3=>$v3)
                    {
                        //下级代理并且上级代理商为该代理商
                        if($v3['high_level'] == $n && $v3['high_agent_id'] == $key)
                        {
                            $all_moeny[$key]['agent'][$k3]['rel_money'] = bcsub($data_money[$k3]['really_money'],$data_money[$k3]['refund_money'],2);
                            $agent_ticheng = $data_money[$k3]['separate_rate']?$data_money[$k3]['separate_rate']:1;
                            $all_moeny[$key]['agent'][$k3]['ticheng_rel_money'] = $all_moeny[$key]['agent'][$k3]['rel_money']*$agent_ticheng;
                        }
                    }

                }
            }
        }
        $msg = '';

        //整理出入账金额  收款账户数据 组装数据 插入
        foreach($data_money as $key=>$val)
        {
            foreach($all_moeny[$key]['com'] as $k1=>$v1)
            {
                $data_money[$key]['chuzhang'] += $v1['ticheng_rel_money'];
            }
            foreach($all_moeny[$key]['agent'] as $k2=>$v2)
            {
                $data_money[$key]['chuzhang'] += $v2['ticheng_rel_money'];
            }
            //获取收款数据
            $sql = " select * from p_agent WHERE id = '{$key}'";
            $agent = $this->db->query($sql)->row_array();
            $insert_data = array
            (
                'type'=>1,
                'start_time'=>$start,
                'end_time'=>$end,
                'acount_id'=>$agent['separate_account'],
                'acount_name'=>$agent['separate_name'],
                'money'=>$val['money'],
                'refund_money'=>$val['refund_money'],
                'dis_money'=>$val['discounted_money'],
                'agent_commer_id'=>$key,
                'realy_money'=>$val['really_money'],
                'separate_rate'=>$val['separate_rate'],
                'wechat_rate'=>$agent['wechat_rate'],
                'alipay_rate'=>$agent['alipay_rate'],
                'out_money'=>$data_money[$key]['chuzhang'],
                'in_money'=>$val['refund_money']
            );
            $rs = $this->db->insert('reconciliation', $insert_data);
            $msg .= $rs;
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
        $sql = " select platform_rs_id from p_commercial WHERE high_agent_id = '{$key}'";
        $rs = $this->db->query($sql)->result_array();
        $platfrom = array_column($rs,'platform_rs_id');
        return $platfrom;
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