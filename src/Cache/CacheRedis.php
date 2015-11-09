<?php
namespace Lite\Cache;

use Lite\Exception\Exception;
use Redis as SysRedis;

class CacheRedis extends CacheAdapter{
	/** @var null|\Redis */
	private $cache = null;        //缓存对象
	private $defaultHost = '127.0.0.1'; //默认服务器地址
	private $defaultPort = 6379;       //默认端口号
	private $queueName = 'redis_queue';

	protected function __construct(array $config){
		if(!extension_loaded('redis')){
			throw new Exception('NO REDIS PLUGIN FOUND');
		}
		$servers = $config['servers'];
		$this->cache = new SysRedis();
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
		return $this->cache->connect($host, $port);
	}

	public function set($cache_key, $data, $expired = 60){
		$data = serialize($data);
		return $this->cache->setex($cache_key, $expired, $data);
	}

	public function get($cache_key){
		$data = $this->cache->get($cache_key);
		return unserialize($data);
	}

	public function delete($cache_key){
		$this->cache->delete($cache_key);
	}

	public function flush(){
		return $this->cache->flushAll();
	}

	public function addServer($host, $port){
		return $this->cache->connect($host, $port);
	}

	/**
	 * 设置队列名称
	 * @param $queueName
	 */
	public function setQueueName($queueName){
		$this->queueName = $queueName;
	}

	/**
	 * 删除队列
	 */
	public function del(){
		return $this->cache->del($this->queueName);
	}

	/**
	 * 取得队列的长度
	 * @return mixed
	 */
	public function lSize(){
		$this->cache->lSize($this->queueName);
	}

	/**
	 * 从队列中取出多少个数据
	 * @param $num
	 * @return mixed
	 */
	public function lrang($num){
		return $this->cache->lRange($this->queueName, 0, $num);
	}

	/**
	 * 给队列添加一个数据
	 * @param $value
	 */
	public function rpush($value){
		$this->cache->rPush($this->queueName, $value);
	}

	/**
	 * 从队列中取出一个数据
	 */
	public function lpop(){
		return $this->cache->lPop($this->queueName);
	}

	/**
	 * 从队列中删除数据
	 * @param number $start 开始index
	 * @param number $stop 结束index
	 * @return mixed
	 */
	public function ltrim($start, $stop){
		return $this->cache->lTrim($this->queueName, $start, $stop);
	}
}
