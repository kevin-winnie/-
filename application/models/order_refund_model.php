<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/8/2
 * Time: 下午2:38
 */
class Order_refund_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    public $status = array(
        0 => '退款状态',
        1 => '待处理',
        2 => '驳回申请',
        3 => '退款中',
        4 => '退款成功',
        5 => '退款失败'
    );


    public function get_total($platform_id=0){
        $where1 = array('refund_status'=>1);
        $where2 = array('refund_status'=>1, 'create_time <='=>date('Y-m-d H:i:s', strtotime('-3 days')));
        if($platform_id){
            $where1['platform_id'] = $platform_id;
            $where2['platform_id'] = $platform_id;
        }
        $this->c_db->from('order_refund');
        $this->c_db->where($where1);
        $result['refund_num'] = $this->c_db->get()->num_rows();//总的申请
        $this->c_db->from('order_refund');
        $this->c_db->where($where2);
        $result['three_refund_num'] = $this->c_db->get()->num_rows();//超过三天的申请
        return $result;
    }
}