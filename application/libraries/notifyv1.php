<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// 天天果园 通知中心v1 文档：http://notify.fruitday.com
/*

	example1:
	$this->load->library("notifyv1");

	$params = [
		"mobile"  => 18621681531",
		"messeage"=>"api推送测试"
	];

	echo $this->notifyv1->send('sms', 'send', $params);

	example2:
	$this->load->library("notifyv1");

	$params = [
		"openid"  => ["obGvfjno2qwDL7hTjjePEEVI8C58","obGvfjno2qwDL7hTjjePEEVI8C58"],
		"template_name"=>"未付款订单通知"
	];

	echo $this->notifyv1->send('weixin', 'group', $params);

*/
class notifyv1 {

	var $source = 'rbac';
	var $url    = "http://notify.fruitday.com"; // prod 10.168.126.48
	// var $url = "http://120.26.72.185:83"; // dev

	function __construct($params = []) {
		$this->ci = &get_instance();
	}

	public function send($type = 'sms', $method = 'send', Array $params, $version = 'v1') {

		$sign = $this->sign(json_encode($params));

		$opts = [
			'http'=>[
				'method'=>"POST",
				'content' => json_encode($params),
			]
		];

		$context = stream_context_create($opts);
		$url     = "{$this->url}/{$version}/{$type}/{$method}?source={$this->source}&sign={$sign}";

		return file_get_contents($url, false, $context);

    }	

    private function sign($params) {
        return md5(substr(md5($params.SMS_SECRET), 0,-1).'s');
    }

}