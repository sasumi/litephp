<?php
namespace Lite\Cache;

use Lite\Exception\Exception;
use Memcache as SysMem;

class CacheMemcache extends CacheAdapter{
	/** @var SysMem * */
	private $cache;        //缓存对象
	private $defaultHost = '127.0.0.1'; //默认服务器地址
	private $defaultPort = 11211;       //默认端口号

	public function __construct(array $config){
		if(!extension_loaded('memcache')){
			throw new Exception('can not find the memcache extension', 403);
		}

		$servers = $config['servers'];
		$this->cache = new SysMem;
		if(!empty($servers)){
			foreach($servers as $server){
				$this->addServe($server);
			}
		}else{
			$this->addServe($this->defaultHost.':'.$this->defaultPort);
		}
		parent::__construct($config);
	}

	/**
	 * @brief  添加服务器到连接池
	 * @param  string $address 服务器地址
	 * @return bool   true:成功;false:失败;
	 */
	private function addServe($address){
		list($host, $port) = explode(':', $address);
		$port = $port ?: $this->defaultPort;
		return $this->cache->addserver($host, $port);
	}

	/**
	 * @brief  写入缓存
	 * @param  string $key 缓存的唯一key值
	 * @param  mixed $data 要写入的缓存数据
	 * @param int $expire 缓存数据失效时间,单位：秒
	 * @return bool   true:成功;false:失败;
	 */
	public function set($key, $data, $expire = 0){
		return $this->cache->set($key, $data, MEMCACHE_COMPRESSED, $expire);
	}

	/**
	 * @brief  读取缓存
	 * @param  string $key 缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed  读取出的缓存数据;null:没有取到数据;
	 */
	public function get($key){
		return $this->cache->get($key);
	}

	/**
	 * @brief  删除缓存
	 * @param  string $key 缓存的唯一key值
	 * @param int|string $timeout 在间隔单位时间内自动删除,单位：秒
	 * @return bool true:成功; false:失败;
	 */
	public function delete($key, $timeout = 0){
		return $this->cache->delete($key, $timeout);
	}

	/**
	 * @brief  删除全部缓存
	 */
	public function flush(){
		$this->cache->flush();
	}
}
