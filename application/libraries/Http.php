<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
HTTP通讯类

author: 
	蔡昀辰 2015-12-31

usage:
	$this->load->library('http');

	$resp = $this->http->req([
		"url"    =>"http://120.26.72.185:83/v1/send/sms?source=test&sign=aaa",
		"method" =>"POST",
		"params" =>'{
			"a":1,
			"b":2
		}'
	]);
	print_r( $resp->toArray() );
*/

class Http {

	var $ci;
	var $error;
	var $info;
	var $statusCode;
	var $opts = [
		"user_agent"=>"Codeignitor 3.0",
		"headers"=>[],
	];

	function __construct($opt = []) {
		$this->ci = &get_instance();
	}

	function __toString() {
		if(strlen($this->error) > 0)
			return $this->error;
		else
			return $this->resp;
	}

	// 发送请求
	public function req($opts) {

		// 处理下参数
		$opts = array_merge($this->opts, $opts);

		// 发送
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $opts["url"]);
		curl_setopt($curl, CURLOPT_POST, strtoupper($opts["method"]) == "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $opts["params"]);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $opts["headers"]);

		$this->resp       = curl_exec($curl);
		$this->info       = curl_getinfo($curl);
		$this->error      = curl_error($curl);

		$this->statusCode = $this->info['http_code'];
		curl_close($curl);
		return $this;
	}

	public function toArray() {
		return json_decode($this->resp, true);
	}

}
