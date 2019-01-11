<?php

class CI_Ocs{

    var $ocs_address = '9c966cc2180411e4.m.cnhzalicm10pub001.ocs.aliyuncs.com';
    var $ocs_port = '11211';
    var $ocs_account = '9c966cc2180411e4';
    var $ocs_passwd = 'Luscfday2014';
    var $mem_obj;
    function CI_Ocs(){
        include('MemcacheSASL.php');
    }

    function connect(){
        $m = new MemcacheSASL;
        $m->setOption(Memcached::OPT_COMPRESSION, false); //关闭压缩功能
        $m->setOption(Memcached::OPT_BINARY_PROTOCOL, true); //使用binary二进制协议
        $m->addServer($this->ocs_address, $this->ocs_port);
        $m->setSaslAuthData($this->ocs_account, $this->ocs_passwd);
        $this->mem_obj = $m;
        return true;
    }

    function set($key,$value){
        $this->mem_obj->set($key, $value);
    }

    function get($key){
       return $this->mem_obj->get($key);
    }

	function delete($key) {
		return $this->mem_obj->delete($key);
	}

	function quit(){
        //$this->mem_obj->quit();
    }

}
