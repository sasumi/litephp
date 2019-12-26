<?php
namespace Lite\Cache;

use Lite\Exception\Exception;
use Redis as SysRedis;

class CacheRedis extends CacheAdapter{
	/** @var \Redis */
	private $redis = null;              //缓存对象
	private $defaultHost = '127.0.0.1'; //默认服务器地址
	private $defaultPort = 6379;        //默认端口号
	private $queueName = 'redis_queue';

	protected function __construct(array $config){
		if(!extension_loaded('redis')){
			throw new Exception('No redis extension found');
		}
		parent::__construct($config);
		$server = $config ?: [
			'host'     => $this->defaultHost,
			'port'     => $this->defaultPort,
			'database' => '',
			'password' => '',
		];
		$this->redis = new SysRedis();
		$this->redis->connect($server['host'], $server['port']);
		if($server['password']){
			$this->redis->auth($server['password']);
		}
		if($server['database']){
			$this->select($server['database']);
		}
	}

	public function select($db_index){
		return $this->redis->select($db_index);
	}

	public function swapDb($from_db_index, $to_db_index){
		return $this->redis->swapdb($from_db_index, $to_db_index);
	}

	public function set($cache_key, $data, $expired = 60){
		$data = serialize($data);
		return $this->redis->setex($cache_key, $expired, $data);
	}

	public function get($cache_key){
		$data = $this->redis->get($cache_key);
		return $data === false ? null : unserialize($data);
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
