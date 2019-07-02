<?php
namespace Lite\Cache;

use Lite\Exception\Exception;
use Redis as SysRedis;

class CacheRedis extends CacheAdapter{
	/** @var \Redis */
	private $redis = null;        //缓存对象
	private $defaultHost = '127.0.0.1'; //默认服务器地址
	private $defaultPort = 6379;       //默认端口号
	private $queueName = 'redis_queue';

	protected function __construct(array $config){
		if(!extension_loaded('redis')){
			throw new Exception('No redis extension found');
		}
		parent::__construct($config);
		$server = $config['server'] ?: ['host' => $this->defaultHost, 'port' => $this->defaultPort];
		if(is_string($server)){
			$server = explode(':', $server);
		}
		$this->redis = new SysRedis();
		$this->connect($server['host'], $server['port']);
	}

	/**
	 * 添加服务器到连接池
	 * @param string $host 服务地址
	 * @param int $port 服务端口
	 * @return bool true:成功;false:失败;
	 * @internal param string $address 服务器地址
	 */
	private function connect($host, $port){
		return $this->redis->connect($host, $port);
	}

	public function set($cache_key, $data, $expired = 60){
		$data = serialize($data);
		return $this->redis->setex($cache_key, $expired, $data);
	}

	public function get($cache_key){
		$data = $this->redis->get($cache_key);
		return unserialize($data);
	}

	public function delete($cache_key){
		$this->redis->delete($cache_key);
	}

	public function flush(){
		return $this->redis->flushAll();
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
		return $this->redis->del($this->queueName);
	}

	/**
	 * 取得队列的长度
	 */
	public function lSize(){
		$this->redis->lSize($this->queueName);
	}

	/**
	 * 从队列中取出多少个数据
	 * @param $num
	 * @return mixed
	 */
	public function lrang($num){
		return $this->redis->lRange($this->queueName, 0, $num);
	}

	/**
	 * 给队列添加一个数据
	 * @param $value
	 */
	public function rpush($value){
		$this->redis->rPush($this->queueName, $value);
	}

	/**
	 * 从队列中取出一个数据
	 */
	public function lpop(){
		return $this->redis->lPop($this->queueName);
	}

	/**
	 * 从队列中删除数据
	 * @param number $start 开始index
	 * @param number $stop 结束index
	 * @return mixed
	 */
	public function ltrim($start, $stop){
		return $this->redis->lTrim($this->queueName, $start, $stop);
	}
}
