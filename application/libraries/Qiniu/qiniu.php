<?php
// CI七牛云存储调用
// 蔡昀辰 2015
// 使用:
// $params['accessKey'] = 'eQjRZFLFzK8Q031o5SYXsTtxO5anOGD3W7oQp0d3';
// $params['secretKey'] = 'JHoVnaeZ-wL1b7qQtJUL-OGkOWMMpBtI9RHzcHy1';
// $params['bucket']    = 'test';
// $this->load->library('Qiniu/qiniu', $params);
// $this->qiniu->put('my_first_pic', '/tmp/girl.jpg');
require 'Auth.php';
require 'Config.php';
require 'Etag.php';
require 'functions.php';
require 'Http/Client.php';
require 'Http/Error.php';
require 'Http/Request.php';
require 'Http/Response.php';
require 'Processing/Operation.php';
require 'Processing/PersistentFop.php';
require 'Storage/BucketManager.php';
require 'Storage/FormUploader.php';
require 'Storage/ResumeUploader.php';
require 'Storage/UploadManager.php';

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class qiniu {

	var $token;
	
	// params:
	// accessKey: 	AK
	// secretKey: 	SK
	// bucket: 		空间名称
	function __construct ($params) {
		if (!$params['accessKey'])
			throw new Exception("需要七牛accessKey参数");
		if (!$params['secretKey'])
			throw new Exception("需要七牛secretKey参数");	
		
		$auth = new Auth($params['accessKey'], $params['secretKey']);

		if(!$auth)
			throw new Exception("验证失败");
		if (!$params['bucket'])
			throw new Exception("需要七牛bucket参数");

		$this->token = $auth->uploadToken($params['bucket']);

		if (!$this->token)
			throw new Exception("七牛Token获取失败");
	}

	// $filePath 	要上传文件的本地路径(包括文件名)
	// $key      	上传到七牛后保存的文件名
	function put($key, $filePath) {

		$uploadMgr = new UploadManager();
		list($res, $err) = $uploadMgr->putFile($this->token, $key, $filePath);

		if ($err !== null) 
		    throw $err;
		else 
		    return true;
		  
	}

}