<?php
class CI_Memcached{
    //demo
//$this->load->library("memcached");
//$session_arr = $this->memcached->set("a-list",111111);
//echo $this->memcached->get("a-list");

//本地memcached
    public static $mem_obj;
    public $memcached_config;

    function CI_Memcached(){
        $CI = & get_instance();
        $CI->config->load("memcached", true, true);
        $this->memcached_config = $CI->config->item('memcached');

        if($this->memcached_config["enable"] && !self::$mem_obj) {
            $this->connect();
        }
    }

    function connect(){
        $memc = new Memcached;
        $memc->setOption(Memcached::OPT_COMPRESSION, false);
        $memc->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        $memc->addServer($this->memcached_config['address'], $this->memcached_config['port']);
        // }
        $this->mem_obj = $memc;
    }

    function set($key,$value, $expiration = 0){
        $this->mem_obj->set($key, $value, $expiration);
    }

    function get($key){
        return $this->mem_obj->get($key);
    }

    function quit(){
        //$this->mem_obj->quit();
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    function delete($key)
    {
        $this->mem_obj->delete($key);
    }

}
