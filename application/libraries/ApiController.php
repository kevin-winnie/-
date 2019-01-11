<?php
class ApiController extends CI_Controller
{
    protected $response;
    public function __construct()
    {
        parent::__construct();
        try {
            if ($this->validate_platform_host() == false) throw new \Exception('签名错误');
            if (!isset($_REQUEST['timestamp'])) throw new \Exception('缺少系统级参数');
            if (!isset($_REQUEST['source'])) throw new \Exception('缺少系统级参数');
        } catch (\Exception $e) {
            $response['msg'] = $e->getMessage();
            echo (json_encode($response));
            exit;
        }
    }

    public function __destruct() {
        if(!empty($this->response))
            echo json_encode($this->response);
    }

    protected  function check_is_null($val,$msg){
        if(is_null($val)){
            $this->response = array("code"=>300,"msg"=>$msg);
        }
    }

    protected function succ_response($msg){
        $this->response = array("code"=>200);
        $this->response = array_merge($this->response,$msg);
    }

    protected  function err_response($msg){
        $this->response = array("code"=>300);
        $this->response = array_merge($this->response,$msg);
    }

    //验签PLATFORM
    protected function validate_platform_host(&$returnParam = array()) {
// 	$post_param = $this->input->post();
        $post_param = $_POST;
        $request_sign = isset($post_param['sign']) ? $post_param['sign'] : '';
        unset($post_param['sign']);
        ksort($post_param);
        $query = '';
        foreach($post_param as $k => $v) {
            //if ($v == '') continue;
            $query .= $k . '=' . $v . '&';
        }
        $validate_sign = md5(substr(md5($query.PLATFORM_HOST_SECRET), 0,-1).'P');
        if ($validate_sign == $request_sign) {
            $bool = true;
        } else {
            $returnParam['code']	= '300';
            $returnParam['msg']	= '签名错误';
            $bool = false;
        }
        return $bool;
    }
}