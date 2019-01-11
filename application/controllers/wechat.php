<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Wechat extends MY_Controller {
    public $workgroup = 'wechat';

    function __construct() {
        parent::__construct();
    }
    function join(){
        $type = $this->input->get('type') ? : 1;
        $this->_pagedata['type'] = $type;
        $this->page('wechat/list.html');
    }

    function table(){
        $limit = $this->input->get('limit') ? : 999;
        $offset = $this->input->get('offset') ? : 0;
        $search_type = $this->input->get('type') ? : 1;
        $sql = "select * from p_wechat_file where 1 = 1 ";
        if ($search_type){
            $sql.= " and type = '".$search_type."' ";
        }
        $sql .= "limit ".$offset.",".$limit;
        $rs = $this->db->query($sql)->result_array();
        foreach ($rs as &$v) {
            if ($v['type'] == 1){
                $v['name'] = '<img height="16" src = "http://awshuodong.fruitday.com/cityBox/pdf.png" />&nbsp;&nbsp;'.$v['name'];
            } else {
                $v['name'] = '<img height="16" src = "http://awshuodong.fruitday.com/cityBox/video.png" />&nbsp;&nbsp;'.$v['name'];
            }

            $v['created_time'] = date('Y-m-d H:i:s',$v['created_time']);
            $v['status_name'] = $v['status'] == 1 ? '正常' : '<font color="red">删除</font>';
        }
        $result = array(
            'total' => count($rs),
            'rows' => $rs
        );
        echo json_encode($result);
    }

    public function set_status(){
        $id  = $this->input->post('id');
        if(!$id){
            $this->showJson(array('status'=>'error', 'msg'=>'请选择编辑项'));
        }
        $val = $this->input->post('val');
        if(!in_array($val, array(0,1))){
            $this->showJson(array('status'=>'error', 'msg'=>'状态不符合'));
        }
        if($this->db->update('wechat_file', array('status'=>$val), array('id'=>$id))){
            $this->showJson(array('status'=>'success', 'msg'=>'成功'));
        }
        $this->showJson(array('status'=>'error', 'msg'=>'网络异常，请稍后尝试'));
    }

    //ajax上传pdf/视频
    public function ajax_upload(){
        $type = $this->input->get('type');
        foreach($_FILES as $k => $v){
            $size = filesize($v['tmp_name']);
            $size = $this->getsize($size, 'mb');
            if ($size > 30){
                $this->showJson(array('status'=>'error', 'msg'=>'文件大小不能大于30M！'));
            }
            $photo_url = upload_img($v['tmp_name'],$v['name']);
            $data = array(
                'name'=>$v['name'],
                'type'=>$type,
                'url'=>$photo_url,
                'created_time'=>time(),
                'created_admin'=>$this->operation_name
            );
            $this->db->insert('wechat_file',$data);
            //href=""
            $this->showJson(array('status'=>'success'));
        }

    }

    private function getsize($size, $format = 'kb') {
        $p = 0;
        if ($format == 'kb') {
            $p = 1;
        } elseif ($format == 'mb') {
            $p = 2;
        } elseif ($format == 'gb') {
            $p = 3;
        }
        $size /= pow(1024, $p);
        return $size;
        //return number_format($size, 3);
    }


}
