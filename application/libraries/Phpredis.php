<?php
// Redis helper
//ex:
//$this->load->library('phpredis');
//$this->redis = $this->phpredis->getConn();
//
//$result = $this->redis->set("a-list",2222,3600);
//echo $this->redis->get("a-list");
class phpredis{

    public static $conn;    // 单例redis连接
    public $redis_config;   // 配置config/redis.php

    // 如果没有连接过redis, 那么自动连接redis
    public function __construct() {
        $this->loadConfig();
    	if($this->redis_config["enable"] && !self::$conn) {
    		$this->connect();
    	}
    }	

    // 连接redis
    private function connect() {
        if(class_exists('Redis')) {
            $redis = new Redis();
            $redis->connect($this->redis_config["address"], $this->redis_config["port"]);
            if($this->redis_config['password']){
                if ( $redis->auth($this->redis_config["password"]) ) {
                    self::$conn = $redis;
                }
            }else{
                self::$conn = $redis;
            }
        }
        $this->checkConn();
    }

    // 测试一下连接是否有效
    private function checkConn() {
        if (!self::$conn) {
            return;
        }

        try {
            $reply = self::$conn->ping();
        } catch (Exception $e) {
            self::$conn = null;
        }

        if($reply != "+PONG") {
            self::$conn = null;
        }
    }    

    // 获取已存在的redis连接
    public function getConn() {
        $this->checkConn();

    	if (self::$conn) {
    		return self::$conn;
    	} else {
    		return false;
    	}
    }

    // 载入配置
    private function loadConfig() {
        $CI = & get_instance();
        $CI->config->load("redis", true, true);
        $this->redis_config = $CI->config->item('redis');
    }

}