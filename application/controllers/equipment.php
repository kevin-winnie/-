<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Equipment extends MY_Controller {

    public $workgroup = 'equipment';
    private $secret_v2 = 'd50b6a5ff6ff4a3j814y6f6b97ec62ab';
    private $secret_nirvana2 = 'caa21c26dfc990c7a534425ec87a111c';
    private  $secret_cityboxapi = '48eU7IeTJ6zKKDd1';
    public  $pault_code = [ '1'=>"关门超时",2=>"盘点超时",3=>"门自动打开",4=>"正在服务中",5=>"锁异常",6=>"二维码请求超时",
                            7=>"RIFD异常",8=>"重启后锁舌未下落",9=>"无开门指令时锁自动开","10"=>"售货机失联","11"=>"网络未连接",
                            "12"=>"其他故障","13"=>"设备不制冷","14"=>"压缩机异响/噪音大","15"=>"大额误扣",'16'=>'门关不上','17'=>'设备制冷过度'
                           ,'18'=>'灯箱不亮','19'=>'门玻璃破碎','20'=>'显示屏黑屏','21'=>'显示屏白屏','22'=>'显示屏红绿蓝','23'=>'电源问题',
                            '24'=>'配送地址不对','25'=>'点位不上班','26'=>'缺失通行证','27'=>'设备不扣款','28'=>'大量撕标签(>=5)','29'=>'大量撕标签(<5)'];
//    //key为pault_type的key
    public  $pault_type_code = [
        '1'=>['1'=>"关门超时",3=>"门自动打开",5=>"锁异常",8=>"重启后锁舌未下落",9=>"无开门指令时锁自动开",'16'=>'门关不上'],
        '2'=>["13"=>"设备不制冷","14"=>"压缩机异响/噪音大",'17'=>'设备制冷过度','18'=>'灯箱不亮','19'=>'门玻璃破碎'],
        '3'=>['20'=>'显示屏黑屏','21'=>'显示屏白屏','22'=>'显示屏红绿蓝'],
        '4'=>['23'=>'电源问题'],
        '5'=>[2=>"盘点超时",4=>"正在服务中",6=>"二维码请求超时",7=>"RIFD异常","10"=>"售货机失联","11"=>"网络未连接",
            "12"=>"其他故障","15"=>"大额误扣",'27'=>'设备不扣款'],
        '6'=>['28'=>'大量撕标签(>=5)','29'=>'大量撕标签(<5)'],
        '7'=>['24'=>'配送地址不对'],
        '8'=>['25'=>'点位不上班'],
        '9'=>['26'=>'缺失通行证']
    ];
    public   $pault_type = [1=>'门锁问题',2=>'冰箱问题','3'=>'显示器问题','4'=>'电源问题','5'=>'设备问题','6'=>'偷盗问题','7'=>'配送地址不对','8'=>'点位不上班','9'=>'缺失通行证'];
    public  $pault_status = [1=>'未维修',2=>'维修中',3=>'已维修'];
    public  $scene = [1=>'平台后台',2=>'补货小程序',3=>'魔盒小程序'];
    function __construct() {
        parent::__construct();
        $this->p_db = $this->load->database('platform_master',true);
        $this->load->model("equipment_model");
        $this->load->model("commercial_model");
        $this->load->model("product_model");
        $this->load->model("agent_model");
        $this->load->library('curl',null,'http_curl');
        $this->eq_type = array(
            array('code'=>'rfid-1','name'=>"rfid-1[蚂蚁盒子RFID]"),
            array('code'=>'rfid-2','name'=>"rfid-2[自己生产RFID]"),
            array('code'=>'rfid-3','name'=>"rfid-3[数字RFID]"),
            array('code'=>'rfid-4','name'=>"rfid-4[无膜RFID]"),
            array('code'=>'rfid-5','name'=>"rfid-5[无膜RFID-数烨]"),
            array('code'=>'rfid-6','name'=>"rfid-6[数字RFID-数烨]"),
            array('code'=>'scan-1','name'=>"scan-1[扫码]"),
            array('code'=>'vision-1','name'=>"vision-1[视觉]"),
            array('code'=>'vision-2','name'=>"vision-2[视觉-数烨]"),
            array('code'=>'vision-3','name'=>"vision-3[静态视觉]"),
            array('code'=>'coffee-1','name'=>"coffee-1[咖啡设备-沙丁鱼]"),
        );
    }

    function index($id = '') {
        $where = ['is_hidden' => 0];
        if ($id){
            $this->_pagedata['id'] = $id;
        }
        $Agent = $this->agent_model->get_own_agents($this->platform_id);
        $Agent_list = $this->agent_model->get_all_agents($this->platform_id);
        if($this->svip)
        {
            $this->_pagedata['is_super'] = 1;
            //代理商级别
            $agent_level_list = $this->agent_model->get_agent_level_list($Agent);
        }
        $this->_pagedata['platform_list'] = $this->commercial_model->getList("*", $where);
        $this->title = '设备信息管理';
        $filter = ['last_agent_id'=>$this->platform_id];
        $this->_pagedata ["list"] = $this->equipment_model->getList($filter);
        $this->_pagedata ["Agent_list"] = $Agent_list;
        $this->_pagedata['agent_id'] = $this->platform_id;
        $this->_pagedata['agent_level_list'] = $agent_level_list;
        $this->page('equipment/index.html');
    }

    
    public function table($id='')
    {
        $limit = $this->input->get('limit') ? : 10;
        $offset = $this->input->get('offset') ? : 0;
        $search_code = $this->input->get('search_code') ? : '';
        $search_platform_id = $this->input->get('search_platform_id');
        $search_start_time = $this->input->get('search_start_time');
        $search_end_time = $this->input->get('search_end_time');
        if ($this->input->get('search_status') === '0'){
            $search_status = 0;
        } else {
            $search_status = $this->input->get('search_status') ? : '';
        }

        if ($this->input->get('search_platform_is_hidden') != null){
            if ($this->input->get('search_platform_is_hidden') == '-1') {
                $platform_is_hidden = null;
            } else {
                $platform_is_hidden = $this->input->get('search_platform_is_hidden');
            }
        } else {
            $platform_is_hidden = null;
        }
        $search_equipment_id = $this->input->get('search_equipment_id') ? : '';
        $where = array();
        if ($search_code){
            $where['code'] = $search_code;
        }
        if($search_platform_id && $search_platform_id!=-1) {
            $where['platform_id'] = $search_platform_id;
        }
        if ($id){
            $where['platform_id'] = $id;
        }
        if ($search_status || $search_status == 0){
            $where['status'] = $search_status;
        }
        if ($search_equipment_id){
            $where['equipment_id'] = $search_equipment_id;
        }
        if ($search_start_time){
            $where['start_time'] = strtotime($search_start_time);
        }
        if ($search_end_time){
            $where['end_time'] = strtotime($search_end_time);
        }
        $where['admin_id'] = $this->adminid;
        $where['last_agent_id'] = $this->platform_id;
        $array = $this->equipment_model->getEquipments("", $where, $offset, $limit, $platform_is_hidden);
        foreach ($array as $k=>$v){
            $array[$k]['qr_action'] = '';
            $array[$k]['platform_name'] = $v['platform_name'] ? $v['platform_name'] : '无';
            if (!$v['qr_common']){
                $array[$k]['qr_common_action'] = "<a href = '/equipment/qrcode/".$v['equipment_id']."/common'>生成通用二维码</a>";
            } else {
                $array[$k]['qr_common_action'] = "<a target='_blank' href = '".$v['qr_common']."'>显示</a>";
            }
            if (!$v['banner_common']){
                $array[$k]['banner_common'] = "<a href = '/equipment/banner/".$v['equipment_id']."/common_banner'>生成通用banner</a>";
            } else {
                $array[$k]['banner_common'] = "<a target='_blank' href = '".$v['banner_common']."'>显示</a>";
            }
            if (!$v['banner_common_alipay']){
                $array[$k]['banner_common_alipay'] = "<a href = '/equipment/banner/".$v['equipment_id']."/common_banner_alipay'>生成支付宝banner</a>";
            } else {
                $array[$k]['banner_common_alipay'] = "<a target='_blank' href = '".$v['banner_common_alipay']."'>显示</a>";
            }
            if (!$v['banner_common_wechat']){
                $array[$k]['banner_common_wechat'] = "<a href = '/equipment/banner/".$v['equipment_id']."/common_banner_wechat'>生成微信banner</a>";
            } else {
                $array[$k]['banner_common_wechat'] = "<a target='_blank' href = '".$v['banner_common_wechat']."'>显示</a>";
            }
            $array[$k]['config'] = "<a target='_blank' href = '/sys_config/device/".$v['equipment_id']."'>个性配置</a>";
        }

        $total = (int)$this->equipment_model->getEquipments("count(*) as c",$where)[0]['c'];

        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }
    
    public function export(){
        $limit = $this->input->get('limit') ? : 999;
        $offset = $this->input->get('offset') ? : 0;
        $search_code = $this->input->get('search_code') ? : '';
        $search_platform_id = $this->input->get('search_platform_id');
        $search_start_time = $this->input->get('search_start_time');
        $search_end_time = $this->input->get('search_end_time');
        if ($this->input->get('search_status') === '0'){
            $search_status = 0;
        } else {
            $search_status = $this->input->get('search_status') ? : '';
        }
        $search_equipment_id = $this->input->get('search_equipment_id') ? : '';
        $where = array();
        if ($search_code){
            $where['code'] = $search_code;
        }
        if($search_platform_id && $search_platform_id!=-1) {
            $where['platform_id'] = $search_platform_id;
        }
        if ($search_status || $search_status == 0){
            $where['status'] = $search_status;
        }
        if ($search_equipment_id){
            $where['equipment_id'] = $search_equipment_id;
        }
        if ($search_start_time){
            $where['start_time'] = strtotime($search_start_time);
        }
        if ($search_end_time){
            $where['end_time'] = strtotime($search_end_time);
        }
        $where['admin_id'] = $this->adminid;
        $array = $this->equipment_model->getEquipments("", $where, $offset, $limit);
        $total = (int)$this->equipment_model->getEquipments("count(*) as c",$where)[0]['c'];
        
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A1', 'ID')
        ->setCellValue('B1', '设备id')
        ->setCellValue('C1', '设备编码')
        ->setCellValue('D1', '状态')
        ->setCellValue('E1', '支付宝二维码')
        ->setCellValue('F1', '果园二维码')
        ->setCellValue('G1', '通用二维码')
        ->setCellValue('H1', '通用banner')
        ->setCellValue('I1', '支付宝banner')
        ->setCellValue('J1', '微信banner');
        $objPHPExcel->getActiveSheet()->setTitle('设备信息');
        
        foreach($array as $item){
            $data[] = array(
                $item['id'],
                $item['equipment_id'],
                $item['code'],
                $item['status_name'],
                $item['qr'],
                $item['qr_fruitday'],
                $item['qr_common'],
                $item['banner_common'],
                $item['banner_common_alipay'],
                $item['banner_common_wechat'],
            );
        }
        for($i=2,$j=0;$i<=$total+1;$i++){
            $objPHPExcel->getActiveSheet()
            ->setCellValue('A'.$i, $data[$j][0])
            ->setCellValue('B'.$i, $data[$j][1])
            ->setCellValue('C'.$i, $data[$j][2])
            ->setCellValue('D'.$i, $data[$j][3])
            ->setCellValue('E'.$i, $data[$j][4])
            ->setCellValue('F'.$i, $data[$j][5])
            ->setCellValue('G'.$i, $data[$j][6])
            ->setCellValue('H'.$i, $data[$j][7])
            ->setCellValue('I'.$i, $data[$j][8])
            ->setCellValue('J'.$i, $data[$j][9]);
            $j++;
        }
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->getColumnDimension('E')->setWidth(60);
        $sheet->getColumnDimension('F')->setWidth(60);
        $sheet->getColumnDimension('G')->setWidth(60);
        $sheet->getColumnDimension('H')->setWidth(60);
        $sheet->getColumnDimension('I')->setWidth(60);
        $sheet->getColumnDimension('J')->setWidth(60);

        @set_time_limit(0);
        
        // Redirect output to a client’s web browser (Excel2007)
        $filename = '设备列表';
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        
    }
    
    public function add(){
        //下级代理商及直推商户
        $platform_id = $this->platform_id;
        $agent_list = $this->agent_model->get_all_agents($platform_id);
        foreach($agent_list as $k=>$v)
        {
            $agent_list[$k]['name'] = '代理商---'.$v['name'];
            $agent_list[$k]['tag'] = 'agent';
        }
//        $commercial_list = $this->agent_model->get_commercial($platform_id);
//        foreach($commercial_list as $k=>$v)
//        {
//            $commercial_list[$k]['name'] = '商户---'.$v['name'];
//            $commercial_list[$k]['tag'] = 'commercial';
//        }
//        $list = array_merge($agent_list,$commercial_list);
        $Agent = $this->agent_model->get_own_agents($platform_id);
        if($this->svip)
        {
            $this->_pagedata['is_hidden'] = 1;
        }
        if(empty($agent_list))
        {
            $agent_list = [['id'=>-1,'name'=>'暂无可选项']];
        }
        $this->_pagedata['platform_list'] = $agent_list;
        $this->_pagedata['eq_type'] = $this->eq_type;
        $this->page('equipment/add.html');
    }

    
    public function add_noclue()
    {
        $where = array();
        $this->_pagedata['platform_list'] = $this->commercial_model->getList("*", $where);
        $this->page('equipment/add_noclue.html');
    }
    public function banner_batch(){
        $this->page('equipment/banner_batch.html');
    }
    public function save_batch_banner(){
        $equipment_ids = $_POST['equipment_ids'];
        $refer = $_POST['refer'] ? $_POST['refer'] : 'common_banner';
        $equipment_ids = str_replace('，',',',$equipment_ids);//替换中文逗号
        $result = $this->create_banner($equipment_ids, $refer);
        foreach ($result['banner']  as $value) {
            switch ($refer){
                case 'common_banner_alipay':
                    $sql = "UPDATE p_equipment SET banner_common_alipay='".$value['banner']."' WHERE equipment_id='{$value['equipment_id']}'";
                    break;
                case 'common_banner_wechat':
                    $sql = "UPDATE p_equipment SET banner_common_wechat='".$value['banner']."' WHERE equipment_id='{$value['equipment_id']}'";
                    break;
                default:
                    $sql = "UPDATE p_equipment SET banner_common='".$value['banner']."' WHERE equipment_id='{$value['equipment_id']}'";
                    break;
            }

            $this->db->query($sql);
        }
        $this->ex_banner($result['banner']);
    }

    public function banner(){
        $equipment_id = $this->uri->segment(3);
        $refer = $this->uri->segment(4) ? $this->uri->segment(4) : 'common_banner';
        $equipment_ids = trim($equipment_id);
        $result = $this->create_banner($equipment_ids, $refer);
        if(count($result['banner']) > 0){
            switch ($refer){
                case 'common_banner_alipay':
                    echo $sql = "UPDATE p_equipment SET banner_common_alipay='".$result['banner'][0]['banner']."' WHERE equipment_id='{$result['banner'][0]['equipment_id']}'";
                    break;
                case 'common_banner_wechat':
                    echo $sql = "UPDATE p_equipment SET banner_common_wechat='".$result['banner'][0]['banner']."' WHERE equipment_id='{$result['banner'][0]['equipment_id']}'";
                    break;
                default:
                    echo $sql = "UPDATE p_equipment SET banner_common='".$result['banner'][0]['banner']."' WHERE equipment_id='{$result['banner'][0]['equipment_id']}'";
                    break;
            }
            $this->db->query($sql);
            $msg = "生成成功";
        }else{
            $msg ="生成失败";
        }

        echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.$msg.'");location.href = "/equipment/index";</script></head>';
        exit;
    }

    public function create_banner($equipment_ids,$refer = 'common_banner'){
        $params = array(
            'timestamp'=>time() . '000',
            'source'    => 'platform',
            'equipment_ids'=>trim($equipment_ids),
            'refer'=>$refer
        );
        $url = RBAC_URL."apiEquipment/general_banner_batch";

        $params['sign'] = $this->create_platform_sign($params);

        $options['timeout'] = 6000;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        $result = json_decode($result['response'],1);
        return $result;
    }
    private function ex_banner($ex_data){
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '设备id')
            ->setCellValue('B1', '设备名称')
            ->setCellValue('C1', '设备编码')
            ->setCellValue('D1', '图片链接');
        $objPHPExcel->getActiveSheet()->setTitle('商品信息');


        foreach($ex_data as $eachProduct){
            $data[] = array(
                "'".$eachProduct['equipment_id'],
                $eachProduct['name'],
                $eachProduct['code'],
                $eachProduct['banner'],

            );
        }

        for($i=2,$j=0;$i<=count($data)+1;$i++){
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$i, $data[$j][0])
                ->setCellValue('B'.$i, $data[$j][1])
                ->setCellValue('C'.$i, $data[$j][2])
                ->setCellValue('D'.$i, $data[$j][3]);
            $j++;
        }

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $filename = 'banner导出列表';
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function add_batch(){
        $where = array();
        $this->_pagedata['platform_list'] = $this->commercial_model->getList("*", $where);
        $this->page('equipment/add_batch.html');
    }

    public function add_save_batch(){
        $where = array();
        $this->_pagedata['platform_list'] = $this->commercial_model->getList("*", $where);

        $equipment_ids = $_POST['equipment_ids'];
        $equipment_ids = str_replace('，',',',$equipment_ids);//替换中文逗号
        $equipments_arr = explode(',',$equipment_ids);
        $platform_id  =   isset($_POST['platform_id']) ? $_POST['platform_id'] : 0;
        $type = $_POST['type'];

        $error = "";
        $succ = "";
        foreach ($equipments_arr as $equipment_id){
            $equipment_id = trim($equipment_id);
            if(empty($equipment_id))
                continue;
            $equipment = $this->equipment_model->findByBoxId($equipment_id);
            if ($equipment){
                $error .= " {$equipment_id} 该盒子id已存在,";
            } else {
                $code = $equipment_id;
                $data = array();
                $data['code'] = $code;
                $data['type'] = $type;
                $data['equipment_id'] = $equipment_id;
                $data['status'] = 1;
                $data['platform_id'] = $platform_id;
                $data['created_time'] = time();
                $insertBox = $this->equipment_model->insertData($data);
                if ($insertBox){
                    $this->load->helper('admin_eq');
                    $rs_admin = add_eq_for_admin($equipment_id,$code,$platform_id,$type);

//                    //done 打cityboxadmin接口 插入设备
//                    $params = array(
//                        'timestamp'=>time() . '000',
//                        'source'    => 'platform',
//                        'code'=> $code,
//                        'type'=> $type,
//                        'platform_id'=>$platform_id,
//                        'equipment_id'=>$equipment_id
//                    );
//                    $url = RBAC_URL."apiEquipment/addEquipment";
//
//                    $params['sign'] = $this->create_platform_sign($params);
//
//                    $options['timeout'] = 100;
//                    $result = $this->http_curl->request($url, $params, 'POST', $options);
//                    if(json_decode($result['response'],1)['code']==200){
//                        $succ .= $equipment_id.',';
//
//                    } else {
//                        $error .= $equipment_id.json_decode($result['response'],1)['msg'].",";
////                        echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");</script></head>';
//                    }
//                    sleep(1);//curl 停留1s
                    if($rs_admin){
                        $succ .= $equipment_id.',';
                    }else{
                        $error .=$equipment_id.',';
                    }
                }
            }
        }
        if($error){
            $this->_pagedata["tips"] = $error;
            if($succ)
                $this->_pagedata["tips"] = $error.",添加成功：".$succ;
            $this->page('equipment/add_batch.html');
            exit;
        }else{
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("添加成功：'.$succ.'");location.href = "/equipment/index";</script></head>';
        }

    }

    /**
     * 顶级代理商是添加设备(也可分配)，将设备录入系统。
     *下级代理商为分配设备，当前设备id必须录入
     */
    public function add_save(){
        $equipment_id = $this->input->post('equipment_id');
        $equipment_id = trim($equipment_id);
        $platform_tag = $this->input->post('platform_tag');
        $code = $this->input->post('code');
        $type = $this->input->post('type');
        $software_time = $this->input->post('software_time');
        $hardware_time = $this->input->post('hardware_time');
        $agent_id = $this->platform_id;
        $equipment = $this->equipment_model->findByBoxId($equipment_id);
        //查看盒子code是否存在
        $codeEquipment = $this->equipment_model->findByBoxCode($code);
        //解析出商户id
        $tag = explode('|',$platform_tag);
        if($tag[1] == 'commercial')
        {
            $platform_id = $tag[0];
        }else
        {
            $last_agent = $tag[0];
        }
        //顶级代理商添加设备 上海鲜动、海星宝
        if($this->svip && $platform_tag == 0)
        {
            if($equipment)
            {
                $this->_pagedata["tips"] = "该盒子id已存在！";
                $this->add();exit;
            }
            if ($codeEquipment){
                $this->_pagedata["tips"] = "该盒子code已存在！";
                $this->add();exit;
            }
            $data = array();
            $data['code'] = $code;
            $data['type'] = $type;
            $data['equipment_id'] = $equipment_id;
            $data['status'] = 1;
            $data['platform_id'] = isset($platform_id)?$platform_id:'0';
            $data['created_time'] = time();
            $data['last_agent_id'] = $agent_id;
            $data['software_time'] = $software_time;
            $data['hardware_time'] = $hardware_time;
            $data['agent_config'] = json_encode(array($agent_id));
            $insertBox = $this->equipment_model->insertData($data);
        }else { //分配设备
                //校验
                if(empty($equipment) || empty($codeEquipment) || $equipment['last_agent_id'] != $agent_id ||$equipment['type'] != $type)
                {
                    $this->check_equipment($equipment,$codeEquipment,$agent_id,$type);
                }
                //更新equipment表
                //若为顶级代理需更新顶级代理字段
                if($this->svip)
                {
                    $data['first_agent_id'] = $agent_id;
                }
                if(!$platform_id)
                {   //分配给代理商
                    $agent_config = json_decode($equipment['agent_config'],true);
                    $agent_config[] = $last_agent;
                    $data['agent_config'] = json_encode($agent_config);
                    $data['last_agent_id'] = $last_agent;
                    $data['software_time'] = $software_time;
                    $data['hardware_time'] = $hardware_time;
                    $data['platform_id'] = 0;
                    $insertBox = $this->db->update('equipment',$data,array('equipment_id'=>$equipment['equipment_id']));
                }else
                {//分配给商户
                    $data['last_agent_id'] = $last_agent;
                    $data['platform_id'] = $platform_id;
                    $data['software_time'] = $software_time;
                    $data['hardware_time'] = $hardware_time;
                    $insertBox = $this->db->update('equipment',$data,array('equipment_id'=>$equipment['equipment_id']));
                }
        }
        //对设备有分配给商户的操作才去同步Admin后台
        if ($insertBox && $platform_id){
            //done 打cityboxadmin接口 插入设备
            $params = array(
                'timestamp'=>time() . '000',
                'source'    => 'program',
                'code'=> $code,
                'type'=> $type,
                'platform_id'=>$platform_id,
                'equipment_id'=>$equipment_id
            );

            $url = RBAC_URL."apiEquipment/addEquipment";

            $params['sign'] = $this->create_platform_sign($params);

            $options['timeout'] = 100;
            $result = $this->http_curl->request($url, $params, 'POST', $options);
            if(json_decode($result['response'],1)['code']==200){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
                exit;
            } else {
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/add";</script></head>';
                exit;
            }
            exit;

            redirect('/equipment/index');
        }else
        {
            redirect('/equipment/index');
        }
        }
        

    private function check_equipment($equipment,$codeEquipment,$agent_id,$type)
    {
        if(empty($equipment))
        {
            $this->_pagedata["tips"] = "该设备id未添加！";
        }elseif(empty($codeEquipment))
        {
            $this->_pagedata["tips"] = "该设备code未添加！";
        }elseif($equipment['last_agent_id'] != $agent_id)
        {
            $this->_pagedata["tips"] = "该设备您暂无分配权限！";
        }elseif($equipment['type'] != $type)
        {
            $this->_pagedata["tips"] = "设备类型选择不正确！";
        }
        $this->add();
        exit;
    }
    public function add_noclue_save(){
        $where = array();
        $this->_pagedata['platform_list'] = $this->commercial_model->getList("*", $where);
    
        $equipment_id = $_POST['equipment_id'];
        $equipment_id = trim($equipment_id);
        $equipment = $this->equipment_model->findByBoxId($equipment_id);
        if ($equipment){
            $this->_pagedata["tips"] = "该盒子id已存在！";
            $this->page('equipment/add_noclue.html');
        } else {
            $code = $_POST['code'];
            $type = $_POST['type'];
            $admin_id = $_POST['admin_id'];
            //查看盒子code是否存在
            $codeEquipment = $this->equipment_model->findByBoxCode($code);
            if ($codeEquipment){
                $this->_pagedata["tips"] = "该盒子code已存在！";
                $this->page('equipment/add_noclue.html');
                exit;
            }
            $platform_id  =   isset($_POST['platform_id']) ? $_POST['platform_id'] : 0;
            $data = array();
            $data['code'] = $code;
            $data['type'] = $type;
            $data['equipment_id'] = $equipment_id;
            $data['status'] = 1;
            $data['platform_id'] = $platform_id;
            $data['created_time'] = time();
            $insertBox = $this->equipment_model->insertData($data);
            if ($insertBox){
                //done 打cityboxadmin接口 插入设备
                $params = array(
                    'timestamp'=>time() . '000',
                    'source'    => 'platform',
                    'code'=> $code,
                    'type'=> $type,
                    'platform_id'=>$platform_id,
                    'equipment_id'=>$equipment_id,
                    'admin_id'=>$admin_id
                );
                $url = RBAC_URL."apiEquipment/addEquipmentnoclue";
    
                $params['sign'] = $this->create_platform_sign($params);
    
                $options['timeout'] = 100;
                $result = $this->http_curl->request($url, $params, 'POST', $options);
                if(json_decode($result['response'],1)['code']==200){
                    echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
                    exit;
                } else {
                    echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/add";</script></head>';
                    exit;
                }
                exit;
    
                redirect('/equipment/index');
            }
        }
    
    }
    
    public function edit(){
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "SELECT * FROM p_equipment WHERE id=$id";
        $info = $this->db->query($sql)->row_array();
        
        $this->_pagedata['id'] = $id;
        $this->_pagedata['info'] = $info;
        $this->_pagedata['platform_list'] = $this->commercial_model->getList("*", $where);
        $this->_pagedata['eq_type'] = $this->eq_type;
        $this->page('equipment/edit.html');
    }
    
    //编辑保存
    public function edit_save(){
        $id = $_POST['id'];
        $code = $_POST['code'];
        $type = $_POST['type'];
        $equipment_id = $_POST['equipment_id'];
        //查看盒子code是否存在
        $codeEquipment = $this->equipment_model->findByBoxCode($code,$id);
        if ($codeEquipment){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该设备code已存在！");location.href = "/equipment/edit/'.$id.'";</script></head>';
            exit;
        }
    
        $sql = "UPDATE p_equipment SET code='".$code."',type='".$type."'
         WHERE id=$id";
        $res = $this->db->query($sql);
        
        //todo打cityboxadmin接口 修改设备信息
        //done 打cityboxadmin接口 插入设备
        $params = array( 
            'timestamp'=>time() . '000',
            'source'    => 'platform',
            'code'=> $code,
            'type'=> $type,
            'equipment_id'=>$equipment_id
        );
        if ($_POST['platform_id']){
            $params['platform_id'] = $_POST['platform_id'];
        }
        $url = RBAC_URL."apiEquipment/editEquipment";
        
        $params['sign'] = $this->create_platform_sign($params);
        
        $options['timeout'] = 100;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        if(json_decode($result['response'],1)['code']==200){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
            exit;
        } else {
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/edit/'.$id.'";</script></head>';
            exit;
        }
        exit;

    }
    
    //启用
    public function start(){
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "UPDATE p_equipment SET status=1 WHERE id=$id";
        $res = $this->db->query($sql);
        $equipment = $this->equipment_model->findById($id);
        $equipment_id = $equipment['equipment_id'];
        $params = array(
            'timestamp'=>time() . '000',
            'source'    => 'platform',
            'status'=> 1,
            'equipment_id'=>$equipment_id
        );
        $url = RBAC_URL."apiEquipment/editStatus";
        
        $params['sign'] = $this->create_platform_sign($params);
        
        $options['timeout'] = 100;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        if(json_decode($result['response'],1)['code']==200){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
            exit;
        } else {
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
            exit;
        }
        exit;
        redirect('/equipment/index');
    }
    
    //停用
    public function stop(){
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "UPDATE p_equipment SET status=0 WHERE id=$id";
        $res = $this->db->query($sql);
        $equipment = $this->equipment_model->findById($id);
        $equipment_id = $equipment['equipment_id'];
        $params = array(
            'timestamp'=>time() . '000',
            'source'    => 'platform',
            'status'=> 0,
            'equipment_id'=>$equipment_id
        );
        $url = RBAC_URL."apiEquipment/editStatus";
        
        $params['sign'] = $this->create_platform_sign($params);
        
        $options['timeout'] = 100;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        if(json_decode($result['response'],1)['code']==200){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
            exit;
        } else {
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
            exit;
        }
        exit;
        redirect('/equipment/index');
    }
    
    public function qr(){
        $equipment_id = $this->uri->segment(3);
        $params = array(
            'box_id'=> $equipment_id,
            'refer'=>'alipay'
        );
        //        var_dump($params);
        $sign = $this->create_sign_cbapi($params);
        //        echo $sign.'<br>';
        
        $headers = array("sign:$sign","platform:admin");
        
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
        //增加来源 alipay/fruitday
        $refer = 'alipay';
        curl_setopt($ch, CURLOPT_URL, 'http://cityboxapi.fruitday.com/api/public_tool/create_qr_code?box_id='.$equipment_id.'&refer='.$refer);
        //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if ($result->qr_img){
            //更新db
            $sql = "UPDATE p_equipment SET qr='".$result->qr_img."'
            WHERE equipment_id='$equipment_id'";
            $res = $this->db->query($sql);
            //打接口 更新商户那设备的qr
            $params = array(
                'timestamp'=>time() . '000',
                'source'    => 'platform',
                'qr'=>$result->qr_img,
                'equipment_id'=>$equipment_id
            );
            $url = RBAC_URL."apiEquipment/editQr";
            
            $params['sign'] = $this->create_platform_sign($params);
            
            $options['timeout'] = 100;
            $result = $this->http_curl->request($url, $params, 'POST', $options);
            if(json_decode($result['response'],1)['code']==200){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
                exit;
            } else {
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
                exit;
            }
            
            $this->showJson(array('status'=>'success','qrcode'=>$result->qr_img));
        } else {
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("获取二维码失败");location.href = "/equipment/index";</script></head>';
            exit;
        }
    }

    
    public function qrcode(){
        $equipment_id = $this->uri->segment(3);
        $refer = $this->uri->segment(4);
        $params = array(
            'box_id'=> $equipment_id,
            'refer'=>$refer
        );
        $sign = $this->create_sign_cbapi($params);
        $headers = array("sign:$sign","platform:admin");


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
        //增加来源 alipay/fruitday
        curl_setopt($ch, CURLOPT_URL, 'http://cityboxapi.fruitday.com/api/public_tool/create_qr_code?box_id='.$equipment_id.'&refer='.$refer);
        //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if ($result->qr_img){
            //更新db
            if ($refer == 'fruitday'){
                $sql = "UPDATE p_equipment SET qr_fruitday='".$result->qr_img."'
                WHERE equipment_id='$equipment_id'";
            } elseif ($refer == 'common'){
                $sql = "UPDATE p_equipment SET qr_common='".$result->qr_img."'
                WHERE equipment_id='$equipment_id'";
            }

            $res = $this->db->query($sql);
            //打接口 更新商户那设备的qr
            $params = array(
                'timestamp'=>time() . '000',
                'source'    => 'platform',
                'qr'=>$result->qr_img,
                'refer'=>$refer,
                'equipment_id'=>$equipment_id
            );
            $url = RBAC_URL."apiEquipment/editQrcode";

            $params['sign'] = $this->create_platform_sign($params);

            $options['timeout'] = 100;
            $result = $this->http_curl->request($url, $params, 'POST', $options);
            if(json_decode($result['response'],1)['code']==200){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
                exit;
            } else {
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.json_decode($result['response'],1)['msg'].'");location.href = "/equipment/index";</script></head>';
                exit;
            }

            $this->showJson(array('status'=>'success','qrcode'=>$result->qr_img));
        } else {
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("获取二维码失败");location.href = "/equipment/index";</script></head>';
            exit;
        }
    }
    
    public function stock($equipment_id){
        $equipment = $this->equipment_model->findById($equipment_id);
        $boxId = $equipment['equipment_id'];
        $stock_data = $this->equipment_label_model->getStock($boxId);
        $count_all = 0;
        foreach($stock_data as $k=>$v){
            $sql = "SELECT * FROM cb_product WHERE id=".$v['product_id'];
            $info = $this->db->query($sql)->row_array();
            $stock_data[$k]['product_name'] = $info['product_name'];
            $stock_data[$k]['price'] = $info['price'];
            $count_all = $count_all + $v['count_num'];
        }
        $this->_pagedata['count_all'] = $count_all;
        $this->_pagedata['stock_data'] = $stock_data;
        $this->page('equipment/stock.html');
    }
    
    public function pandian($equipment_id){
        $equipment = $this->equipment_model->findById($equipment_id);
        $boxId = $equipment['equipment_id'];
        $params = array(
            'box_id'=> $boxId
        );
        //        var_dump($params);
        $sign = $this->create_sign_cbapi($params);
        //        echo $sign.'<br>';
        
        $headers = array("sign:$sign","platform:admin");
        
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
        //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/public_tool/create_qr_code?box_id=68805328909');
        curl_setopt($ch, CURLOPT_URL, 'https://cityboxapi.fruitday.com/api/device/stock?box_id='.$boxId);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode(json_decode($result));
        if ($result->state){
            $state = $result->state;
            $tips = $state->tips;
        } else {
            $tips = '盘点失败！';
        }
        echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.$tips.'");location.href = "/equipment/index";</script></head>';
    }
    
    
    
    public function create_sign_v2($params) {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_v2), 0, -1) . 'w');
        return $sign;
    }
    
    public function create_sign_nirvana2($params){
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_nirvana2), 0, -1) . 'w');
        return $sign;
    }
    
    public function create_sign_cbapi($params){
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_cityboxapi), 0, -1) . 'w');
        return $sign;
    }
    
    public function test(){
        $params = array(
            'orderNo'=> 'cb12345',
            'status'=> 1
        );
        //        var_dump($params);
        $sign = $this->create_sign_cbapi($params);
        //        echo $sign.'<br>';

        $headers = array("sign:$sign","platform:admin");


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
        curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/shipping/confirm_status');
        //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        var_dump($result);exit;    
    }
    
    public function test2(){
        $params = array(
            'orderNo'=> 'cb170518413456',
            'order_items'=> json_encode(array(array('innerCode'=>'20943356','count'=>5),array('innerCode'=>'21070013','count'=>2)))
        );
        $sign = $this->create_sign_cbapi($params);
        //        echo $sign.'<br>';
        
        $headers = array("sign:$sign","platform:admin");
        
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
        curl_setopt($ch, CURLOPT_URL, 'https://cityboxapi.fruitday.com/api/shipping/confirm_num');
        //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        var_dump($error);
        curl_close($ch);
        var_dump($result);
        //var_dump(curl_error($result));
        
        $result = json_decode($result);
        //var_dump($result);exit;
    }

    /*
    *展示设备在百度地图的分布情况
    */
    public function map(){
        $this->_pagedata['store_list'] = $this->equipment_model->get_store_list();

        $this->title = '设备分布概览';
        $equ_list = $this->equipment_model->getList(array('status'=>1));
        $equ_data = array();
        foreach ($equ_list as $key => $value) {
            if(!empty($value['baidu_xyz'])){
                $equ_data[$key]['name'] = $value['name'];
                $equ_data[$key]['baidu_xyz'] = $value['baidu_xyz'];
            }
        }
        $this->_pagedata ["list"] = $equ_data;
        $this->page('equipment/map.html');
    }
    
    public function ajax_eq_heart(){
        $device_id = $this->input->post('device_id');
        $sql = "SELECT * FROM cb_receive_box_log WHERE device_id='".$device_id."' and msg_type='heart' order by id desc limit 1";
        $info = $this->db->query($sql)->row_array();
        die(json_encode($info));
    }
    
    public function getAdmins(){
        $platform_id = $this->input->post('platform_id');
        //调接口获取admins
        $params = array(
            'timestamp'=>time() . '000',
            'source'    => 'platform',
            'platform_id' => $platform_id,
        );
        $url = RBAC_URL."apiAdmin/getAdmins";
        
        $params['sign'] = $this->create_platform_sign($params);
        
        $options['timeout'] = 100;
        $result = $this->http_curl->request($url, $params, 'POST', $options);
        $result = json_decode($result['response'],1);
        echo json_encode($result);
    }
    //组装设备添加
    public function add_assemble(){
        //print_r($_SERVER["REQUEST_METHOD"]);die;
        if(strtolower($_SERVER["REQUEST_METHOD"]) == 'post'){
            $params = $this->input->post();
            if($params['status']=='update'){
                $id = $params['id'];
                unset($params['status']);
                unset($params['id']);
                $row = $this->db->from('assemble_equipment')->where('id', $id)->get()->row_array();
                if(!$row){
                    redirect('equipment/assemble_list');
                }
             //验证各个配件是否已存在
                $log = true;
                foreach($params as $k=>$v){

                        if ($row[$k] != $v) {
                            $result = $this->db->from('assemble_equipment')->where($k, trim($v))->get()->row_array();
                            if ($result) {
                                $log = false;
                                break;
                            }
                        }
                }
                if($log == true){

                    $res = $this->equipment_model->update_assemble($params,$id);
                    if($res){
                        redirect('equipment/add_assemble?id='.$id.'&status=update&message=更新成功');
                    }else{
                        redirect('equipment/add_assemble?id='.$id.'&status=update&message=更新失败');
                    }
                }else{
                    redirect('equipment/add_assemble?id='.$id.'&status=update&message=更新失败');
                }
            }else{
                unset($params['status']);
                unset($params['id']);
                $log = true;
                //验证各个配件是否已存在
                foreach($params as $k=>$v){
                    if($k != "type"){  //跳过type类型的验证
                        $row = $this->db->from('assemble_equipment')->where($k,trim($v))->get()->row_array();
                        if($row){
                            $log = false;
                            break;
                        }
                    }

                }
                if($log == true){
                    $params['admin_id'] = $this->session->userdata('sess_admin_data')["adminid"];
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $row = $this->db->from('assemble_equipment')->where('equipment_id',$params['equipment_id'])->get()->row_array();
                    if($row){
                        redirect('equipment/add_assemble?message=添加失败');
                    }
                    $res = $this->equipment_model->add_assemble($params);
                    if($res){
                        redirect('equipment/assemble_list');
                    }else{
                        redirect('equipment/add_assemble?message=添加失败');
                    }
                }else {
                    redirect('equipment/add_assemble?message=1');
                }
            }


        }else{
            $status = $this->input->get('status');
            $this->_pagedata['message'] = !empty($this->input->get('message')) ? $this->input->get('message') : "";
            //status等于update为修改
            if($status == "update"){
                $this->_pagedata['status'] = 'update';
                $id = $this->input->get('id');
                $this->_pagedata['rows'] = $this->db->from('assemble_equipment')->where('id',$id)->get()->row_array();

            }
            $params = $this->input->get('params');
          //  echo $params;die;
            if($params){
                var_dump(json_decode($params,1));die;
                $this->_pagedata['rows'] = json_decode($params,1);
            }



            $this->page('equipment/add_assemble.html');
        }

    }
    public function assemble_list(){
        $this->page("equipment/assemble_list.html");
    }
    public function get_assembleinfo(){
        $equipment_id = trim($this->input->post('equipment_id'));

        $res = $this->db->from('assemble_equipment')->where('equipment_id',$equipment_id)->get()->row_array();
        if($res){
            $this->showJson(['status'=>'error']);
        }else{
            $this->showJson(['status'=>'success']);
        }
    }
    public function assemble_table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $search_type = $this->input->get('search_type');
        $search_equipment_id = $this->input->get('search_equipment_id');
        $search_box = $this->input->get('search_box');
        $search_router = $this->input->get('search_router');
        $search_monitor = $this->input->get('search_monitor');
        $search_writer = $this->input->get('search_writer');
        $search_camera = $this->input->get('search_camera');
        $search_sm_number = $this->input->get('search_sm_number');
        $start_time = $this->input->get('search_start_time');
        $end_time = $this->input->get('search_end_time');
        $sort =  'e.id';
        $order = 'desc';
        $where = ' where 1=1 ';
        if($search_type){
          $where .= ' and e.type = "'.$search_type.'"';
        }
        if($search_equipment_id){
            $where .= ' and e.equipment_id = "'.$search_equipment_id.'"';
        }
        if($search_box){
            $where .= ' and e.box like "%'.$search_box.'%" ';
        }
        if($search_router){
            $where .= ' and e.router like "%'.$search_router.'%" ';
        }
        if($search_monitor){
            $where .= ' and e.monitor like "%'.$search_monitor.'%" ';
        }
        if($search_writer){
            $where .= ' and e.writer like "%'.$search_writer.'%" ';
        }
        if($search_camera){
            $where .= ' and e.camera like "%'.$search_camera.'%" ';
        }
        if($search_sm_number){
            $where .= ' and e.sm_number like "%'.$search_sm_number.'%" ';
        }
        if($start_time){
            $where .= ' and e.create_time >= "'.$start_time.'"';
        }
        if($end_time){
            $where .= ' and e.create_time <= "'.$end_time.'"';
        }


        $rows = $this->equipment_model->assemble_table($where,$sort,$order,$offset,$limit);
        $this->db->from('assemble_equipment');
        $sql="select count(*) as c from p_assemble_equipment as e".$where;
        $total =$this->db->query($sql)->row_array();;
        $result = array("rows" => $rows, "total" => $total['c']);
        echo $this->showJson($result);
    }
    public function get_info(){
        $params = $this->input->post();
        // 有值为修改判断 没有为添加判断
        if($params['status'] == ""){
            $row = $this->db->from('assemble_equipment')->where($params['key'],trim($params['data']))->get()->row_array();
             if($row){
                 $this->showJson(['status'=>'error','msg'=>'该配件不可重复添加,请重新输入']);
             }else{
                 $this->showJson(['status'=>'success']);
             }
        }else{
            $row = $this->db->from('assemble_equipment')->where('equipment_id',trim($params['equipment_id']))->get()->row_array();
            if($row[$params['key']] != $params['data']){
                $result = $this->db->from('assemble_equipment')->where($params['key'],trim($params['data']))->get()->row_array();
                if($result){
                    $this->showJson(['status'=>'error','msg'=>'该配件不可重复添加,请重新输入']);
                }
            }
        }
    }
    //故障设备start
    public function pault_list(){
        //判断当前代理商权限
        if($this->svip)
        {
            //代理商级别
            $agent_level_list = $this->agent_model->get_agent_level_list($Agent);
            $this->_pagedata['is_svip']= 1;
        }
        $this->_pagedata['pault_code']= $this->pault_code;
        $this->_pagedata['pault_status']= $this->pault_status;
        $this->_pagedata['pault_type']= $this->pault_type;
        $this->_pagedata['scene']= $this->scene;
        $this->_pagedata['agent_level_list']= $agent_level_list;
        $this->page("equipment/pault_list.html");
    }
    public function pault_table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 20;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $search_equipment_id = $this->input->get('search_equipment_id');
        $search_pault_code = $this->input->get('search_pault_code');
        $search_pault_status = $this->input->get('search_pault_status');
        $start_time = $this->input->get('search_start_time');
        $end_time = $this->input->get('search_end_time');
        $search_pault_type = $this->input->get('search_pault_type');
        $search_scene = $this->input->get('search_scene');
        $search_commercial = $this->input->get('search_commercial');
        $sort =  'p.id';
        $order = 'desc';
        $where = ' where is_del=0 ';
        if($search_equipment_id){
            $where .= ' and p.equipment_id = "'.$search_equipment_id.'"';
        }
        if($search_scene){
            $where .= ' and p.scene = "'.$search_scene.'"';
        }
        if($search_commercial){
            $where .= ' and p.platform_id = "'.$search_commercial.'"';
        }
        if($search_pault_code){
            $where .= ' and p.pault_code = '.$search_pault_code;
        }
        if($search_pault_status){
            $where .= ' and p.pault_status = '.$search_pault_status;
        }
        if($start_time){
            $where .= ' and p.create_time >= "'.$start_time.'"';
        }
        if($end_time){
            $where .= ' and p.create_time <= "'.$end_time.'"';
        }
        if($search_pault_type){
            $where .= ' and p.pault_type = '.$search_pault_type;
        }
        $rows = $this->equipment_model->pault_table($where,$sort,$order,$offset,$limit);
        foreach($rows as $key=>$val) {
            //array_push($equipment_ids, $val['equipment_id']);
            //判断状态为1的显示天数
           // $data[$val['equipment_id']] = $val;
            if($val['make_time_end']){
                if($val['pault_status'] == 2){
                    $time = time();
                    $make_time = strtotime($val['make_time']);
                    $make_time_end = strtotime($val['make_time_end']);
                    if($make_time_end > $time){
                        $rows[$key]['pault_status'] = $this->pault_status[$val['pault_status']];
                    }else{
                        $rows[$key]['pault_status'] = '<font color="#FF0000">'.$this->pault_status[$val['pault_status']].'(越期)</font>';
                    }
                }else{
                    $rows[$key]['pault_status'] = $this->pault_status[$val['pault_status']];
                }

            }else{
                $rows[$key]['pault_status'] = $this->pault_status[$val['pault_status']];
            }


            if($val['create_admin']){
                $sql = 'select alias from s_admin where id='.$val['create_admin'];
                $rows[$key]['create_admin'] = $this->db->query($sql)->row_array()['alias'];

            }
            if($val['update_admin']){
                $sql2 = 'select alias from s_admin where id='.$val['update_admin'];
                $rows[$key]['update_admin'] = $this->db->query($sql2)->row_array()['alias'];

            }

            $rows[$key]['scene'] = $this->scene[$val['scene']];
            $rows[$key]['pault_code'] =$this->pault_code[$val['pault_code']];
            $rows[$key]['pault_type'] =$this->pault_type[$val['pault_type']];
            $rows[$key]['contacts_phone'] = $val['contacts'].$val['phone'];
            $rows[$key]['user_name_user_phone'] = $val['user_name'].$val['user_phone'];
            //获取代理商名字、商户名称
            $name_list = $this->equipment_model->get_agent_commercial_name($val['equipment_id']);
            $rows[$key]['agent_name'] = $name_list['agent_name'];
            $rows[$key]['commercial_name'] = $name_list['commercial_name'];
            $rows[$key]['hardware_time'] = $name_list['hardware_time'];
            $rows[$key]['code'] = $name_list['code'];
        }
        $total = $this->p_db->query('select count(id) as c  from p_pault as p'.$where)->row_array();

        $result = array("rows" => $rows, "total" => $total['c']);
        echo $this->showJson($result);
    }

    public function pault_add(){
        if(strtolower($_SERVER["REQUEST_METHOD"]) == 'post'){
            $params = $this->input->post();
            $row = $this->p_db->from('pault')->where('equipment_id',trim($params['equipment_id']))->where('pault_status !=',3)->where('is_del',0)->get()->row_array();
            if(!$row){

                $params['create_admin'] = $this->session->userdata('sess_admin_data')["adminid"];
                $params['create_time'] = date('Y-m-d H:i:s');
                $res = $this->equipment_model->add_assemble($params,'pault');

            }

            if($res){
                 redirect('equipment/pault_list');
            }else{
                redirect('equipment/pault_add');
            }
        }else{
            $this->_pagedata['pault_type']= $this->pault_type;
            $this->_pagedata['pault_code']= $this->pault_code;
            $this->page('equipment/pault_add.html');
        }

    }
    //根据故障类别pault_type获取相对应的pault_code所有的值
    public function get_pault_type(){
        $pault_type = $this->input->post('pault_type');
        $pault_type_code = $this->pault_type_code[$pault_type];
        $this->showJson($pault_type_code);
    }

    //查询是否有设备未维修或维修中的
    public function get_pault_equipment(){
        $equipment_id = $this->input->post('equipment_id');
        $row = $this->p_db->from('pault')->where('equipment_id',$equipment_id)->where('pault_status !=',3)->where('is_del',0)->get()->row_array();
        if($row){
            $this->showJson('error');
        }else{
            $this->showJson('success');
        }
    }
  /*
   * 故障设备编辑
   */
    public function pault_update(){
        if(strtolower($_SERVER["REQUEST_METHOD"]) == 'post'){
            $params = $this->input->post();
            $id = $params['id'];
            unset($params['id']);
            $params['update_admin'] = $this->session->userdata('sess_admin_data')["adminid"];
            $params['update_time'] = date('Y-m-d H:i:s');
            $res = $this->equipment_model->update_assemble($params,$id,'pault');
            if($res){
                redirect('equipment/pault_list');
            }else{
                redirect('equipment/pault_update?id='.$id);
            }
        }else{
            $id = $this->input->get('id');
            $this->_pagedata['rows'] = $this->db->from('pault')->where('id',$id)->get()->row_array();
            $this->_pagedata['pault_status']= $this->pault_status;
            $this->_pagedata['pault_code']= $this->pault_code;
            $this->_pagedata['pault_type']= $this->pault_type;
            $this->page('equipment/pault_update.html');
        }

    }
    public function pault_status(){
         $ids = trim($this->input->post('ids'));
         $ids_array = explode(",",$ids);
         $this->db->trans_begin();
         foreach($ids_array as $key=>$val){
             $this->db->set('pault_status',3)->set('complete_time',date('Y-m-d H:i:s'))->where('id',$val)->update('pault');
         }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->showJson(['status'=>'error']);
        } else {
            $this->db->trans_commit();
            $this->showJson(['status'=>'success']);
        }

    }
    public function pault_delete(){
        $ids = trim($this->input->post('ids'));
        $ids_array = explode(",",$ids);

        foreach($ids_array as $key=>$val){
            $this->db->set('is_del',1)->where('id',$val)->update('pault');
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->showJson(['status'=>'error']);
        } else {
            $this->db->trans_commit();
            $this->showJson(['status'=>'success']);
        }
    }
    public function pault_excel(){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        $search_equipment_id = $this->input->get('search_equipment_id');
        $search_equipment_name = $this->input->get('search_equipment_name');
        $search_pault_code = $this->input->get('search_pault_code');
        $search_pault_status = $this->input->get('search_pault_status');
        $start_time = $this->input->get('search_start_time');
        $end_time = $this->input->get('search_end_time');
        $search_pault_type = $this->input->get('search_pault_type');
        $search_scene = $this->input->get('search_scene');
        $search_commercial = $this->input->get('search_commercial');
        $sort =  'p.id';
        $order = 'desc';
        $where = ' where is_del=0 ';
        if($search_equipment_id){
            $where .= ' and p.equipment_id = "'.$search_equipment_id.'"';
        }
        if($search_scene){
            $where .= ' and p.scene = "'.$search_scene.'"';
        }
        if($search_commercial){
            $where .= ' and p.platform_id = "'.$search_commercial.'"';
        }
        if($search_pault_code){
            $where .= ' and p.pault_code = "'.$search_pault_code.'" ';
        }
        if($search_pault_status){
            $where .= ' and p.pault_status like "'.$search_pault_status.'" ';
        }
        if($start_time){
            $where .= ' and p.create_time >= "'.$start_time.'"';
        }
        if($end_time){
            $where .= ' and p.create_time <= "'.$end_time.'"';
        }
        if($search_pault_type){
            $where .= ' and p.pault_type = '.$search_pault_type;
        }
        $rows = $this->equipment_model->pault_table($where,$sort,$order,0,1000);
        $equipment_info = [];
        foreach($rows as $key=>$val) {
            //array_push($equipment_ids, $val['equipment_id']);
            //判断状态为1的显示天数
            // $data[$val['equipment_id']] = $val;
            if($val['make_time_end']){
                if($val['pault_status'] == 2){
                    $time = time();
                    $make_time = strtotime($val['make_time']);
                    $make_time_end = strtotime($val['make_time_end']);
                    if($make_time_end > $time){
                        $rows[$key]['pault_status'] = $this->pault_status[$val['pault_status']];
                    }else{
                        $rows[$key]['pault_status'] = '<font color="#FF0000">'.$this->pault_status[$val['pault_status']].'(越期)</font>';
                    }
                }else{
                    $rows[$key]['pault_status'] = $this->pault_status[$val['pault_status']];
                }

            }else{
                $rows[$key]['pault_status'] = $this->pault_status[$val['pault_status']];
            }


            if($val['create_admin']){
                $sql = 'select alias from s_admin where id='.$val['create_admin'];
                $rows[$key]['create_admin'] = $this->db->query($sql)->row_array()['alias'];

            }
            if($val['update_admin']){
                $sql2 = 'select alias from s_admin where id='.$val['update_admin'];
                $rows[$key]['update_admin'] = $this->db->query($sql2)->row_array()['alias'];

            }
            $rows[$key]['scene'] = $this->scene[$val['scene']];
            $rows[$key]['pault_code'] =$this->pault_code[$val['pault_code']];
            $rows[$key]['pault_type'] = $this->pault_type[$val['pault_type']];
            $rows[$key]['contacts_phone'] = $val['contacts'].$val['phone'];
            $rows[$key]['user_name_user_phone'] = $val['user_name'].$val['user_phone'];
            $params = array(
                'timestamp' => time() . '000',
                'source' => 'platform',
                'equipment_id' => $val['equipment_id']
            );

            //判断是否根据设备名称搜索
            if($search_equipment_name){
                $params['equipment_name'] = $search_equipment_name;
            }
            $res = $this->equipment_info($params, "equipment_info");
//            error_log(var_export($res,1),3,dirname(dirname(__FILE__)).'/a.log');
            //判断查找是是否有$search_equipment_name 有以$res未主表循环 无已$rows
            if($search_equipment_name){
                foreach($res as $k=>$v){
                    $equipment_info[] = array_merge($v,$val);
                    //print_r($val);die;
                }
            }else{
                $rows[$key]['equipment_address'] = $res[0]['equipment_address'];
                $rows[$key]['equipment_name'] = $res[0]['equipment_name'];
                $rows[$key]['equipment_admin'] = $res[0]['equipment_admin'];
            }
        }
        if($search_equipment_name){
            $result = $equipment_info;
        }else{
            $result = $rows;
        }
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $columns = [
            'A' => ['width' => 10, 'title' => '序号', 'field' => 'id',],
            'B' => ['width' => 15, 'title' => '设备代码', 'field' => 'equipment_id'],
            'C' => ['width' => 30, 'title' => '设备名称', 'field' => 'equipment_name'],
            'D' => ['width' => 10, 'title' => '设备地址', 'field' => 'equipment_address'],
            'E' => ['width' => 15, 'title' => '设备管理者', 'field' => 'equipment_admin'],
            'F' => ['width' => 15, 'title' => '故障类别', 'field' => 'pault_type'],
            'G' => ['width' => 15, 'title' => '故障代码', 'field' => 'pault_code'],
            'H' => ['width' => 20, 'title' => '故障详情', 'field' => 'pault_info'],
            'I' => ['width' => 10, 'title' => '点位联系人+电话', 'field' => 'contacts_phone'],
            'J' => ['width' => 20, 'title' => '上报时间', 'field' => 'create_time'],
            'K' => ['width' => 20, 'title' => '完成时间', 'field' => 'complete_time'],

//            'I' => ['width' => 10, 'title' => '电话', 'field' => 'phone'],
//            'J' => ['width' => 20, 'title' => '预约上门维修开始时间', 'field' => 'make_time'],
//            'K' => ['width' => 20, 'title' => '预约上门维修结束时间', 'field' => 'make_time_end'],
            'L' => ['width' => 20, 'title' => '上报人+电话', 'field' => 'user_name_user_phone'],
//            'M' => ['width' => 20, 'title' => '电话', 'field' => 'user_phone'],
            'M' => ['width' => 10, 'title' => '紧急状态', 'field' => 'urgent_status'],
            'N' => ['width' => 10, 'title' => '维修状态', 'field' => 'pault_status'],
            'O' => ['width' => 10, 'title' => '更新人', 'field' => 'update_admin'],
            'P' => ['width' => 20, 'title' => '更新时间', 'field' => 'update_time'],
            'Q' => ['width' => 40, 'title' => '故障结果', 'field' => 'pault_result'],
            'R' => ['width' => 20, 'title' => '来源', 'field' => 'scene']


        ];


        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle('故障设备' . date('YmdHis'));
        //第一行
        $line = 1;
        foreach ($columns as $k => $v) {
            $sheet->getColumnDimension($k)->setWidth($v['width']);
            $sheet->setCellValue("{$k}{$line}", $v['title']);
        }

        //第二行
        $line++;
        foreach ($result as $k => $row) {

            $sheet->setCellValue("A{$line}", $row[$columns['A']['field']]);
            $sheet->setCellValue("B{$line}", $row[$columns['B']['field']]);
            $sheet->setCellValue("C{$line}", $row[$columns['C']['field']]);
            $sheet->setCellValue("D{$line}", $row[$columns['D']['field']]);
            $sheet->setCellValue("E{$line}", $row[$columns['E']['field']]);
            $sheet->setCellValue("F{$line}", $row[$columns['F']['field']]);
            $sheet->setCellValue("G{$line}", $row[$columns['G']['field']]);
            $sheet->setCellValue("H{$line}", $row[$columns['H']['field']]);
            $sheet->setCellValue("I{$line}", $row[$columns['I']['field']]);
            $sheet->setCellValue("J{$line}", $row[$columns['J']['field']]);
            $sheet->setCellValue("K{$line}", $row[$columns['K']['field']]);
            $sheet->setCellValue("L{$line}", $row[$columns['L']['field']]);
            $sheet->setCellValue("M{$line}", $row[$columns['M']['field']]);
            $sheet->setCellValue("N{$line}", $row[$columns['N']['field']]);
            $sheet->setCellValue("O{$line}", $row[$columns['O']['field']]);
            $sheet->setCellValue("P{$line}", $row[$columns['P']['field']]);
            $sheet->setCellValue("Q{$line}", $row[$columns['Q']['field']]);
            $sheet->setCellValue("R{$line}", $row[$columns['R']['field']]);
            $line++;
        }

        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => ['rgb' => 'f9bf92']
            ],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ]
        ]);

        $sheet->getStyle('A1:R' . ($line-1))->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('argb' => '000000')
                ]
            ]
        ]);

        $objPHPExcel->initHeader('故障设备' . date('Y-m-d'));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        die;

    }
    //故障设备end
    public function replace_equipment_add(){
        $this->_pagedata['eq_type'] = $this->eq_type;
        $this->page('equipment/replace_equipment.html');
    }
    //中控设备id替换
    public function replace_equipment(){
         $equipment_id = $this->input->post('equipment_id');
         $to_equipment_id = $this->input->post('to_equipment_id');
         $to_type = $this->input->post('to_type');


        $result = $this->db->query('select equipment_id from p_equipment where equipment_id= "'.$equipment_id.'"')->row_array();
        if(!$result){

            $this->showJson(['status'=>'error','message'=>'旧设备不存在']);
        }
        $cb_to_equipemnt_id= $this->db->query('select equipment_id from p_equipment where equipment_id="'.$to_equipment_id.'"')->row_array();
        if($cb_to_equipemnt_id){
            $this->showJson(['status'=>'error','message'=>'新设备已存在']);
        }

        if(!$to_type){
            $this->showJson(['status'=>'error','message'=>'新设备类型不能为空']);
        }

        $this->db->update('equipment',array('equipment_id'=>$to_equipment_id, 'type' => $to_type,'qr'=>'','qr_fruitday'=>'','qr_common'=>'','banner_common'=>''),array('equipment_id'=>$equipment_id));
        $this->db->update('clue_equipment',array('equipment_id'=>$to_equipment_id),array('equipment_id'=>$equipment_id));
//        exit;
        $cb_db = $this->load->database('citybox_master', TRUE);
        $update_sql[] = "update cb_active_banner set box_no = REPLACE(box_no,'$equipment_id','$to_equipment_id')";
        $update_sql[] = "update cb_active_discount set box_no = REPLACE(box_no,'$equipment_id','$to_equipment_id')";
        $update_sql[] = "update cb_admin_equipment set equipment_id = '$to_equipment_id' where equipment_id='$equipment_id'";
        $update_sql[] = "update cb_bi_product set equipment_id = '$to_equipment_id' where equipment_id='$equipment_id'";
        $update_sql[] = "update cb_deliver set equipment_id = '$to_equipment_id' where equipment_id='$equipment_id'";
        $update_sql[] = "update cb_equipment set equipment_id = '$to_equipment_id', type='{$to_type}',qr='' where equipment_id='$equipment_id'";
        $update_sql[] = "update cb_equipment_label set equipment_id = '$to_equipment_id' where equipment_id='$equipment_id'";
        $update_sql[] = "delete from cb_equipment_qr where equipment_id='$equipment_id'";
        $update_sql[] = "update cb_order set box_no = '$to_equipment_id' where box_no='$equipment_id'";
        $update_sql[] = "update cb_order_refund set box_no = '$to_equipment_id' where box_no='$equipment_id'";
        $update_sql[] = "update cb_order_sale set box_no = '$to_equipment_id' where box_no='$equipment_id'";

        $update_sql[] = "update cb_shipping_order set equipment_id = '$to_equipment_id' where equipment_id='$equipment_id'";
        $update_sql[] = "update cb_shipping_permission set equipment_id = '$to_equipment_id' where equipment_id='$equipment_id'";

        $update_sql[] = "update cb_user set register_device_id = '$to_equipment_id' where register_device_id='$equipment_id'";
        $sess_admin_data = $this->session->userdata('sess_admin_data');

        $update_sql[] = "insert into cb_equipment_op_log (from_eq_id,to_eq_id,op_time,admin_id) VALUE ('$equipment_id','$to_equipment_id','".date("Y-m-d H:i:s")."',".$sess_admin_data['adminid'].")";

        foreach($update_sql as $v){
            $cb_db->query($v);
        }
        $this->showJson(['status'=>'success','message'=>'替换成功']);
    }

    /**
     * 切换为商户
     */
    public function change_platform()
    {
        $agent_id = $this->input->post('agent_id')?$this->input->post('agent_id'):$this->platform_id;
        $type_id = $this->input->post('type_id');
        $id_string = "'";
        //获取商户
        if($type_id == 0)
        {
            if($this->svip)
            {
                $high_agent_list = $this->agent_model->high_agent_list($agent_id);
                if(!empty($high_agent_list))
                {
                    $ids = array_column($high_agent_list, 'id');
                    $id_string .= implode("','",$ids)."','";
                }
            }
            $id_string .= $agent_id."'";
            $data = $this->agent_model->change_platform($id_string);
        }else
        {//获取代理商
            $data = $this->agent_model->get_all_agents($agent_id);
        }
        $this->showJson(['status'=>'success','data'=>$data]);
    }
}
