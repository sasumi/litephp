<?php
namespace Lite\Cache;
abstract class Adapter {
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

	/**
	 * 设置缓存接口
	 * @param $cache_key
	 * @param $data
	 * @param int $expired
	 * @return mixed
	 */
	abstract public function set($cache_key, $data, $expired=60);

	/**
	 * 获取数据接口
	 * @param $cache_key
	 * @return mixed
	 */
	abstract public function get($cache_key);

	/**
	 * 删除缓存接口
	 * @param $cache_key
	 * @return mixed
	 */
	abstract public function delete($cache_key);

	/**
	 * 清空整个缓存区域接口
	 * @return mixed
	 */
	abstract public function flush();
}