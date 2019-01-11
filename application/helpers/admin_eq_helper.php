<?php
/**
 * Created by PhpStorm.
 * User: Excel
 * Date: 17/01/2018
 * Time: 13:49
 */

function add_eq_for_admin($equipment_id,$code,$platform_id,$type){
    $ci = &get_instance();
    $ci->citybox_master = $ci->load->database('citybox_master', TRUE);

    $data['name'] = $code;
    $data['code'] = $code;
    $data['type'] = $type;
    $data['serial_num'] = $equipment_id;
    $data['equipment_id'] = $equipment_id;
    $data['status'] = 1;
    $data['platform_id'] = $platform_id ? $platform_id : 0;
    $data['merchantid'] = $platform_id ? $platform_id : 0;
    $data['created_time'] = time();
    $data['replenish_location'] = 'default';
    $data['is_paper_order'] = 1;
    $data['is_self_type'] = 99;
    $insertBox = $ci->citybox_master->insert('equipment',$data);

    return $insertBox;
}

/**
 * 生成二维码、banner
 */

function general_qrcode_for_cron($equipment_id,$refer){
    $ci = &get_instance();
    $qr_img = NULL;
    $ci->citybox_master = $ci->load->database('citybox_master', TRUE);
    if($refer == 'alipay'){
        //先查询是否有qr
        $sql_qr = "select * from cb_equipment WHERE  equipment_id='{$equipment_id}'";
        $rs_qr = $ci->citybox_master->query($sql_qr)->row_array();
        if(!$rs_qr || !isset($rs_qr['qr']) || !$rs_qr['qr']){
            //没有生成支付宝的二维码

        }else{
            //已经生成支付宝的二维码
            $qr_img = $rs_qr['qr'];
        }
    }
    if(!$qr_img){
        $qr_img = general_code_api($equipment_id,$refer);
    }

    if ($qr_img){

        $qr_img = $qr_img;
        //更新db
        if ($refer == 'fruitday' || $refer == 'common'){
            $filed = 'qr_'.$refer;

            $admin_qr_sql = "select id from cb_equipment_qr where refer='{$refer}' and equipment_id= '{$equipment_id}'";
            $admin_qr = $ci->citybox_master->query($admin_qr_sql)->row_array();
            if ($admin_qr){
                $sql = "UPDATE cb_equipment_qr SET qr='{$qr_img}',update_time = '".time()."' WHERE id=".$admin_qr['id'];
            } else {
                $sql = "INSERT INTO cb_equipment_qr (equipment_id,refer,qr,update_time) VALUES ('{$equipment_id}','{$refer}','{$qr_img}','".time()."')";
            }
            //更新db
            $ci->citybox_master->query($sql);
        }elseif($refer == 'alipay'){
            $filed = 'qr';
            $param['qr'] = $qr_img;
            $ci->citybox_master->update('equipment', $param, array('equipment_id'=>$equipment_id));
        }

        $sql = "UPDATE p_equipment SET $filed='{$qr_img}' WHERE equipment_id='{$equipment_id}'";
        $ci->db->query($sql);
    }
    echo 'qr=>', $equipment_id,'-',$refer,'=>', $qr_img,"\n";
}

function general_code_api($equipment_id,$refer){
    $ci = &get_instance();
    $ci->load->library('curl',null,'http_curl');
    $params = array(
        'box_id'=> $equipment_id,
        'refer'=>$refer
    );
    $sign = create_sign_for_api($params);
    $headers = array("sign:$sign","platform:admin");
    $options['timeout'] = 6000;
    $options['headers'] = $headers;
    $result = $ci->http_curl->request(BOX_API_URL.'/api/public_tool/create_qr_code', $params, 'GET', $options);
    $result = json_decode($result['response'],1);
    return $result['qr_img'];
}

function create_banner_for_cron($equipment_ids,$refer = 'common_banner'){
    $ci = &get_instance();
    $ci->load->library('curl',null,'http_curl');
    $params = array(
        'timestamp'=>time() . '000',
        'source'    => 'platform',
        'equipment_ids'=>trim($equipment_ids),
        'refer'=>$refer
    );
    $url = RBAC_URL."apiEquipment/general_banner_batch";

    $params['sign'] = create_sign_for_admin($params);

    $options['timeout'] = 6000;
    $result = $ci->http_curl->request($url, $params, 'POST', $options);
    $result = json_decode($result['response'],1);
    foreach ($result['banner']  as $value) {
        switch ($refer){
            case 'common_banner_alipay':
                $sql = "UPDATE p_equipment SET banner_common_alipay='".$value['banner']."' WHERE equipment_id='{$value['equipment_id']}'";
                break;
            case 'common_banner_wechat':
                $sql = "UPDATE p_equipment SET banner_common_wechat='".$value['banner']."' WHERE equipment_id='{$value['equipment_id']}'";
                break;
            default:
                $sql = "UPDATE p_equipment SET banner_common='".$value['banner']."' WHERE equipment_id='{$value['equipment_id']}'";
                break;
        }
        $ci->db->query($sql);
        echo 'banner=>',$value['equipment_id'],'-',$refer,'=>', $value['banner'],"\n";
    }
    return $result;
}

function create_sign_for_api($params){
    ksort($params);
    $query = '';
    foreach ($params as $k => $v) {
        $query .= $k . '=' . $v . '&';
    }
    $sign = md5(substr(md5($query . BOX_API_SECRET), 0, -1) . 'w');
    return $sign;
}

function create_sign_for_admin($params)
{
    if (isset($params['sign'])) {
        unset($params['sign']);
    }
    ksort($params);
    $query = '';
    foreach ($params as $k => $v) {
        $query .= $k . '=' . $v . '&';
    }
    $sign = md5(substr(md5($query . PLATFORM_SECRET), 0, -1) . 'P');
    return $sign;
}