<?php
/**
* 刷新配置缓存
*/
function refresh_config_cache()
{
    $ci = &get_instance();
    $ci->load->library('phpredis');
    $redis = $ci->phpredis->getConn();
    //删除所有的key
    $keys = $redis->keys(CONFIG_PRE."*");
    $redis->del($keys);
    $sql = "select * from p_config order by id desc limit 10000";
    $list = $ci->db->query($sql)->result_array();
    $config_list = array();
    foreach ($list as $value) {
        $config_list[$value['id']] = $value;
    }

    $sql = "select * from p_config_device order by id desc limit 10000000";
    $custom_list = $ci->db->query($sql)->result_array();
    $ret_array = array();
    $platfoms = array();
    foreach ($custom_list as $v){
        if($v['platform_id'] == "0"){
            $key  = CONFIG_PRE."d_".$v['device_id'];
        }else {
            $key  = CONFIG_PRE."p_".$v['platform_id'];
            $platfoms[] = $v['platform_id'];
        }

        $configs = json_decode($v['config_ids'],1);
        $new_config = array();
        foreach ($configs as $k=>$v1){
            $new_config[trim($k)] = json_decode(trim($config_list[trim($v1)]['config_text']),1);
        }
        $new_config['other']['refers'] = json_decode($v['refer'],1);
        $new_config['other']['error_msg'] = $v['error_msg'];
        $new_config['other']['error_url'] = $v['error_url'];
        $new_config['other']['use_yue'] = $v['use_yue'];
        $new_config['other']['use_modou'] = $v['use_modou'];
        $new_config['other']['group_code'] = $v['group_code'];
        $new_config['other']['common_pr'] = $v['common_pr'];
        $redis->set($key,json_encode($new_config));
        $ret_array[$key] = $new_config;
    }
    $ci->load->model('commercial_model');
    $platform_list= $ci->commercial_model->getList("*");
    foreach ($platform_list as $v2 ){
        if(!in_array($v2['id'],$platfoms)){
            $key  = CONFIG_PRE."p_".$v2['id'];
            $redis->set($key,"default");
            $ret_array[$key] = "default";
        }
    }
    return $ret_array;
}
