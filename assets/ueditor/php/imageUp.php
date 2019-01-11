<?php
header("Content-Type:text/html;charset=utf-8");
date_default_timezone_set("Asia/chongqing");
include "Uploader.class.php";

//get ci config
define('BASEPATH', str_replace("\\", "/", "system"));
include "../../../application/config/config.php";
$ci_config = $config;
unset($config);
$savePath = $ci_config['WEB_BASE_PATH']."up_images/";

//上传配置
$config = array(
    "savePath" => $savePath ,             //存储文件夹
    "maxSize" => 1000 ,                   //允许的文件最大尺寸，单位KB
    "allowFiles" => array( ".gif" , ".png" , ".jpg" , ".jpeg" , ".bmp" ),  //允许的文件格式
    "openS3" => OPEN_S3,	//是否开启aws s3
);

//背景保存在临时目录中
$up = new Uploader( "upfile" , $config );
$type = $_REQUEST['type'];
$callback=$_GET['callback'];

$info = $up->getFileInfo();
//获取详细路径
$info['url'] = str_replace($ci_config['WEB_BASE_PATH'],'',$info['url']);

/**
 * 返回数据
 */
if($callback) {
    echo '<script>'.$callback.'('.json_encode($info).')</script>';
} else {
    echo json_encode($info);
}
