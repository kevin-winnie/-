<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User extends MY_Controller
{
    public $workgroup = 'user';

    function __construct() {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("equipment_new_model");
        $this->load->model('commercial_model');
        $this->load->model('user_acount_model');
        $this->load->model('order_model');
        $this->c_db = $this->load->database('citybox_master', TRUE);
    }

    public function user_list(){
        $this->_pagedata['acount_id']    = $this->input->get('acount_id')?$this->input->get('acount_id'):0;
        $this->_pagedata['store_list']   = $this->equipment_new_model->get_store_list();
        $this->_pagedata['platform_list']= $this->commercial_model->get_all_platforms();
        $this->page('user/user_list.html');
    }

    public function user_list_table(){
        $limit         = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset        = $this->input->get('offset')?$this->input->get('offset'):0;
        $search_mobile = $this->input->get('search_mobile');
        $search_nick   = $this->input->get('search_nick');
        $search_type   = $this->input->get('search_type');
        $search_device = $this->input->get('search_device');
        $start_time    = $this->input->get('search_start_time');
        $end_time      = $this->input->get('search_end_time');
        $sort          = $this->input->get('sort')?'i.'.$this->input->get('sort'):'u.id';
        $order         = $this->input->get('order')?$this->input->get('order'):'desc';
        $platform_id   = $this->input->get('search_platform_id')?$this->input->get('search_platform_id'):0;
        $acount_id     = $this->input->get('acount_id');

        $equipment_arr = array();
        if($search_device){
            $equipment_arr = $this->equipment_new_model->get_box_no(array('name'=>$search_device), 'equipment_id');
            if(empty($equipment_arr)){
                $equipment_arr = array('-1');
            }
        }
        $where = array();
        if($search_mobile){
            $where['u.mobile like'] = '%'.$search_mobile.'%';
        }
        if($search_nick){
            $where['u.user_name like'] = '%'.$search_nick.'%';
        }
        if($search_type){
            $where['u.source'] = $search_type;
        }
        if($start_time){
            $where['u.reg_time >='] = $start_time;
        }
        if($end_time){
            $where['u.reg_time <='] = $end_time;
        }
        if($platform_id){
            $where['u.platform_id'] = $platform_id;
        }
        if($acount_id){
            $where['u.acount_id']   = $acount_id;
        }
        //仅能看到直营商户的用户数据
        $commercial_list = $this->commercial_model->get_commercial_list($this->platform_id);
        $this->c_db->select("u.id,u.mobile,u.user_name,u.reg_time,sum(i.buy_times) as buy_times, sum(i.total_money) as total_money, sum(i.open_times) as open_times,u.source,u.open_id,u.register_device_id, u.acount_id");
        $this->c_db->from('user u');
        $this->c_db->join('user_daily_info i',"u.id=i.uid",'left');
        $this->c_db->order_by($sort.' '.$order);
        $this->c_db->limit($limit, $offset);
        if(!empty($where)){
            $this->c_db->where($where);
        }
        $this->c_db->group_by('u.id');
        if(!empty($equipment_arr)){
            $this->c_db->where_in('u.register_device_id', $equipment_arr);
        }
        $this->c_db->where_in('u.platform_id', $commercial_list);
        $list = $this->c_db->get()->result_array();
        if($_GET['is_export'] == 1){
            return $this->user_export($list);
        }
        $this->c_db->select("count(DISTINCT(u.id)) as user_num");
        $this->c_db->from('user u');
        $this->c_db->where($where);
        if(!empty($equipment_arr)){
            $this->c_db->where_in('u.register_device_id', $equipment_arr);
        }
        $this->c_db->where_in('u.platform_id', $commercial_list);
        $total = $this->c_db->get()->row_array();
        $acount_id_arr = array();
        foreach($list as $k=>$v){
            $acount_id_arr[] = $v['acount_id'];
        }
        $acount_info = $this->user_model->get_acount_info($acount_id_arr);

        $ranklist =  $this->config->item('user_rank');
        foreach($list as $k=>$v){
            $list[$k]['user_rank'] = $ranklist['level'][intval($acount_info[$v['acount_id']]['user_rank'])]['name'];
            $list[$k]['yue']       = '<a target="_blank" href="/user/yue_detail/'.$v['id'].'/'.$v['acount_id'].'">'.floatval($acount_info[$v['acount_id']]['yue']).'</a>';
            $list[$k]['name']      = $this->equipment_new_model->get_box_no(array('equipment_id'=>$v['register_device_id']), 'name');
        }

        $result = array(
            'total' => $total['user_num'],
            'rows' => $list
        );
        $result['a'] = $this->user_model->get_reg_by_pl(date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'),$platform_id);
        $result['b'] = $this->user_model->get_reg_by_pl(date('Y-m-d 00:00:00', strtotime('-1 days')), date('Y-m-d 23:59:59', strtotime('-1 days')),$platform_id);
        echo json_encode($result);
    }


    public function download_html($num){
        $limit = 5000;
        $page = ceil($num/$limit);
        $result = array();
        for($i=1;$i<=$page; $i++){
            $start = ($i-1)*$limit;
            $next = $i*$limit;
            $next = $next>$num?$num:$next;
            $result[$i]['text'] = '导出第'.$start.'-'.$next.'条用户';
            $result[$i]['url']  = '/user/user_list_table?is_export=1&order=asc&page='.$i.'&limit='.$limit.'&offset='.$start;
        }
        $this->Smarty->assign('list',$result);
        $html = $this->Smarty->fetch('user/download_model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }

    function user_export($list){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');

        $uids = $acount_id = $eq_id = array();
        foreach($list as $k=>$v){
            $acount_id[] = $v['acount_id'];
            $eq_id[]     = $v['register_device_id'];
            $uids[]      = $v['id'];
        }
        $acount_info  = $this->user_acount_model->get_user_info_ids($acount_id);
        $eq_info      = $this->equipment_new_model->get_equipment_by_ids($eq_id);
        $ranklist     = $this->config->item('user_rank');
        $last_time    = $this->order_model->get_user_last_order($uids);
        foreach($list as $k=>$v){
            $list[$k]['name'] = $eq_info[$v['register_device_id']];
            $user_rank = intval($acount_info[$v['acount_id']]['user_rank']);
            $list[$k]['user_rank'] = $ranklist['level'][$user_rank]['name'];
            $list[$k]['last_buy_time'] = $last_time[$v['id']];
        }
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '用户ID')
            ->setCellValue('B1', '用户手机号')
            ->setCellValue('C1', '昵称')
            ->setCellValue('D1', '注册时间')
            ->setCellValue('E1', '最后购买时间')
            ->setCellValue('F1', '购买次数')
            ->setCellValue('G1', '消费金额')
            ->setCellValue('H1', '开门次数')
            ->setCellValue('I1', '注册设备')
            ->setCellValue('J1', '来源')
            ->setCellValue('K1', '用户等级');
        $objPHPExcel->getActiveSheet()->setTitle('用户信息');

        foreach($list as $k=>$item){
            $i = $k+2;
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$i, $item['id'])
                ->setCellValue('B'.$i, $item['mobile'])
                ->setCellValue('C'.$i, $item['user_name'])
                ->setCellValue('D'.$i, $item['reg_time'])
                ->setCellValue('E'.$i, $item['last_buy_time'])
                ->setCellValue('F'.$i, intval($item['buy_times']))
                ->setCellValue('G'.$i, floatval($item['total_money']))
                ->setCellValue('H'.$i, intval($item['open_times']))
                ->setCellValue('I'.$i, $item['name'])
                ->setCellValue('J'.$i, $item['source'])
                ->setCellValue('K'.$i, $item['user_rank']);
        }

        // Redirect output to a client’s web browser (Excel2007)
        $filename = '用户导出-'.$_GET['page'];
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    function user_bind($id){
        $data  = $this->user_model->get_info_by_id($id);
        $this->_pagedata['user'] = $data['user'];
        $this->_pagedata['admin'] = $data['admin'];
        $this->page('user/user_bind.html');
    }

    function data_encode($data){
        echo json_encode($data);
    }

    //绑定后台账户  已废弃
    function do_bind(){
        $params = $this->input->post();
        $uid = $params['uid'];
        $name = $params['name'];
        $admin_info = $this->user_model->get_admin_info($name);
        if (empty($admin_info)){
            $this->data_encode(array('code'=>'300','msg'=>'错误的后台账户'));
        }else{
            $where = array('id'=>$uid);
            $data = array('s_admin_id'=>$admin_info['id']);
            $r = $this->user_model->user_update($data,$where);
            if($r){
                $this->data_encode(array('code'=>'200','msg'=>'绑定成功'));
            }else{
                $this->data_encode(array('code'=>'300','msg'=>'操作失败，请稍后重试'));
            }
        }
    }

    //解绑后台账户
    function do_unbild(){
        $params = $this->input->post();
        $uid = $params['uid'];
        $where = array('id'=>$uid);
        $data = array('s_admin_id'=>0);
        $r = $this->user_model->user_update($data,$where);
        if($r){
            $this->data_encode(array('code'=>'200','msg'=>'解绑成功'));
        }else{
            $this->data_encode(array('code'=>'300','msg'=>'操作失败，请稍后重试'));
        }
    }

    function s_admin_ap(){
        $params = $this->input->post();
        $query = $params['query'];
        $this->db->dbprefix = '';
        $data = $this->db->select('name')->from('s_admin')->where(array(
            'name like'=>'%'.$query.'%'
        ))->limit(10)->get()->result_array();
        echo json_encode($data);
    }

    //用户余额详情
    public function yue_detail($uid, $acount_id){
        $user = $this->user_model->get_user_by_acount($acount_id);

        foreach($user as $k=>$v){
            if($v['source']=='gat'){
                $user[$k]['source'] = '关爱通';
            }elseif($v['source']=='wechat'){
                $user[$k]['source'] = '微信';
            }elseif($v['source']=='alipay'){
                $user[$k]['source'] = '支付宝';
            }elseif($v['source']=='fruitday'){
                $user[$k]['source'] = '天天果园';
            }
        }
        $this->_pagedata['user']   = $user;
        $acount = $this->user_model->get_acount($acount_id);
        $this->_pagedata['acount'] = $acount;
        $yue    = $this->user_model->get_yue_libi($acount_id, $acount['yue']);
        $yue['card_money'] = bcsub($acount['yue'], bcadd($yue['withdraw'], $yue['bonusMoney'], 2), 2);
        $this->_pagedata['yue'] = $yue;
        $this->_pagedata['acount_id'] = $acount_id;
        $this->_pagedata['uid'] = $uid;
        $this->page('user/yue_detail.html');
    }

    //获取充值明细
    public function yue_table(){
        $limit         = $this->input->get('limit') ? $this->input->get('limit') : 50;
        $offset        = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $acount_id     = $this->input->get('acount_id');

        $this->load->helper('public_helper');
        //卡券充值
        $yue_card = $this->c_db->select('id,card_number as order_name,card_money as money,used_time as update_time')->from('recharge_cards')->where(array('acount_id'=>$acount_id))->get()->result_array();
        foreach($yue_card as $k=>$v){
            $yue_card[$k]['time'] = strtotime($v['update_time']);
            $yue_card[$k]['bonus']= 0;
            $yue_card[$k]['type'] = '卡券充值';
        }
        //在线充值  bonus  赠送
        $yue_online = $this->c_db->select('id,order_name,money,yue,(yue-money) as bonus,update_time,refer')->from('recharge_online')->where(array('acount_id'=>$acount_id, 'status'=>2))->get()->result_array();
        foreach($yue_online as $k=>$v){
            $yue_online[$k]['time'] = strtotime($v['update_time']);
            if($v['refer'] == 'alipay'){
                $yue_online[$k]['type'] = '支付宝充值';
            }elseif($v['refer'] == 'wechat'){
                $yue_online[$k]['type'] = '微信充值';
            }
        }
        $yue_array = array_merge($yue_card,$yue_online);
        $yue_array = array_sort($yue_array, 'time', 'desc');
        $list = array_slice($yue_array, $offset, $limit);
        $result = array(
            'total' => count($yue_array),
            'rows' => $list
        );
        echo json_encode($result);
    }

    //余额提现
    public function tixian(){
        $acount_id  = $this->input->post('acount_id');
        $uid        = $this->input->post('uid');
        $remarks    = $this->input->post('remarks');
        if(!$remarks){
            $this->showJson(array('status'=>'error', 'msg'=>'备注信息不能空'));
        }
        $remarks    = htmlspecialchars($remarks).'-'.$this->operation_name;
        $withdraw   = $this->input->post('withdraw');
        $bonusMoney = $this->input->post('bonusMoney');
        $acount     = $this->user_model->get_acount($acount_id);
        $yue_bili   = $this->user_model->get_yue_libi($acount_id, $acount['yue']);//再次验证比例
        if($withdraw==0){
            $this->showJson(array('status'=>'error', 'msg'=>'无可提现金额'));
        }
        if(!$acount_id || $withdraw!=$yue_bili['withdraw'] || $bonusMoney!=$yue_bili['bonusMoney']){
            $this->showJson(array('status'=>'error', 'msg'=>'金额不匹配'));
        }
        $last_money = bcsub($acount['yue'], bcadd($withdraw, $bonusMoney, 2), 2);//剩余的余额
        $this->load->model('yue_pool_model');
        $this->yue_pool_model->reduce_pool($uid, $withdraw);
        $this->db->trans_begin();
        $this->user_model->insert_acount_yue($uid, $acount_id, '提现', '-'.$withdraw, 5, $remarks);//insert 余额记录
        $this->user_model->insert_acount_yue($uid, $acount_id, '提现扣除赠送', '-'.$bonusMoney, 5);//insert 余额记录

        $this->c_db->update('user_acount',array('yue'=>$last_money, 'update_time'=>date('Y-m-d H:i:s')), array('id'=>$acount_id));//更新用户余额
        $this->c_db->update('recharge_online', array('is_tixian'=>1), array('acount_id'=>$acount_id, 'status'=>2, 'is_tixian'=>0));//标记在线充值表

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->showJson(array('status'=>'error', 'msg'=>'系统错误，稍后再试'));
        } else {
            $this->db->trans_commit();
            $this->showJson(array('status'=>'success', 'msg'=>'操作成功'));
        }
    }

    //冻结
    public function frozen(){
        $acount_id  = $this->input->post('acount_id');
        $uid        = $this->input->post('uid');
        $frozen_yue = $this->input->post('frozen_yue');
        $remarks    = $this->input->post('remarks');
        if(!$remarks){
            $this->showJson(array('status'=>'error', 'msg'=>'备注信息不能空'));
        }
        $remarks    = htmlspecialchars($remarks).'-'.$this->operation_name;

        $acount     = $this->user_model->get_acount($acount_id);
        $yue_bili   = $this->user_model->get_yue_libi($acount_id, $acount['yue']);//再次验证比例
        $card_money = bcsub($acount['yue'], bcadd($yue_bili['withdraw'], $yue_bili['bonusMoney'], 2), 2);

        $last_money = bcsub($acount['yue'], $frozen_yue, 2);
        if( !$acount_id || $frozen_yue==0 || $last_money<0 || bccomp($frozen_yue, $card_money, 2)>0 ){
            $this->showJson(array('status'=>'error', 'msg'=>'无可冻结金额'));
        }
        $this->db->trans_begin();
        $this->user_model->insert_acount_yue($uid, $acount_id, '冻结', '-'.$frozen_yue, 6, $remarks);//insert 余额记录
        $this->c_db->update('user_acount',array('yue'=>$last_money, 'frozen_yue'=>bcadd($acount['frozen_yue'], $frozen_yue, 2), 'update_time'=>date('Y-m-d H:i:s')), array('id'=>$acount_id));//更新用户余额
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->showJson(array('status'=>'error', 'msg'=>'系统错误，稍后再试'));
        } else {
            $this->db->trans_commit();
            $this->showJson(array('status'=>'success', 'msg'=>'操作成功'));
        }

    }

    //余额明细  余额类型0:消费，2：在线充值，1：赠送 3:退款，4:卡券充值，5：提现，6:冻结
    public function yue_list(){
        $acount_id  = $this->input->get('acount_id');
        $limit      = $this->input->get('limit') ? $this->input->get('limit') : 50;
        $offset     = $this->input->get('offset') ? $this->input->get('offset') : 0;

        $where = array('acount_id'=>$acount_id);
        $this->c_db->from('user_acount_yue');
        $this->c_db->where($where);
        $this->c_db->limit($limit,$offset);
        $this->c_db->order_by('id', 'desc');
        $list = $this->c_db->get()->result_array();
        foreach($list as $k=>$v){
            if($v['yue_type'] == 0){
                $list[$k]['yue_type'] = '消费';
            }elseif($v['yue_type'] == 1){
                $list[$k]['yue_type'] = '赠送';
            }elseif($v['yue_type'] == 2){
                $list[$k]['yue_type'] = '在线充值';
            }elseif($v['yue_type'] == 3){
                $list[$k]['yue_type'] = '退款';
            }elseif($v['yue_type'] == 4){
                $list[$k]['yue_type'] = '卡券充值';
            }elseif($v['yue_type'] == 5){
                $list[$k]['yue_type'] = '提现';
            }elseif($v['yue_type'] == 6){
                $list[$k]['yue_type'] = '冻结';
            }elseif($v['yue_type'] == 9){
                $list[$k]['yue_type'] = '后台充值';
            }
        }
        $this->c_db->from('user_acount_yue');
        $this->c_db->where($where);
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);

    }



}

