<?php
namespace Lite\Cache;
abstract class CacheAdapter implements CacheInterface{
	private static $instances;
	private $config;

	protected function __construct($config){
		$this->setConfig($config);
	}

	public function getConfig($key=''){
		if($key){
			return $this->config[$key];
		}
		return $this->config;
	}

	public function setConfig($config){
		$this->config = $config;
	}

	public static function instance(array $config = array()){
		$class = get_called_class();
		if(!self::$instances[$class]){
			self::$instances[$class] = new $class($config);
		}
		return self::$instances[$class];
	}
}