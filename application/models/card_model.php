<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/08/03
 */

class Card_model extends MY_Model
{
    function __construct(){
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }


    function get_card_model_list($where,$limit='',$offset='',$order='',$sort='',$where_in = ''){
        if(!isset($where['platform_id'])){
            $where['platform_id'] = $this->platform_id;
        }
        $this->c_db->select("*");
        $this->c_db->from('card_model');
        if(!empty($where))
            $this->c_db->where($where);
        $this->c_db->limit($limit,$offset);
        $this->c_db->order_by('id desc');
        $list = $this->c_db->get()->result_array();
        foreach($list as $k=>$each){
            if ($each['time_limit_type'] == 1){
                $list[$k]['card_time'] = $each['card_begin_date'].' - '.$each['card_end_date'];
            } else {
                $list[$k]['card_time'] = '领取后'.$each['card_last'].'天';
            }
        }

        $this->c_db->select("*");
        $this->c_db->from('card_model');
        if(!empty($where))
            $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }

    function get_list($where,$limit='',$offset='',$order='',$sort='',$where_in = ''){
        $this->c_db->select("*");
        $this->c_db->from('card');
        if(!empty($where))
            $this->c_db->where($where);
        $this->c_db->limit($limit,$offset);
        $this->c_db->order_by('id desc');
        $list = $this->c_db->get()->result_array();
        foreach($list as $k=>$each){
            if ($each['is_used'] == 1){
                $list[$k]['is_used'] = '是';
            } else {
                $list[$k]['is_used'] = '否';
            }

            $list[$k]['source'] = trim($each['source'],',');
        }

        $this->c_db->select("*");
        $this->c_db->from('card');
        if(!empty($where))
            $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }

    /*
     * 批量插入优惠券
     */
    function insertBatchCard($data){
        return $this->c_db->insert_batch('card',$data);
    }

    /*
     * 插入优惠券模板
     */
    function add_card_model($data){
        return $this->c_db->insert('card_model',$data);
    }

    /*
     * 更新优惠券模板
     */
    function update_card_model($data,$where){
        return $this->c_db->update('card_model',$data,$where);
    }

    /*
     * 获取优惠券内容
     */
    function get_card_model_info($where){
        return $this->c_db->from('card_model')->where($where)->get()->row_array();
    }

    /*
     * 根据 优惠券模板生成优惠券基础数据
     */
    function init_card_by_card_model($card_model_info,$now){
//        echo '<pre>';
//        print_r($card_model_info);

        $card_data = array(
            'card_number' =>'',//空
            'uid' => '',
            'send_time'=>$now,
            'card_money'=>$card_model_info['card_money'],
            'order_money_limit'=>$card_model_info['order_money_limit'],
            'product_limit_type'=>$card_model_info['product_limit_type'],
            'use_with_sales'=>$card_model_info['use_with_sales'],
            'source'=>$card_model_info['source_limit'],
            'tag'=>$card_model_info['tag'],
            'card_name'=>$card_model_info['card_remarks'],
            'is_used'=>0,
            'platform_id'=>$card_model_info['platform_id']

        );

        if($card_model_info['order_limit_type']==1){
            $card_data['order_money_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 2;
        }elseif($card_model_info['order_limit_type']==2){
            $card_data['order_product_num_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 3;
        }else{//do nothing
            $order_limit_type = 1;
        }

        $card_data['order_limit_type'] = $order_limit_type;

        if($card_model_info['product_limit_type']==1){
            $card_data['product_id'] = $card_model_info['product_limit_value'];
        }elseif($card_model_info['product_limit_type']==2){
            $card_data['class_id'] = $card_model_info['product_limit_value'];
        }else{//do nothing
        }

        if($card_model_info['time_limit_type'] == 1){
            $card_data['begin_date'] = $card_model_info['card_begin_date'];
            $card_data['to_date'] = $card_model_info['card_end_date'];
        }else{
            $card_data['begin_date'] = date('Y-m-d');
            $card_data['to_date'] = date("Y-m-d",strtotime("+".($card_model_info['card_last']?$card_model_info['card_last']:1)." day"));;
        }

//print_r($card_data);
//        exit;



        return $card_data;
    }

    function rand_card_number($p_card_number = '') {
        $a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
        $a_array = explode(",", $a);
        $tname = '';
        for ($i = 1; $i <= 10; $i++) {
            $tname.=$a_array[rand(0, 31)];
        }
        if ($this->checkCardNum($p_card_number . $tname)) {
            $tname = $this->rand_card_number($p_card_number);
        }
        return $p_card_number.$tname;
    }

    function checkCardNum($card_number) {
        $this->c_db->from('card');
        $this->c_db->where('card_number', $card_number);
        $query = $this->c_db->get();
        $num = $query->num_rows();
        if ($num > 0) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * 根据订单信息 查询得到最优 优惠券
     * $order_info=array(
     *      'product_info'=>array(array('product_id'=>1,'class_id'=>1,'qty'=>1,'price'=>1),array('product_id'=>2,'class_id'=>2,'qty'=>1,'price'=>1)),
     *      'uid'=>1,
     *      'money'=>'100',
     *      'on_sale'=>true,   //是否享受过优惠活动
     *      'platform_id'=>1,
     *      'source'=>'alipay'，
     * )
     *
     */
    function get_which_card_to_use($order_info){
        $can_use_card = array();
        $today = date("Y-m-d");
        $where = array(
            'uid'=>$order_info['uid'],
            'begin_date <='=>$today,
            'to_date >='=>$today,
            'is_used' => 0
        );
        $where_in =array(0,$order_info['platform_id']);
        $avalible_cards = $this->get_avalible_cards($where,$where_in);

        $product_ids = array();
        $class_ids = array();
        $total_num = 0;
        $total_money = 0;
        if(!empty($order_info['product_info'])){
            foreach ($order_info['product_info'] as $v){
                $product_ids [] = $v['product_id'];
                $class_ids [] = $v['class_id'];
                $total_num += $v['qty'];
                $total_money += $v['qty']*$v['price'];
            }
        }
//        echo '<pre>';
//        print_r($avalible_cards);

        if(!empty($avalible_cards)){
            foreach ($avalible_cards as $card){
//                //优惠券金额大于订单实际需支付金额的时候不使用
                if($card['card_money']>=$order_info['money']){
                    continue;
                }

                //优惠券渠道是否满足
                $arr_source = explode(',',$card['source']);
                if($card['source']&&!in_array($order_info['source'],$arr_source)){
                    continue;
                }

                //是否可以和优惠活动一起用
                if($card['use_with_sales']==2&&$order_info['on_sale']==true){
                    continue;
                }

                //限制产品
                if($card['product_limit_type']==1){   //指定商品
                    if($card['product_id']){
                        $p_ids = explode(',', $card['product_id']);
                        $can_use_product = array_intersect($p_ids, $product_ids);
                        if(empty($can_use_product)){
                            continue;
                        }else{
                            if(!empty($order_info['product_info'])){
                                $order_product_money = $order_product_num = 0;
                                foreach ($order_info['product_info'] as $v){
                                    if(in_array($v['product_id'],$p_ids)){
                                        $order_product_money += $v['qty']*$v['price'];
                                        $order_product_num += $v['qty'];
                                    }
                                }
                            }
//                        var_dump($card['order_product_num_limit'],$order_product_num,$card['order_money_limit'],$order_product_money);

                            if($card['order_limit_type']==2&&$card['order_money_limit']>$order_product_money){//需判断是否满足满额
                                continue;
                            }elseif($card['order_limit_type']==3&&$card['order_product_num_limit']>$order_product_num){//需判断是否满足满件
                                continue;
                            }
                        }
                    }
                }elseif($card['product_limit_type']==2){  //指定品类
                    if($card['class_id']){                //限制品类
                        $card_class_ids = explode(',', $card['class_id']);
                        $can_use_class = array_intersect($card_class_ids, $class_ids);
                        if(empty($can_use_class)){
                            continue;
                        }else{
                            if(!empty($order_info['product_info'])){
                                $order_product_money = $order_product_num = 0;
//                            var_dump($card_class_ids);
                                foreach ($order_info['product_info'] as $v){
                                    if(in_array($v['class_id'],$card_class_ids)){
                                        $order_product_money += $v['qty']*$v['price'];
                                        $order_product_num += $v['qty'];
                                    }
                                }
                            }
//                        var_dump($card['order_product_num_limit'],$order_product_num,$card['order_money_limit'],$order_product_money,$card['order_limit_type']);
                            if($card['order_limit_type']==2&&$card['order_money_limit']>$order_product_money){//需判断是否满足满额
                                continue;
                            }elseif($card['order_limit_type']==3&&$card['order_product_num_limit']>$order_product_num){//需判断是否满足满件
                                continue;
                            }
                        }
                    }
                }else{
                    if($card['order_limit_type']==2&&$card['order_money_limit']>$total_money){//需判断是否满足满额
                        continue;
                    }elseif($card['order_limit_type']==3&&$card['order_product_num_limit']>$total_num){//需判断是否满足满件
                        continue;
                    }
                }
                $can_use_card = $card;
                break;
            }
        }
        return ($can_use_card);
    }

    /*
     * 优惠券是否可以使用
     */

    /*
     * 获取用户有效期内的优惠券
     */
    function get_avalible_cards($where,$where_in){
        $cards = $this->c_db->from('card')->where($where)->where_in('platform_id',$where_in)->order_by('card_money','desc')->get()->result_array();
        return $cards;
    }


    /*
     * 使用后更新优惠券状态   更新订单表卡号
     */
    function set_card_used($card_number){
        $rs = $this->c_db->update('card',array(
            'is_used'=>1,
        ),array('card_number'=>$card_number));
        return $rs;
    }

    /*
     * @desc 根据card_number获取优惠券信息
     * @return array
     * */
    function get_card_info($card_number, $return=''){
        $this->c_db->from('card');
        $this->c_db->where(array('card_number'=>$card_number));
        $rs = $this->c_db->get()->row_array();
        if($return){
            return $rs[$return];
        }
        return $rs;
    }


}