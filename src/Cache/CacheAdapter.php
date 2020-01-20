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
	 * @return static
	 */
	final public static function instance(array $config = array()){
		$class = get_called_class();
		$key = $class.serialize($config);
		if(!isset(self::$instances[$key]) || !self::$instances[$key]){
			self::$instances[$key] = new $class($config);
		}
		return self::$instances[$key];
	}

	/**
	 * 快速调用方法，不提供配置参数传入
	 * @param string $key 缓存key
	 * @param callable $fetcher 数据获取回调
	 * @param int $expired_seconds 缓存过期时间
	 * @param bool $refresh_cache 是否刷新缓存，默认false为仅在缓存过期时才更新
	 * @return mixed
	 */
	final public function cache($key, callable $fetcher, $expired_seconds = 60, $refresh_cache = false){
		$cache_class = get_called_class();
		if($cache_class == self::class){
			throw new Exception('Cache method not callable in '.self::class);
		}

		if($refresh_cache){
			$data = call_user_func($fetcher);
			$this->set($key, $data, $expired_seconds);
			return $data;
		}

		$data = $this->get($key);
		if(!isset($data)){
			$data = call_user_func($fetcher);
			$this->set($key, $data, $expired_seconds);
		}
		return $data;
	}

	/**
	 * set cache distributed
	 * @param $cache_prefix_key
	 * @param array $data_list
	 * @param int $expired
	 */
	final public function setDistributed($cache_prefix_key, array $data_list, $expired = 60){
		foreach($data_list as $k=>$data){
			$this->set($cache_prefix_key.$k, $data, $expired);
		}
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
