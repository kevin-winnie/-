<?php
define('SMARTY_DIR', APPPATH.'libraries/Smarty/libs/');
require_once(SMARTY_DIR .'Smarty.class.php');
class MY_Controller extends CI_Controller
{
    private $sSource = 'pc';
	protected $_pagedata = array();
	protected $detail_path  = "images";
	protected $thumb_size = "320";
	const IS_OPEN_LOCK = TRUE;

	public function __construct()
	{
		parent::__construct();
		register_shutdown_function(function(){
            foreach(get_object_vars($this) as $key => $val) {
                if(substr($key, 0, 3) == 'db_' && is_object($this->{$key}) && method_exists($this->{$key}, 'close')) {
                    $this->{$key}->close($key);
                }
                if(substr($key, 0, 5) == 'conn_'  && is_resource($this->{$key})) {
                    $this->db->_close($val);
                    unset($this->{$key});
                }
            }
        });
		$this->commonData = $this->menu();
		$this->detail_path .=  "/".date("Y-m-d");
		if($this->uri->segment(2)!="logout"&&$this->uri->segment(2)!="login"){
		            $this->checkLogin();
		}

		$this->Smarty = new Smarty;
		$this->Smarty->template_dir = APPPATH.'views';
		$this->Smarty->compile_dir = APPPATH.'templates_c/';
		$this->Smarty->left_delimiter = '<{';
		$this->Smarty->right_delimiter = '}>';
		$this->Smarty->addPluginsDir( APPPATH.'plugins/smarty')  ;

		$sess_admin_data = $this->session->userdata('sess_admin_data');

		//普通帐号没法
		if ($sess_admin_data['grade'] == 3){
			$uri = '/'.$this->uri->uri_string;
			$options = $this->function_class->getModulesXml("OptionList");
			$node_value = '';
			foreach($options as $option){
				$url = $option->getAttribute("url");
				//获取当前url对应的数字value
				if ($url == $uri){
					$node_value = $option->getAttribute("value");
				}
			}
			$adminflag = $sess_admin_data['adminflag'];

			if (!in_array($node_value,$adminflag) && $node_value != ''){
				echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("没有权限访问该页面！");window.top.location.href="/"</script></head>';exit;
			}
		}

		$this->operation_name = $sess_admin_data['adminname'];
		$this->adminid = $sess_admin_data['adminid'];
		$this->platform_id = $sess_admin_data['platform_id'];
		$this->box_num = intval($sess_admin_data['box_num']);


	}

	public function page($view)
	{
		foreach ((array) $this->_pagedata as $key => $value) {
			$this->Smarty->assign($key,$value);
		}

		$main = $this->Smarty->fetch($view);unset($this->_pagedata);

		$this->Smarty->assign('main',$main);

		// $workgroup = isset($this->workgroup) ? $this->workgroup : strtolower(get_class($this));
		if(self::IS_OPEN_LOCK==true){
			$this->load->model('Admin_model');
			$adminid =  $this->session->userdata('sess_admin_data')['adminid'];
			$adminres = $this->Admin_model->getUser($adminid);
			$is_lock = $adminres->is_lock;
			if($is_lock == 1){
				$this->commonData['menuArr'] = array();
				$this->title = '为了您的帐户安全，请先修改密码！！！<a  href="/admin/changepwd">点击修改密码</a>';
			}
		}


		foreach ($this->commonData['menuArr'] as $key => $value) {
			if ($value['workgroup'] == $this->workgroup) {
				$children = $value['child'];
				foreach ($children as $k => $v) {
					$children[$k]['active'] = false;
					$uri = ltrim($v['url'],'/');

					if(isset($this->currwork) && $this->currwork==$uri){
						$children[$k]['active'] = true;
					}else{
						$uri_arr = explode("/", $uri);
						$ck_uri_arr = explode("/", $this->uri->uri_string());

						$second_uri_arr = $this->_initUri($uri_arr, $ck_uri_arr);
						if($second_uri_arr['second_uri'] == $second_uri_arr['ck_second_uri'] && count($uri_arr)>=2 && count($ck_uri_arr)>=2){
							$children[$k]['active'] = true;
						}
					}
				}
				$this->Smarty->assign('sidebar',$children);
				$this->commonData['menuArr'][$key]['active'] = 1;
				break;
			}
		}

		foreach ($this->commonData as $key => $value) {
			$this->Smarty->assign($key,$value);
		}

		if (isset($this->title)) {
			$this->Smarty->assign('title',$this->title);
		}

		$this->Smarty->display('page.html');
	}

	//o2o门店商品展示，脱离框架
	public function bill_page($view){
		foreach ((array) $this->_pagedata as $key => $value) {
			$this->Smarty->assign($key,$value);
		}
		unset($this->_pagedata);
		if(self::IS_OPEN_LOCK==true){
			$this->load->model('Admin_model');
			$adminid =  $this->session->userdata('sess_admin_data')['adminid'];
			$adminres = $this->Admin_model->getUser($adminid);
			$is_lock = $adminres->is_lock;
			if($is_lock == 1){
				$this->commonData['menuArr'] = array();
				$this->title = '为了您的帐户安全，请先修改密码！！！<a  href="/admin/changepwd">点击修改密码</a>';
			}
		}

		$this->Smarty->display($view);

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author
	 **/
	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
	{
		foreach ((array) $this->_pagedata as $key => $value) {
			$this->Smarty->assign($key,$value);
		}

		$this->Smarty->display($template,$cache_id,$compile_id,$parent);
	}


	public function checkLogin()
    	{
		$sess_admin_data = $this->session->userdata('sess_admin_data');
		if (isset($sess_admin_data['adminid']))
        		{
			if((time()-$sess_admin_data['adminTimestamp']) < (7200))
            			{
				$sess_admin_data['adminTimestamp'] = time();
				$this->session->set_userdata('sess_admin_data',$sess_admin_data);

				$this->_pagedata['adminname'] = $sess_admin_data['adminname'];
				return true;
			}else{
				echo "<script>window.top.location.href='/admin/logout'</script>";exit;
			}
		}
		echo "<script>window.top.location.href='/admin/login'</script>";exit;
	}

	public function menu(){
		$modules = $this->function_class->getModulesXml("ModulesList");
		$options = $this->function_class->getModulesXml("OptionList");
		$flags = $this->session->userdata('sess_admin_data')["adminflag"];
		$isTop = ($this->session->userdata('sess_admin_data')["adminname"]=='admin') ? true : false;
		$isFirst = ($this->session->userdata('sess_admin_data')["adminfirst"]=='1') ? true : false;
		$menuArr = array();
		foreach ($modules as $module)
		{
			$tempOption = "";
			$mid = $module->getAttribute("value");
			$workgroup = $module->getAttribute("workgroup");
			$name = $module->nodeValue;
			foreach($options as $option)
			{
				$target = "MainFrame";
				$type = $option->getAttribute("type");
				if($type == $mid)
				{
					$oid = $option->getAttribute("value");
					$url = $option->getAttribute("url");
					$oname = $option->nodeValue;
					if($oid==94 or $oid==95){
						continue;
					}

					if(!empty($flags)){
						if(in_array($oid, $flags) || $isTop==true)
						{
							$menuArr[$mid]['name'] = $name;
							$menuArr[$mid]['workgroup'] = $workgroup;
							$menuArr[$mid]['child'][] = array('url'=>$url,'name'=>$oname);
						}
					}
				}
			}
		}
		$modules = null;
		$options = null;

		$data ["menuArr"] = $menuArr;
		$data ["adminalias"] = $this->session->userdata('sess_admin_data')["adminalias"];
		return $data;
	}

	/**
	* 默认跳转操作 支持错误导向和正确跳转
	* 调用模板显示 默认为public目录下面的success页面
	* 提示页面为可配置 支持模板标签
	* @param string $message 提示信息
	* @param Boolean $status 状态
	* @param string $jumpUrl 页面跳转地址
	* @access private
	* @return void
	*/
	private function dispatchJump($message,$status=1,$jumpUrl='') {
		if(!empty($jumpUrl)) $data['jumpUrl'] = $jumpUrl;
		// 提示标题
		$data['msgTitle'] = $status? "成功": "失败";
		if($status) { //发送成功信息
			$data['message'] = $message;// 提示信息
			// 成功操作后默认停留1秒
			$data['waitSecond'] = '1';
			// 默认操作成功自动返回操作前页面
			if(!isset($data['jumpUrl'])) $data["jumpUrl"] = $_SERVER["HTTP_REFERER"];
			$this->load->view($this->config->item('TMPL_ACTION_SUCCESS'),$data);
		}else{
			$data['error'] = $message;// 提示信息
			//发生错误时候默认停留3秒
			$data['waitSecond'] = '3';
			// 默认发生错误的话自动返回上页
			if(!isset($data['jumpUrl'])) $data["jumpUrl"] = "javascript:history.back(-1);";
			$this->load->view($this->config->item('TMPL_ACTION_ERROR'),$data);
		}
	}
	/**
	* 操作错误跳转的快捷方法
	* @access protected
	* @param string $message 错误信息
	* @param string $jumpUrl 页面跳转地址
	* @return void
	*/
	protected function error($message='',$jumpUrl='') {
		$this->dispatchJump($message,0,$jumpUrl);
	}
	/**
	* 操作成功跳转的快捷方法
	* @access protected
	* @param string $message 提示信息
	* @param string $jumpUrl 页面跳转地址
	* @return void
	*/
	protected function success($message='',$jumpUrl='') {
		$this->dispatchJump($message,1,$jumpUrl);
	}

	/**
	 * undocumente
	 *
	 * @return void
	 * @author
	 **/
	public function splash($status,$msg='',$data=array())
	{
		$output = array(
			'status' => $status,
			'msg' => $msg,
			'data' => $data,
			);

		echo json_encode($output);exit;
	}

	protected function _finder_filter($post)
	{

		return $filter = array();
	}

	public function finder($filter = array())
	{
	    $sechma = $this->get_model()->get_sechma();

	    // $filter = array();
		$post = $this->input->post();

	    $idSrc = $sechma['index']['pkey'];

	    $cols = $idSrc ? array($idSrc) : array();
	    foreach ($sechma['columns'] as $key => $value) {
	        if (!$value['hidden']) {
	            $cols[] = $key;
	        }
	    }

	    $cols = $cols ? implode(',',$cols) : '*';

		$draw   = $post['draw'] ? $post['draw'] : 1;
		$length = $post['length'] ? $post['length'] : -1;
		$start  = $post['start'] ? $post['start'] : 0;

	    $rows = $this->get_model()->getList($cols,$filter,$start,$length);

	    foreach ($rows as $key => $value) {
	    	$rows[$key]['DT_RowId'] = $value[$idSrc];
	    }

	    $total = $this->get_model()->count($filter);

	    $data = array(
			'data'            => $rows,
			'draw'            => $draw,
			'recordsTotal'    => $total,
			'recordsFiltered' => $total,
	    );
	    echo json_encode($data);exit;
	}

	/**
	 * 删除操作
	 *
	 * @return void
	 * @author
	 **/
	public function remove()
	{
	    $model = $this->model;

	    $pos = strrpos($model, '/');
	    $object_name = false !== $pos ? substr($model, $pos+1) : $model;

	    $this->load->model($model);

	    $sechma = $this->{$object_name}->get_sechma();

	    $cols = array();$idSrc = 'id';
	    foreach ($sechma as $key => $value) {
	        if ($value['findercol']) {
	            $cols[] = $key;
	        }

	        if ($value['primary']) {
	            $cols[] = $key;
	            $idSrc = $key;
	        }
	    }

		$post = $this->input->post();

		$affected_rows = 0;
		if($post['id'])
				$affected_rows = $this->{$object_name}->delete(array($idSrc=>$post['id']));

		$data = array(
		    'status' => $affected_rows === false ? false : true,
		    'msg' => $affected_rows === false ? '删除失败' : '删除成功',
		);
		echo json_encode($data); exit;

	}

	/**
	 * 数据验证
	 *
	 * @return void
	 * @author
	 **/
	protected function validform($post)
	{
	    $sechma = $this->get_model()->get_sechma();

		// 数据验证
		$error = array();
		foreach ($sechma['columns'] as $col => $value) {
			if ($value['required'] && !$post['data'][$col]) {
				$error[] = array('name'=>$col,'status'=>$value['label'].'必填');
			}
		}

		return $error;
	}

	public function save()
	{

		$post = $this->input->post();

		$error = $this->validform($post);

		if ($error) {
			echo json_encode(array('fieldErrors'=>$error));exit;
		}

	    $sechma = $this->get_model()->get_sechma();

	    $idSrc = 'id';
	    foreach ($sechma as $key => $value) {
	        if ($value['primary']) {
	            $idSrc = $key;break;
	        }
	    }

		if (!$post['id']) {
			$rs = $this->get_model()->insert($post['data']);

			if ($rs) {
				echo json_encode(array('succ'=>'保存成功'));
			} else {
				echo json_encode(array('error'=>'保存失败'));
			}
			exit;
		}  else {
			$rs = $this->get_model()->update($post['data'],array($idSrc=>$post['id']));

			if ($rs === false) {
				echo json_encode(array('error'=>'更新失败'));
			} else {
				echo json_encode(array('succ'=>'更新成功'));
			}
			exit;
		}
	}

	public function details()
	{
		echo '';exit;
	}

	protected function get_model()
	{
	    $pos = strrpos($this->model, '/');
	    $modelObj = false !== $pos ? substr($this->model, $pos+1) : $this->model;

	    $this->load->model($this->model);

		return $this->{$modelObj};
	}

	protected function _initUri($uri_arr, $ck_uri_arr){
		if(isset($uri_arr[2]) && isset($ck_uri_arr[2])){
			$second_uri = $uri_arr[0]."/".$uri_arr[1]."/".$uri_arr[2];
			$ck_second_uri = $ck_uri_arr[0]."/".$ck_uri_arr[1]."/".$ck_uri_arr[2];
		}else{
			$second_uri = $uri_arr[0]."/".$uri_arr[1];
			$ck_second_uri = $ck_uri_arr[0]."/".$ck_uri_arr[1];
		}

		return array(
				'second_uri'=>$second_uri,
				'ck_second_uri'=>$ck_second_uri,
			);
	}

	//保存图片
	protected function savePhoto($pname="photo"){
		$photo = "";
		$thumbs = "";
		if(!empty($_FILES[$pname]['size'])){
			$config['upload_path'] = $this->config->item('WEB_BASE_PATH').$this->detail_path;
			$config['allowed_types'] = 'gif|jpg|png|jpeg';
			$config['encrypt_name'] = true;
			$config['openS3'] = OPEN_S3;
			$this->load->library('upload', $config);

			if(!is_array($_FILES[$pname]['size'])){
				if ( ! $this->upload->do_upload($pname)){
					return array('code'=>300,'msg'=>$this->upload->display_errors());
				}
				$image_data[] = $this->upload->data();
			}else{
				$multiple_file = $this->upload->multiple($pname);
				if ( ! $multiple_file){
					return array('code'=>300,'msg'=>$this->upload->display_errors());
				}
				$image_data = $multiple_file['files'];
			}

			if(!empty($image_data)){
				$this->load->library('image_lib');
				if(OPEN_S3){
					$s3_url = 'http://fdaycdn.fruitday.com/';
				}else{
					$s3_url = '';
				}
				foreach($image_data as $val){
					$curr_image_info = pathinfo($val['file_name']);
					$thumb_image_info = $curr_image_info['filename']."_thumb";
					$thumb_photo =  $thumb_image_info.".".$curr_image_info['extension'];
					$thumb_config['image_library'] = 'gd2';
					$thumb_config['source_image'] = $s3_url.$config['upload_path']."/".$val['file_name'];;
					$thumb_config['create_thumb'] = TRUE;
					$thumb_config['maintain_ratio'] = TRUE;
					$thumb_config['width'] = $this->thumb_size;
					$thumb_config['height'] = $this->thumb_size;
					$thumb_config['openS3'] = OPEN_S3;
					$this->image_lib->initialize($thumb_config);
					if ( ! $this->image_lib->resize())
					{
						return array('code'=>300,'msg'=>$this->image_lib->display_errors());
					}
					$photo_arr[] = $this->detail_path."/".$val['file_name'];
					$thumbs_arr[] = $this->detail_path."/".$thumb_photo;
				}
				$photo = join(",",$photo_arr);
				$thumbs = join(",",$thumbs_arr);
			}
		}
		$img_name_arr["photo"] = $photo;
		$img_name_arr["thumbs"] = $thumbs;
		return array('code'=>200,'msg'=>$img_name_arr);
	}

	//保存APP广告图片
	protected function saveAppPhoto($pname="photo",$filename = ''){
		$app_banner_farm = $this->config->item('APP_BANNER_FARM');

		if(!empty($_FILES[$pname]['size'])){
            $config['upload_path'] = $app_banner_farm;
			$config['allowed_types'] = 'gif|jpg|png';
			$config['encrypt_name'] = true;
			$config['openS3'] = OPEN_S3;
			$this->load->library('upload', $config);

			if (!$this->upload->do_upload($pname,$filename)){
				return array('code'=>300,'msg'=>$this->upload->display_errors());
			}
			$image_data = $this->upload->data();
			if(!OPEN_S3){
			 rename($app_banner_farm.$image_data['file_name'], $app_banner_farm.'app_active.jpg');
			}

		}
		return array('code'=>200,'msg'=>'上传成功','data'=>'app_active.jpg');
	}

	public function _initFilter($filter){
		$res = array(
			'where'=>array(),
			'like'=>array(),
		);
		if(!empty($filter)){
			foreach($filter as $tb_name=>$tb_arr){
				if(!empty($tb_arr)){
					foreach($tb_arr as $k=>$v){
						switch ($k) {
							case 'eq':
								$res['where'][$tb_name] = $v;
								break;
							case 'cnt':
								$res['like'][$tb_name] = $v;
								break;
							default:
								# code...
								break;
						}
					}
				}
			}
		}
		return $res;
	}

    /**
     * @param        $module  模块id 商品模块:1
     * @param        $index   相关id
     * @param        $operation 操作 c:创建 u:更新 d:删除n
     * @param        $column  所更新的数据库字段
     * @param        $after 更新后的值
     * @param string $before 更新前的值
     * @param int    $parent 父id 如:sku父id为商品id
     *
     * @return mixed
     */
    public function writeLog($module, $index, $operation, $column, $after, $before = '', $parent = 0){
        $this->load->model('rbac_log_model');
        return $this->rbac_log_model->write_log($module, $index, $operation, $column, $after, $before, $parent );
    }

    final function create_sign($params){
        ksort($params);//以键升序排列
        $query = "";
        foreach($params as $k=>$v){
            $query .= $k."=".$v."&";
        }//拼接成get字符串
        $sign = md5(substr(md5($query.API_SECRET), 0,-1)."w");
        //字符串拼接密钥后md5加密,      去处最后一位再拼接"w"，再md5加密
        return $sign;
    }

    /**
     * @desc 发送json数据
     */
    public function showJson($data)
    {
        $data = is_null($data) ? $this->_json : $data;
        echo json_encode($data);
        die();
    }

    protected function setAPI($sService, $aParams)
    {
        $this->sService = $sService;
        $this->aParams = $aParams;
    }

    protected function setSource($sSource)
    {
        $this->sSource = $sSource;
    }

    protected function getApiContent($timeout = 10, $data = [], $headers = null)
    {
        $this->aParams['service'] = $this->sService;
        $this->aParams['source'] = $this->sSource;
        $this->aParams['version'] = '1.0';
        $this->aParams['timestamp'] = time();
        $this->aParams['sign'] = $this->getSign($this->aParams);

        if (!is_array($data)) {
            return null;
        } else {
            $data = array_merge($this->aParams, $data);
        }

        $ch = curl_init(API_URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE );
        curl_close($ch);

        if ($httpcode >= 400) {
            return null;
        }

        $content = trim($content);
        return json_decode($content, true);
    }

    protected function getSign($params)
    {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }

        ksort($params);
        $query = '';

        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }

        $sign = md5(substr(md5($query . API_SECRET), 0, -1) . 'w');

        return $sign;
    }



    /**
     * 获得性别列表。
     * @return array
     */
    protected function getSexList()
    {
        $sex = [
            0 => '未知',
            1 => '男',
            2 => '女'
        ];

        return $sex;
    }

    public function get_api_content( $params, $url, $is_post=1){
        $sign = $this->getCitySign($params);
        $headers = array("sign:$sign","platform:admin");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if($is_post){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, CITY_BOX_API.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }



    protected function getCitySign($params)
    {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . CITY_API_SECRET), 0, -1) . 'w');
        return $sign;
    }

    protected function create_platform_sign($params)
    {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . PLATFORM_SECRET), 0, -1) . 'P');
        return $sign;
    }

    protected function create_platform_host_sign($params)
    {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . PLATFORM_HOST_SECRET), 0, -1) . 'P');
        return $sign;
    }

    protected $money = array();

    /*
     * @desc 计算商品价值 占总价值的比例
     * @param $data array 订单商品数组
     * @param $good_money float 订单商品总价
     * @param $modou int    魔豆
     * @param $yue   float  余额
     * @param $money float  实付金额
     * @return array
     * */
    public function get_proportion($data, $good_money, $modou, $yue, $money){
        $this->money = array('modou'=>$modou, 'yue' =>$yue, 'money'=>$money);
        foreach($data as $k=>$v){
            $last_money =  bcsub($v['total_money'], bcadd($v['dis_money'], $v['card_money'], 2), 2);
            $data[$k]['modou_money']  = $this->get_money('modou', $last_money , $good_money, $modou);
            $data[$k]['yue_money']    = $this->get_money('yue', $last_money, $good_money, $yue);
            //剩余就是在线支付金额
            $max_money = bcsub($last_money, bcadd($data[$k]['modou_money'], $data[$k]['yue_money'], 2), 2);
            if(bccomp($max_money, $money, 2)>0){//对比最后一分钱
                $max_money = $money;
            }
            if($max_money<0){
                $max_money = number_format(0,2);
            }
            $data[$k]['really_money'] = $max_money;
        }
        return $data;
    }


    public function get_money($type, $one, $all, $original){
        if($this->money[$type]>0){
            $tmp_money = bcdiv(bcmul($one, $original, 3), $all, 2);//商品金额除以最低金额，得到比例，计算本次单个商品优惠
            $this->money[$type] = bcsub($this->money[$type], $tmp_money, 2);//得出剩余的优惠额度
            if ($this->money[$type] > 0 && $this->money[$type] < 0.05) {
                $tmp_money = bcadd($tmp_money, $this->money[$type], 2);//最后一分钱  给最后一个商品
            }
            return $tmp_money;
        }else{
            return number_format(0,2);
        }
    }
}
