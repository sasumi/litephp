<?php
namespace Lite\Cache;
use Lite\Exception\Exception;

/**
 * Class CacheAdapter
 * @package Lite\Cache
 */
abstract class CacheAdapter implements CacheInterface{
	private static $instances;
	private $config;

	/**
	 * CacheAdapter constructor.
	 * @param array $config
	 */
	protected function __construct($config = []){
		$this->setConfig($config);
	}

	/**
	 * 单例
	 * @param array $config
	 * @return CacheAdapter
	 */
	final public static function instance(array $config = array()){
		$class = get_called_class();
		$key = $class.serialize($config);
		if(!self::$instances[$key]){
			self::$instances[$key] = new $class($config);
		}
		return self::$instances[$key];
	}

	/**
	 * 快速调用方法，不提供配置参数传入
	 * @param $key
	 * @param callable $fetcher
	 * @param int $expired_seconds
	 * @return mixed
	 * @throws \Lite\Exception\Exception
	 */
	final public function cache($key, callable $fetcher, $expired_seconds = 60){
		$cache_class = get_called_class();
		if($cache_class == self::class){
			throw new Exception('Cache method not callable in '.self::class);
		}
		$key .= ':'.$expired_seconds;
		$data = $this->get($key);
		if(!isset($data)){
			$data = call_user_func($fetcher);
			$this->set($key, $data, $expired_seconds);
		}
		return $data;
	}

	/**
	 * set config
	 * @param string $key
	 * @return mixed
	 */
	public function getConfig($key = ''){
		if($key){
			return $this->config[$key];
		}
		return $this->config;
	}

	/**
	 * get config
	 * @param $config
	 */
	public function setConfig($config){
		$this->config = $config;
	}
}