# CI七牛云存储调用
在Codeigniter 2中测试过，Codeigniter 3应该也能用


使用:


	$params['accessKey'] = 'eQjRZFLFzK8Q031o5SYXsTtxO5anOGD3W7oQp0d1';
	$params['secretKey'] = 'JHoVnaeZ-wL1b7qQtJUL-OGkOWMMpBtI9RHzcHy3';
	$params['bucket']    = 'test';

	$this->load->library('Qiniu/qiniu', $params);
	$this->qiniu->put('my_first_pic', '/tmp/girl.jpg');

