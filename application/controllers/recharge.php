<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/11/27
 * Time: 下午5:36
 */

class Recharge extends MY_Controller
{

    public $workgroup = 'recharge';

    function __construct()
    {
        parent::__construct();
        if (!isset($this->commonData['menuArr'][16])) {
            redirect("/admin/index");
        }
        $this->c_db = $this->load->database('citybox_master', TRUE);
        $this->load->library('encrypt');

    }

    public function index(){
        $this->page('recharge/index.html');
    }

    public function add(){
        $this->_pagedata['today'] = date('Y-m-d');
        $this->page('recharge/detail.html');
    }

    public function save(){
        $name       = $this->input->post('name');
        $money      = intval($this->input->post('money'));
        $to_date    = $this->input->post('to_date');
        $num        = $this->input->post('num');
        $admin_name = $this->input->post('admin_name');
        $use_num    = intval($this->input->post('use_num'));
        if(!$name){
            $this->showJson(array('status'=>'error','msg'=>'任务名称不能为空'));
        }
        if(!$money){
            $this->showJson(array('status'=>'error','msg'=>'金额不能为空'));
        }
        if(!$num){
            $this->showJson(array('status'=>'error','msg'=>'制作数量不能为0'));
        }
        if(!$to_date){
            $this->showJson(array('status'=>'error','msg'=>'结束日期不能为空'));
        }
        if( $use_num <= 0 || $use_num > $num ){
            $this->showJson(array('status'=>'error','msg'=>'充值限制数量必须大于0，且小于等于制作数量'));
        }
        $param['name'] = $name;
        $param['money'] = $money;
        $param['to_date'] = $to_date;
        $param['num'] = $num;
        $param['admin_name'] = $admin_name;
        $param['status']   = 0;
        $param['add_time'] = date('Y-m-d H:i:s');
        $param['use_num']  = $use_num;
        $this->c_db->insert('recharge_card_add', $param);
        if($this->c_db->insert_id()){
            $this->showJson(array('status'=>'success'));
        }
        $this->showJson(array('status'=>'error','msg'=>'系统错误'));
    }

    public function table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $name   = $this->input->get('search_name');
        $where = array('id >'=>0);
        $this->c_db->from('recharge_card_add');
        if($name){
            $this->c_db->like('name', $name);
        }
        $this->c_db->where($where);
        $this->c_db->limit($limit,$offset);
        $this->c_db->order_by('id', 'desc');
        $list = $this->c_db->get()->result_array();
        foreach($list as $k=>$v){
            if($v['status']==0){
                $list[$k]['status'] = '<button type="button" class="btn btn-default">未开始</button>';
            }elseif($v['status']==1){
                $list[$k]['status'] = '<button type="button" class="btn btn-default">制卡中</button>';
            }elseif($v['status']==2){
                $list[$k]['status'] = '<button type="button" class="btn btn-success">制卡完成</button>';
                $list[$k]['download'] = '<a href="/recharge/download/'.$v['id'].'" target="_blank">点击下载</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/recharge/card_list/'.$v['id'].'" target="_blank">查看详情</a> ';
            }elseif($v['status']==3){
                $list[$k]['status'] = '<button type="button" class="btn btn-danger">制卡失败</button>';
            }
            $list[$k]['name'] ='<a style="color:#333;text-decoration:none" title="'.$v['name'].'" href="javascript:void(0)">'.$v['name'].'</a>';
        }
        $this->c_db->from('recharge_card_add');
        $this->c_db->where($where);
        if($name){
            $this->c_db->like('name', $name);
        }
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);


    }

    public function download($id){
        $this->c_db->from('recharge_cards');
        $this->c_db->where(array('add_id'=>$id));
        $this->c_db->order_by('id', 'desc');
        $list = $this->c_db->get()->result_array();


        $this->c_db->from('recharge_card_add');
        $this->c_db->where(array('id'=>$id));

        $add_info = $this->c_db->get()->row_array();

        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '卡号')
            ->setCellValue('B1', '密码')
            ->setCellValue('C1', '金额')
            ->setCellValue('D1', '到期时间')
            ->setCellValue('E1', '是否使用')
            ->setCellValue('F1', '使用账号id')
        ;
        $objPHPExcel->getActiveSheet()->setTitle($add_info['name'].'充值卡列表');
        foreach($list as $k=>$v){
            $key = $k+2;
            if($v['is_used'] == 0){
                $is_used = '未充值';
            }else{
                $is_used = '已充值';
            }
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$key, $v['card_number'])
                ->setCellValue('B'.$key, $this->encrypt->decode($v['format_pass']))
                ->setCellValue('C'.$key, $v['card_money'])
                ->setCellValue('D'.$key, $v['to_date'])
                ->setCellValue('E'.$key, $is_used)
                ->setCellValue('F'.$key, $v['acount_id']);
        }


        @set_time_limit(0);
        $objPHPExcel->initHeader($add_info['name'].'充值卡列表');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;

    }

    //查询 任务产生的卡券列表
    public function card_list($id){
        $this->_pagedata['id'] = $id;
        $this->c_db->from('recharge_card_add');
        $this->c_db->where(array('id'=>$id));
        $this->_pagedata['detail'] = $this->c_db->get()->row_array();
        $this->page('recharge/card_list.html');
    }

    //卡券列表
    public function card_list_table(){
        $limit         = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset        = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $card_number   = $this->input->get('search_card_number');
        $id            = $this->input->get('id');
        $is_used       = $this->input->get('search_is_used');
        $start_time    = $this->input->get('search_start_time');
        $end_time      = $this->input->get('search_end_time');


        $where = array('add_id'=>$id);

        if(isset($_GET['search_is_used'])){
            $where['is_used'] = $is_used;
        }
        if($start_time){
            $where['used_time >='] = $start_time;
        }
        if($end_time){
            $where['used_time <='] = $end_time;
        }
        $this->c_db->from('recharge_cards');
        $this->c_db->where($where);
        if($card_number){
            $this->c_db->like('card_number', $card_number);
        }
        $this->c_db->limit($limit,$offset);
        $this->c_db->order_by('id', 'desc');
        $list = $this->c_db->get()->result_array();
        foreach($list as $k=>$v){
            if($v['is_used'] == 0){
                $list[$k]['status'] = '<button type="button" class="btn btn-success">未充值</button>';
            }else{
                $list[$k]['status'] = '<button type="button" class="btn btn-danger">已充值</button>';
            }
            $format_pass = $this->encrypt->decode($v['format_pass']);
            $list[$k]['format_pass'] = substr_replace($format_pass, '***********', 4, 11);
            $list[$k]['acount_id'] = '<a href="/user/user_list?acount_id='.$v['acount_id'].'" target="_blank">'.$v['acount_id'].'</a>';
        }
        $this->c_db->from('recharge_cards');
        $this->c_db->where($where);
        if($card_number){
            $this->c_db->like('card_number', $card_number);
        }
        $total = $this->c_db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }

}