<?php
abstract class Cache {
	private static $instance_list;
	private $identify;
	private $driver;
	private $config;
	private $data;

	private function __construct($identify, $config){
		$this->identify = $identify;
		$this->config = $config;
	}

	public function instance($identify = 'session', array $config = array()){
		if(!self::$instance_list[$identify]){
			self::$instance_list[$identify] = new self($identify, $config);
		}
		return self::$instance[$identify];
	}

	public function set($cache_key, $data, $expired=60){
	}

	public function get($cache_key){

	}

	public function delete($cache_key){

	}

	public function cleanAll(){

	}
}