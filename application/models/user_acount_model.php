<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/10/27
 * Time: 下午1:18
 */

class User_acount_model extends MY_Model
{

    function __construct()
    {
        parent::__construct ();
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    /*
     * @desc 获取用户信息
     * */
    function get_user_info_by_id($uid){
        if(!$uid){
            return false;
        }
        $this->c_db->select('u.acount_id, ua.user_rank, ua.moli, ua.modou, ua.yue');
        $this->c_db->from('user u');
        $this->c_db->join('user_acount ua', 'u.acount_id=ua.id');
        $this->c_db->where(array('u.id'=>$uid));
        return $this->c_db->get()->row_array();
    }

    function get_user_info_ids($ids){
        if(empty($ids)){
            return array();
        }
        $this->c_db->select('id, user_rank, moli, modou, yue');
        $this->c_db->from('user_acount');
        $this->c_db->where_in('id', $ids);
        $rs = $this->c_db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['id']] = $v;
        }
        return $result;
    }
}