<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 
class Rsautils {
	public $private_key =<<<EOT
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANnEM0BtuSDb0CQy
EOwcpgdf+2dnbJKune/u8VMS9CKOPsdOxoGyqqIzp8PB6Qg+N2vaTw2UZXazg3bb
et3RBTjKWJ0UFA7bppOhLcsCLrmdsmq0jDA/2JUSxIn03qZ85I5PDDp0CV0CVCnt
JXKopZ8GxE9LNXhzNfG3EGJGYNr3AgMBAAECgYBTCx8AUtdmUGzvYKhTYhludovI
wwgEZ9KSzOv6Yh/3jYcjOkc47oljkY9Id4oGOrOnzXiYFR8cRRi9GrWgITV73hdg
8PeMmUGlfRbnm1fx+/8hJ0EBFMPzSW4PtB2kb0bvrf7WUbPaS1gMj5t2ijh1RC1b
ckjEJSlDyQIsZwU/AQJBAP6JKZfxWnQzw3ryPSS8U5qAC3q+Vg7EJW/EcdNKj8Id
J9o8Uj2W5zxkYU8AmeA1HE31PDi2QxCTRqyncClAho0CQQDbBOPUYvm468dP5fRG
V7YdknEwRrg09jKIG7LSxf0HsR8c0SyM4Mz44hmAj6RHd2Y9NQT7Llf84n6aPxkG
p/iTAkB4azHHVYLCqN6ZYtL0dzhiRqOnrTaPg9JmPxzOpl6+qgZ5o8IQqzy4gJDc
zF8ACIBcjWGxPuEZjWOJOSnCCmndAkEAyi0EZt0KqnIz5YRfbsOu4DN7etX9Wx5d
XRk8hKaxQXV2Q/KvTkiBzclhQzTVsAb/AMc9luSb4lvuBFL0thk+MwJAdgYbbisW
LTcCl2iJRcKAoSQrKfWQuWDppRF1zcV7Cye/5NwxRv5TPqVOIH1n1F4lxxeZuAFO
ca7UPjGQ+sfAsQ==
-----END PRIVATE KEY-----
EOT;
	public $public_key = <<<EOT
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCpuzcnJ8YcuP2RjVOL7xjLeA51
t6CqnF6HfycW47vnmudwyCHiNg0GCYvlb9FCGxjxDcMdCsMHKQuJcQ9J1BurlPBk
fY6nhE6qdSd3qA+unTKsEq/GXRt36OBjCJ3tMjpHrnPo6mZx/VHKvAmhmCKNvI59
V2PxDHaFEegDGKDzjQIDAQAB
-----END PUBLIC KEY-----
EOT;

	protected function decrypt($cipher_text, $private_key = '') {
		$private_key = $private_key ? $private_key : $this->private_key;
		$rs = openssl_private_decrypt($cipher_text, $plaintext, openssl_pkey_get_private($private_key));
		return $rs ? $plaintext : FALSE;
	}

	/**
	* RSA加密
	* @param $plain_text
	* @param string $public_key
	* @return bool|string
	*/
	protected function encrypt($plain_text, $public_key = '') {
		$public_key = $public_key ? $public_key : $this->public_key;
		$rs = openssl_public_encrypt($plain_text, $cipher_text, openssl_pkey_get_public($public_key));
		return $rs ? $cipher_text : FALSE;
	}

	/**
	* RSA分片解密 默认 117字节
	* @param $cipher_text
	* @param string $private_key
	* @param int $rsa_bit
	* @return string
	*/
	public function rsa_slice_decrypt($cipher_text, $private_key = '', $rsa_bit = 1024) {
		$cipher_text = base64_decode($cipher_text);
		$private_key = $private_key ? $private_key : $this->private_key;

		$input_length = strlen($cipher_text);
		$offset = 0;
		$i = 0;

		$max_block = $rsa_bit / 8;

		$plain_text = '';

		// 对数据分段解密
		while ($input_length - $offset > 0) {

		if ($input_length - $offset > $max_block) {
			$cache = $this->decrypt(substr($cipher_text, $offset, $max_block), $private_key);
		} else {
			$cache = $this->decrypt(substr($cipher_text, $offset, $input_length - $offset), $private_key);
		}

		$plain_text = $plain_text . $cache;

		$i = $i + 1;
		$offset = $i * $max_block;
		}
		return $plain_text;
	}

	/**
	* RSA分片加密
	* @param $plain_text
	* @param string $public_key
	* @param int $rsa_bit
	* @return string
	*/
	public function rsa_slice_encrypt($plain_text, $public_key = '', $rsa_bit = 1024) {
		$public_key = $public_key ? $public_key : $this->public_key;

		$input_length = strlen($plain_text);
		$offset = 0;
		$i = 0;

		$max_block = $rsa_bit / 8 - 11;

		$cipher_text = '';

		// 对数据分段加密
		while ($input_length - $offset > 0) {

			if ($input_length - $offset > $max_block) {
				$cache = $this->encrypt(substr($plain_text, $offset, $max_block), $public_key);
			} else {
				$cache = $this->encrypt(substr($plain_text, $offset, $input_length - $offset), $public_key);
			}

			$cipher_text = $cipher_text . $cache;

			$i++;
			$offset = $i * $max_block;
		}
		// return $cipher_text;
		return  base64_encode($cipher_text);
	}
}