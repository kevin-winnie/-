<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * @author [chenjy]
 * 小米生活
 * 签名校验、参数rsa解密等等 
 */
class Xiaomi{
	public $secret_key = "J41Mo7n2rwnyVEKXoS0qVg==";//something to do
	public $secret_id = "2882303761517229886";

	function __construct()
	{
          		$this->CI = & get_instance(); 
          		$this->CI->load->library("Rsautils");
	}

	//验证签名
	function verify_sign($data){
		if(1 >= count($data)){
			return false;
		}

		$sign = $this->generate_sign($data);
		if($data['sign'] != $sign){
			return false;
		}
		return true;
	}
	//解密参数
	function decrypt_data($data){
		$json_return = $this->CI->rsautils->rsa_slice_decrypt($data);
		$return = json_decode($json_return,true);
		return $return;
	}
	//加密参数
	function encrypt_data($data){
		$json_data = json_encode($data);
		$return = $this->CI->rsautils->rsa_slice_encrypt($json_data);
		return $return;
	}
	//生成签名
	function generate_sign($data, $secret_key = '') {
		$secret_key = $secret_key ? $secret_key : $this->secret_key;
		ksort($data);

		$sign = '';
		foreach ($data as $k => $v) {
			if($k != "sign"){
				$sign .= "$k=$v&";
			}
		}
		unset($k, $v);
		$sign .= "secretKey=" . $secret_key;
		$sign = strtoupper(md5($sign));
		return $sign;
	}
}
?>
