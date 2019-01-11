<?php
//defined('OPEN_CURL_PROXY') or define('OPEN_CURL_PROXY', true);
//defined('CURL_PROXY_PORT') or define('CURL_PROXY_PORT','8088');
//defined('CURL_PROXY_ADDR') or define('CURL_PROXY_ADDR','10.251.241.71');

class Tms
{
    private $_error = array();

    private $_rpc_log = array();

    private $ci;

    public function __construct()
    {
        include(APPPATH . 'libraries/Curl.php');
        $this->ci = $this->ci = &get_instance();
    }

    public function set_rpc_log($rpc_log)
    {
        $this->_rpc_log = $rpc_log;

        return $this;
    }

    /**
     * 获取错误信息
     *
     * @return void
     * @author
     **/
    public function get_errorinfo()
    {
        return $this->_error;
    }

    /**
     * 请求
     *
     * @param $params ['url']
     * @param $params ['appId']
     * @param $params ['method']
     * @param $params ['v']
     * @param $params ['jsonData']
     * @param $params ['secret']
     * @param $params ['cnone']
     * @param $params ['timestamp']
     *
     * @param int $timeout
     * @return mixed
     */
//    public function realtime_call($params, $timeout = 6)
//    {
//        $this->ci->load->library('poolhash');
//        $this->ci->load->library('aes', null, 'encrypt_aes');
//        $this->ci->load->library('curl', null, 'http_curl');
//
//        $nParams = array(
//            'appId'     => !empty($params['appId'])     ? $params['appId'] : POOL_O2O_OMS_APPID,
//            'cnone'     => !empty($params['cnone'])     ? $params['cnone'] : $this->gen_uuid(),
//            'timestamp' => !empty($params['timestamp']) ? $params['timestamp'] : date('Y-m-d H:i:s'),
//            'method'    => !empty($params['method'])    ? $params['method'] : '',
//            'v'         => !empty($params['v'])         ? $params['v'] : POOL_O2O_OMS_VERSION,
//            'data'      => is_array($params['data'])    ? json_encode($params['data'], JSON_UNESCAPED_UNICODE) : $params['data'],
//        );
//
//        $secret = !empty($params['secret']) ? $params['secret'] : POOL_O2O_OMS_SECRET;
//        unset($params['secret']);
//
//        $nParams['sign'] = $this->ci->poolhash->create_sign($nParams, $secret);
//        $nParams['data'] = urlencode($this->ci->encrypt_aes->AesEncrypt($nParams['data'], !empty($params['aesKey']) ? $params['aesKey'] : base64_decode(POOL_O2O_AES_KEY)));
//
//        $options['timeout'] = $timeout;
//        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
//            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
//        }
//        $rs = $this->ci->http_curl->request($params['url'], $nParams, 'POST', $options);
//
//        if ($rs['errorNumber'] || $rs['errorMessage']) {
//
//            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);
//
//            $this->insert_log($params['url'], $params['data'], $nParams['data'], 'fail');
//            return false;
//        }
//
//        $response = json_decode($rs['response'], true);
//
//        if ($response['code'] != '1000' && $response['code'] != '200') {
//            $this->_error = array('errorNumber' => $response['code'], 'errorMessage' => $response['msg']);
//
//            $this->insert_log($params['url'], $params['data'], $nParams['data'], 'fail');
//            return false;
//        }
//        if(!empty($response['data'])){
//            $response['data'] = urldecode($response['data']);
//            $data = $this->ci->encrypt_aes->AesDecrypt($response['data'], !empty($params['aesKey']) ? $params['aesKey'] : base64_decode(POOL_O2O_AES_KEY));
//            //$data = $response['data'];
//
//            $data = json_decode($data, true);
//        }else{
//            $data = true;
//        }
//
//        $this->insert_log($params['url'], $params['data'], $nParams['data'], 'succ');
//
//        return $data;
//    }

    /**
     * tms 发配送单日志
     *
     * @return void
     * @author
     *$log = array(
    'box_no'=>"73081618627",
    'req_body' => json_encode($req, JSON_UNESCAPED_UNICODE),
    'req_time' => date("Y-m-d H:i:s"),
    'response'=>'',
    'response_time'=>'',
    'req_type' => 'tms_deliver',
    );
     *
     **/
    private function insert_log($log,$equipment_log)
    {
        if($equipment_log){
            foreach($equipment_log as $v){
                $log['box_no'] = $v['shipping_no'];
                $log_batch[] = $log;
            }
        }
        $this->ci->db->insert_batch('request_msg_log',$log_batch);
//        $this->ci->db->insert("request_msg_log",$log);
    }

    private function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * @param $params
     * $params参数:
     *      method
     *      v
     *      appKey
     *      ts
     *      自定义参数
     * @return mixed
     */
    public function tms_call($params,$equipment_log){
        $http_curl = new Curl();
        $url = POOL_O2O_TMS_URL;
        $params += array(
            'v'         => POOL_O2O_TMS_VERSION,
            'appKey'    => POOL_O2O_TMS_APPKEY,
            'ts'        => time() . '000'
        );
        $params['sign'] = $this->create_tms_sign($params);

        $log = array(
            'box_no'=>'',
            'req_body' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'req_time' => date("Y-m-d H:i:s"),
            'req_type' => 'tms_deliver',
        );

        $options['timeout'] = 100;
//        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
//            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
//        }

//        var_dump($url,$params,$options);

        $rs = $http_curl->request($url, $params, 'POST', $options);

        $log['response_time'] = date("Y-m-d H:i:s");

        if ($rs['errorNumber'] || $rs['errorMessage']) {
            $this->_error = array('success'=>'false','errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);
            $log['response'] = json_encode($this->_error,JSON_UNESCAPED_UNICODE);
            $this->insert_log($log,$equipment_log);
            return false;
        }

        $response = json_decode($rs['response'], true);

        if (empty($response['success'])) {
            $this->_error = array('success'=>'false','errorNumber' => $response['errorCode'], 'errorMessage' => $response['message']);
            $log['response'] = json_encode($this->_error,JSON_UNESCAPED_UNICODE);
            $this->insert_log($log,$equipment_log);
            return false;
        }else{
            $log['response'] = json_encode($response,JSON_UNESCAPED_UNICODE);
            $this->insert_log($log,$equipment_log);
            return $response;
        }
    }

    public function create_tms_sign($params){
        $sign = '';
        if(!empty($params) && is_array($params)){
            ksort($params);
            foreach($params as $k => $v){
                $sign .= $k . $v;
            }
            $sign = sha1($sign . POOL_O2O_TMS_SECRET);
        }

        return strtoupper($sign);
    }
}