<?php
/**
 * Created by PhpStorm.
 * User: Excel
 * Date: 17/01/2018
 * Time: 14:54
 */
class Eq extends CI_Controller{

    function gereral_code_and_banner(){
        $sql = "select * from p_equipment WHERE  (qr_common='' OR banner_common='' OR banner_common_alipay='' OR banner_common_wechat='' OR qr_common IS  NULL OR banner_common IS NULL OR banner_common_alipay IS NULL OR banner_common_wechat IS NULL) and platform_id > 0 ORDER BY id DESC limit 200 ";
        $rs_eqs = $this->db->query($sql)->result_array();
        $this->load->helper('admin_eq');
        foreach ($rs_eqs as $eq){
            // if(!$eq['qr']){
            //     general_qrcode_for_cron($eq['equipment_id'],'alipay');
            //     sleep(3);
            // }
            // if(!$eq['qr_fruitday']){
            //     general_qrcode_for_cron($eq['equipment_id'],'fruitday');
            //     sleep(3);
            // }
            if(!$eq['qr_common']){
                general_qrcode_for_cron($eq['equipment_id'],'common');
                sleep(2);
            }
            if(!$eq['banner_common']){
                create_banner_for_cron($eq['equipment_id']);
                sleep(2);
            }

            if(!$eq['banner_common_alipay']){
                create_banner_for_cron($eq['equipment_id'], 'common_banner_alipay');
                sleep(2);
            }

            if(!$eq['banner_common_wechat']){
                create_banner_for_cron($eq['equipment_id'], 'common_banner_wechat');
                sleep(2);
            }
        }
    }
}