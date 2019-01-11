<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* @author Marares.liu
 * sms send lib
 */
include_once("Curl.php");
include_once(BASEPATH."core/Model.php");
class sms extends CI_Model{

    protected $curl;
    public $mobile;
    public $message;
    protected $data;

    public function __construct()
    {   
        parent::__construct();
        $this->curl = new Curl();
        $this->bind_data();
    }

    public function send()
    {
        $response = $this->curl->request("http://sms.fruitday.com:8080/fday/sms/send/single",$this->data,'POST',array(),"");

        if(strpos($response['response'], "HTTP/1.1 302 Found") !== false) {

        }
        return $response;
        
    }

    protected function bind_data()
    {
        $this->data['mobile'] = $this->mobile; 
        $this->data['message'] = $this->message; 
        $this->data['priority'] = 9;
        $this->data['via'] = "fruitday";
    }
    public function fastSend($DESTMOBS,$CONTENT, $priority = 9){
        /*短信发送黑名单start*/
        $black = array(15889766783,18954982021,18001201283,15506918605,15340668236,15349231419,13999137168,18204894764,18764251028,18600033524);
        if(in_array($DESTMOBS, $black)){
            exit;
        }
        /*短信发送黑名单end*/
              /*短信发送方式切换start*/
  if(SMS_CHANNEL=='local'){
    $account = SMS_ACCOUNT;
    $password = md5(SMS_PASSWD);
    $phone = $DESTMOBS;
    $content = $CONTENT;

    $sms_url = 'http://sms.fruitday.com/fday/sms/send/single';
    
    $data = array(
        'mobile'=>$phone,
        'message'=>$content,
        'appId'=>'fruitdaysc03'
    );
    $data['sign'] = $this->create_sign($data);
    $data_string = json_encode($data);
    $ch=curl_init($sms_url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER,
    array('Content-Type:application/json',
        'Content-Length: ' . strlen($data_string))
    );
    $result = curl_exec($ch);
    curl_close($ch);

    /*
    $sendSmsAddress = SMS_API_URL;
    $message ="<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
                                ."<message>"
                                . "<account>"
                                . $account
                                . "</account><password>"
                                . $password
                                . "</password>"
                                . "<msgid></msgid><phones>"
                                . $phone
                                . "</phones><content>"
                                . $content
                                . "</content><subcode>"
                                ."</subcode>"
                                ."<sendtime></sendtime>"
                                ."</message>";
    $params = array('message' => $message);
    $data = http_build_query($params);
    $context = array('http' => array(
        'method' => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $data,
    ));
    $contents = file_get_contents($sendSmsAddress, false, stream_context_create($context));
    */
    if(strstr($content, "验证")){
        $user_sms_log_date = array(
            'mobile'=>$phone,
            'create_time'=>date("Y-m-d H:i:s"),
            'content'=>$content
        );
        $this->db->insert('user_sms_log',$user_sms_log_date);
    }
  }else{
  /*短信发送方式切换end*/
        $url = 'http://sms.fruitday.com:8080/fday/sms/send/single';
        $data['mobile'] = $DESTMOBS;
        $data['message'] = $CONTENT;
        $data['priority'] = $priority;
        $data['via'] = "fruitday";
        $data_string = json_encode($data);
        $ch=curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array('Content-Type:application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = json_decode(curl_exec($ch),1);
        curl_close($ch);
    }
}

function create_sign($params){
        ksort($params);//对所有入参的键升序排列
        $query = "";
        foreach($params as $k=>$v){
             if(is_array($v)){
                $v = json_encode($v);
            }
            $query .= $k."=".$v."&";
        }//拼接成get字符串
        $sign = md5(substr(md5($query.SMS_SECRET), 0,-1)."s");
        return $sign;
    }
    
    //群发短信             add by dengjm 2015-08-12
    public function mass($content){
    	$sms_url = 'http://sms.fruitday.com/fday/sms/send/bulk';
    	$data = array(
    			'messages'=>json_encode($content),
    			'appId'=>'fruitdaysc03'
    	);
    	$data['sign'] = $this->create_sign($data);
    	$data_string = json_encode($data);
    	$ch=curl_init($sms_url);
    	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    	curl_setopt($ch, CURLOPT_HTTPHEADER,
    	array('Content-Type:application/json',
    	'Content-Length: ' . strlen($data_string))
    	);
    	$result = curl_exec($ch);
    	curl_close($ch);
    }
    
}
