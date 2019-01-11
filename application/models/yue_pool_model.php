<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 18/7/5
 * Time: 上午11:18
 */

class Yue_pool_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }


    /**
     * 池子提现
     * @param $uid int 用户id
     * @param $money float 扣除金额
     * @return array
     */

    public function reduce_pool($uid, $money){
        $this->load->model('user_model');
        $user_info = $this->user_model->get_user($uid);
        $group_code = $user_info['group_code'];
        if(!$group_code){
            return array('status'=>'error', 'msg'=>'集团不存在');
        }
        $this->db->from('group_yue_pool');
        $this->db->where(array('group_code'=>$group_code));
        $rs = $this->db->get()->row_array();
        if(!$rs){
            return array('status'=>'error', 'msg'=>'集团充值池子不存在');
        }
        $last_money = bcsub($rs['money'], $money, 2);
        if($last_money<0){
            return array('status'=>'error', 'msg'=>'集团充值池子余额不足');
        }
        $this->db->trans_begin();
        $this->db->update('group_yue_pool', array('money'=>$last_money), array('id'=>$rs['id']));

        $log['order_name'] = $user_info['acount_id'];
        $log['type'] = 2;//1：充值，2：提现， 3：分账
        $log['op_money'] = -$money;
        $log['result_money'] = $last_money;
        $log['created_time'] = date('Y-m-d H:i:s');
        $log['remarks'] = '账号'.$user_info['acount_id'].'提现';
        $log['group_code'] = $group_code;
        $log['platform_id'] = $user_info['platform_id'];
        $this->db->insert('group_yue_pool_log', $log);
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('status'=>'error', 'msg'=>'系统异常');
        } else {
            $this->db->trans_commit();
            return array('status'=>'success', 'msg'=>'提交成功');
        }
    }


}