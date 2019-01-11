<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 18/5/28
 * Time: 下午2:02
 */
class Image extends MY_Controller
{
    public $img_http  = 'http://fdaycdn.fruitday.com/';


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
    }

    //ajax上传图片
    public function ajax_upload_img(){
        foreach($_FILES as $k => $v){
            $size = filesize($v['tmp_name']);
            $size = $this->getsize($size, 'kb');
            if ($size > 500){
                $this->showJson(array('status'=>'error', 'msg'=>'图片大小不能大于500K！'));
            }
            $photo_url =   upload_img($v['tmp_name'],$v['name']);
            $this->showJson(array('status'=>'success', 'url'=>$photo_url, 'img_http'=>$this->img_http));
        }

    }
}