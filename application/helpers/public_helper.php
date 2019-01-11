<?php
    use Aws\S3\S3Client;
if(!function_exists('array_column')){ 
	function array_column(array $array, $column_key, $index_key=null){
		$result = [];
		foreach($array as $arr){
			if(!is_array($arr)) continue;

			if(is_null($column_key)){
				$value = $arr;
			}else{
				$value = $arr[$column_key];
			}

			if(!is_null($index_key)){
				$key = $arr[$index_key];
				$result[$key] = $value;
			}else{
				$result[] = $value;
			}
		}
		return $result;
	}
}

/*生成唯一短标示码*/
function tag_code($str){
	$str = crc32($str);
	$x = sprintf("%u", $str);
	$show = '';
	while($x > 0) {
		$s = $x % 62;
		if ($s > 35) {
			$s = chr($s+61);
		} elseif ($s > 9 && $s <=35) {
			$s = chr($s + 55);
		}
		$show .= $s;
		$x = floor($x/62);
	}
	return $show;
}
function rand_pass_new(){
	$a   =  "1,2,3,4,5,6,7,8,9,A,B,C,D,F,P,R,S,Y,T,W,U,K,H,X";
	$a_array = explode(",",$a);
	for($i=1;$i<9;$i++){
		$tname.=$a_array[rand(0,23)];
	}
	return $tname;
}
function get_pwd_strength($pwd){  
	if (strlen($pwd)>30 || strlen($pwd)<6)  
	{  
		return "密码必须为6-30位的字符串";  
	}  

	if(preg_match("/^\d*$/",$pwd))  
	{  
		return "密码必须包含字母,强度:弱";//全数字  
	}  

	if(preg_match("/^[a-z]*$/i",$pwd))  
	{  
		return "密码必须包含数字,强度:中";//全字母      
	}  

	if(!preg_match("/^[a-z\d]*$/i",$pwd))  
	{  
		return "密码只能包含数字和字母,强度:强";//有数字有字母  ";  
	}  

	return "";
}

function win_linux($path){
    if(PHP_OS == 'WINNT'){
        return "..".$path;
    }else{
        return $path;
    }
}

function upload_pic2($id,$t_file,$o_file,$n,$i='01',$path='product_pic/',$tag='tag'){
    if($t_file == ''){
    }else{
        $file_array = pathinfo($o_file);
        $p_type = strtolower($file_array['extension']);
        $path = $path.$id.'/'.$n.'/';
        if(!OPEN_S3){
            if (!file_exists($path)){
                mkdir($path, 0777, true);
            }
        }
        $file_name = $n."-1000x1000-".$id."-".$tag.".".$p_type;
        if(OPEN_S3){
            uploadS3('fdaycdn.fruitday.com',$path.$file_name,$t_file,$p_type);
            $upload_file = $t_file;
        }else{
            move_uploaded_file($t_file,$path.$file_name);    
            $upload_file = $path.$file_name;
        }
        
        if ($i == '01'){
            $thum_name370 = $path.$n."-370x370-".$id."-".$tag.".".$p_type;
            $thum_name180 = $path.$n."-180x180-".$id."-".$tag.".".$p_type;
            $thum_name100 = $path.$n."-100x100-".$id."-".$tag.".".$p_type;
            $thum_name270 = $path.$n."-270x270-".$id."-".$tag.".".$p_type;
            ImageResize($upload_file,370,370,$thum_name370);
            ImageResize($upload_file,180,180,$thum_name180);
            ImageResize($upload_file,100,100,$thum_name100);
            ImageResize($upload_file,270,270,$thum_name270);
        }
        $res =  array($path.$file_name,$thum_name180,$thum_name100,$thum_name370,$thum_name270);
        foreach($res as &$v){
            $v = str_replace('/mnt/www/img/','',$v);
        }
        return $res;
    }
}

function uploadS3($bucket = 'fdaycdn.fruitday.com',$s3_key,$t_file,$p_type){
    require_once APPPATH.'plugins/aws/aws-autoloader.php';
    if($p_type=='jpg'){
        $p_type = 'jpeg';
    }
    $client = S3Client::factory ( array (
        'region'=>'cn-north-1',
        'version'=>'latest',
        'key' => 'AKIAPFZ5G3A3XR6EETHQ',
        'secret' => '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'
    ) );

    $result = $client->putObject(array(
        'Bucket' => $bucket,
        'Key' => $s3_key,
        'SourceFile' => $t_file,
        'ContentType'=>'image/'.$p_type
    ));
}

function uploadApk($bucket = 'fdaycdn.fruitday.com', $s3_key, $t_file) {
    require_once APPPATH.'plugins/aws/aws-autoloader.php';
    $client = S3Client::factory ( array (
        'region'=>'cn-north-1',
        'version'=>'latest',
        'key' => 'AKIAPFZ5G3A3XR6EETHQ',
        'secret' => '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'
    ) );
    $result = $client->putObject(array(
        'Bucket' => $bucket,
        'Key' => 'apk/'.$s3_key,
        'SourceFile' => $t_file,
        'ContentType'=>'string'
    ));

    return $result;
}


function downloadFile($key ,$bucket = 'fdaycdn.fruitday.com'){
    require_once APPPATH.'plugins/aws/aws-autoloader.php';
    $client = S3Client::factory ( array (
        'region'=>'cn-north-1',
        'version'=>'latest',
        'key' => 'AKIAPFZ5G3A3XR6EETHQ',
        'secret' => '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'
    ) );

    $object = $client->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key
    ));

    header('Content-Description: File Transfer');
    //this assumes content type is set when uploading the file.
    header('Content-Type: ' . $object->ContentType);
    header('Content-Disposition: attachment; filename=' . 'aa.apk');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    //send file to browser for download.
    echo $object->body;
    die;
}

function watermark($src){
    $bg=getimagesize($src);
    $ground_w=$bg[0];
    $ground_h = $bg[1];
    $water_src="./assets/image/water.png";
    $wg=getimagesize($water_src);
    $w=$wg[0];
    $h=$wg[1];
    $posX = ($ground_w - $w) / 2;
    $posY = ($ground_h - $h) / 2;
    $im=water($src,$water_src,$posX,$posY);
    imagejpeg($im,$src);
    imagedestroy($im);
}
//水印函数
function water($fn,$water_src,$_x,$_y){  //返回图片水印后画布
    $stat=getimagesize($fn);  //获取原始图片信息
    $ws=getimagesize($water_src);  //获取水印图片信息
    switch($stat[2]){  //使用原始图片，创建画布，这一步经常用到，建议写成函数
        case 1:
            $im=imagecreatefromgif($fn);
            break;

        case 2:
            $im=imagecreatefromjpeg($fn);
            break;

        case 3:
            $im=imagecreatefrompng($fn);
            break;
    }
    switch($ws[2]){  //使用水印图片，创建画布
        case 1:
            $wa=imagecreatefromgif($water_src);
            break;

        case 2:
            $wa=imagecreatefromjpeg($water_src);
            break;

        case 3:
            $wa=imagecreatefrompng($water_src);
            break;
    }

    imagecopy($im,$wa,$_x,$_y,0,0,$ws[0],$ws[1]);  //使用imagecopy生成最终的图片画布
    return $im;
}

function ImageResize($srcFile,$toW,$toH,$toFile="")
{
    if($toFile==""){ $toFile = $srcFile; }
    $info = "";
    $data = GetImageSize($srcFile,$info);
    switch ($data[2])
    {
        case 1:
            if(!function_exists("imagecreatefromgif")){
                echo "你的GD库不能使用GIF格式的图片，请使用Jpeg或PNG格式！<a href='javascript:go(-1);'>返回</a>";
                exit();
            }
            $im = ImageCreateFromGIF($srcFile);
            break;
        case 2:
            if(!function_exists("imagecreatefromjpeg")){
                echo "你的GD库不能使用jpeg格式的图片，请使用其它格式的图片！<a href='javascript:go(-1);'>返回</a>";
                exit();
            }
            $im = ImageCreateFromJpeg($srcFile);
            break;
        case 3:
            $im = ImageCreateFromPNG($srcFile);
            break;
    }
    $srcW=ImageSX($im);
    $srcH=ImageSY($im);
    $toWH=$toW/$toH;    //新的尺寸比例
    $srcWH=$srcW/$srcH; //旧的尺寸比例
    $ftoW=$toW; //沿用最新的宽度
    $ftoH = intval($ftoW/$srcWH);//按照比例来取得新的高度

    if($srcW>$toW||$srcH>$toH)
    {
        if(function_exists("imagecreatetruecolor")){
            @$ni = ImageCreateTrueColor($ftoW,$ftoH);
            if($ni) ImageCopyResampled($ni,$im,0,0,0,0,$ftoW,$ftoH,$srcW,$srcH);
            else{
                $ni=ImageCreate($ftoW,$ftoH);
                ImageCopyResized($ni,$im,0,0,0,0,$ftoW,$ftoH,$srcW,$srcH);
            }
        }else{
            $ni=ImageCreate($ftoW,$ftoH);
            ImageCopyResized($ni,$im,0,0,0,0,$ftoW,$ftoH,$srcW,$srcH);
        }

        $tmp_img = "/tmp/".rand(0,9999).$toW.'x'.$toH.rand_pass_new();

        if(function_exists('imagejpeg')){
            if(OPEN_S3){
                ImageJpeg($ni,$tmp_img,100);
                uploadS3('fdaycdn.fruitday.com',$toFile,$tmp_img,'jpg');
            }else{
                ImageJpeg($ni,$toFile,100);
            }
        }
        else{
            if(OPEN_S3){
                ImagePNG($ni,$tmp_img);
                uploadS3('fdaycdn.fruitday.com',$toFile,$tmp_img,'png');
            }else{
                ImagePNG($ni,$toFile);
            }
        }
        @unlink($tmp_img);
        ImageDestroy($ni);
    }
    ImageDestroy($im);
}

function upload_img($t_file,$o_file,$i=2,$path='images/',$is_thum=0,$thum_width=130,$thum_height=72){
    if ($t_file == ''){
        return '';
    }
    else{
        $path  =  $path.date("Y-m-d")."/";
        if(!OPEN_S3){
            if (!file_exists($path)){
                mkdir($path, 0777, true);
            }
        }
        $file_array  =  pathinfo($o_file);
        $p_type      =  strtolower($file_array['extension']);
        $p_name      =  time()."_".$i.".".$p_type;
        if(OPEN_S3){
            uploadS3('fdaycdn.fruitday.com',$path.$p_name,$t_file,$p_type);
            $upload_file = $t_file;
        }else{
            move_uploaded_file($t_file,$path.$p_name);
            $upload_file = $path.$p_name;
        }
        if ($is_thum == 1){
            $thum_path   =  $path."thum_".$p_name;
            ImageResize($upload_file,$thum_width,$thum_height,$thum_path);
            $res = array($path.$p_name,$thum_path);
            foreach($res as &$v){
                $v = str_replace('/mnt/www/img/','',$v);
            }
            return $res;
        }else{
            return str_replace('/mnt/www/img/','',$path.$p_name);
        }
    }
}


function get_param($arr){
    $where = array();
    foreach($_REQUEST as $key=>$v){
        if(in_array($key,$arr)){
            if($v != ''){
                $where[$key] = $v;
            }
        }
    }
    return $where;
}

/**
     * 建立文件夹
     *
     * @param string $aimUrl
     * @return viod
     */
    function createDir($aimUrl) {
        $aimUrl = str_replace('', '/', $aimUrl);
        $aimDir = '';
        $arr = explode('/', $aimUrl);
        $result = true;
        foreach ($arr as $str) {
            $aimDir .= $str . '/';
            if (!file_exists($aimDir)) {
                $result = mkdir($aimDir);
            }
        }
        return $result;
    }

    /**
     * 建立文件
     *
     * @param string $aimUrl 
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    function createFile($aimUrl, $overWrite = false) {
        if (file_exists($aimUrl) && $overWrite == false) {
            return false;
        } elseif (file_exists($aimUrl) && $overWrite == true) {
            unlinkFile($aimUrl);
        }
        $aimDir = dirname($aimUrl);
        createDir($aimDir);
        touch($aimUrl);
        return true;
    }

    /**
     * 移动文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    function moveDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            createDir($aimDir);
        }
        @ $dirHandle = opendir($oldDir);
        if (!$dirHandle) {
            return false;
        }
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                moveFile($oldDir . $file, $aimDir . $file, $overWrite);
            } else {
                moveDir($oldDir . $file, $aimDir . $file, $overWrite);
            }
        }
        closedir($dirHandle);
        return rmdir($oldDir);
    }

    /**
     * 移动文件
     *
     * @param string $fileUrl
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    function moveFile($fileUrl, $aimUrl, $overWrite = false) {
        if (!file_exists($fileUrl)) {
            return false;
        }
        if (file_exists($aimUrl) && $overWrite = false) {
            return false;
        } elseif (file_exists($aimUrl) && $overWrite = true) {
            unlinkFile($aimUrl);
        }
        $aimDir = dirname($aimUrl);
        createDir($aimDir);
        rename($fileUrl, $aimUrl);
        return true;
    }

    /**
     * 删除文件夹
     *
     * @param string $aimDir
     * @return boolean
     */
    function unlinkDir($aimDir) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        if (!is_dir($aimDir)) {
            return false;
        }
        $dirHandle = opendir($aimDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($aimDir . $file)) {
                unlinkFile($aimDir . $file);
            } else {
                unlinkDir($aimDir . $file);
            }
        }
        closedir($dirHandle);
        return rmdir($aimDir);
    }

    /**
     * 删除文件
     *
     * @param string $aimUrl
     * @return boolean
     */
    function unlinkFile($aimUrl) {
        if (file_exists($aimUrl)) {
            unlink($aimUrl);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 复制文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    function copyDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            createDir($aimDir);
        }
        $dirHandle = opendir($oldDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                copyFile($oldDir . $file, $aimDir . $file, $overWrite);
            } else {
                copyDir($oldDir . $file, $aimDir . $file, $overWrite);
            }
        }
        return closedir($dirHandle);
    }

    /**
     * 复制文件
     *
     * @param string $fileUrl
     * @param string $aimUrl
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    function copyFile($fileUrl, $aimUrl, $overWrite = false) {
        if (!file_exists($fileUrl)) {
            return false;
        }
        if (file_exists($aimUrl) && $overWrite == false) {
            return false;
        } elseif (file_exists($aimUrl) && $overWrite == true) {
            unlinkFile($aimUrl);
        }
        $aimDir = dirname($aimUrl);
        createDir($aimDir);
        copy($fileUrl, $aimUrl);
        return true;
    }

    //对二维数组 进行排序
    function array_sort($arr,$keys,$type='asc'){
        $tmp = array();
        foreach($arr as $k=>$v){
            $tmp[$k] = $v[$keys];
        }
        if($type == "asc"){
            array_multisort($arr, SORT_ASC, SORT_STRING, $tmp, SORT_ASC);
        }else{
            array_multisort($arr, SORT_DESC, SORT_STRING, $tmp, SORT_DESC);
        }
        return $arr;
    }
?>