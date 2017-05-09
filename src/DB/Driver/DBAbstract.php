<?php
namespace Lite\DB\Driver;

use Lite\Core\Hooker;
use Lite\Core\PaginateInterface;
use Lite\Core\RefParam;
use Lite\DB\Model;
use Lite\DB\Query;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use function Lite\func\dump;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2016/6/11
 * Time: 17:50
 */
abstract class DBAbstract{
	const EVENT_BEFORE_DB_QUERY = 'EVENT_BEFORE_DB_QUERY';
	const EVENT_AFTER_DB_QUERY = 'EVENT_AFTER_DB_QUERY';
	const EVENT_DB_QUERY_ERROR = 'EVENT_DB_QUERY_ERROR';
	const EVENT_BEFORE_DB_GET_LIST = 'EVENT_BEFORE_DB_GET_LIST';
	const EVENT_AFTER_DB_GET_LIST = 'EVENT_AFTER_DB_GET_LIST';
	const EVENT_ON_DB_QUERY_DISTINCT = 'EVENT_ON_DB_QUERY_DISTINCT';
	const EVENT_ON_DB_RECONNECT = 'EVENT_ON_DB_RECONNECT';

	//最大重试次数，如果该数据配置为0，将不进行重试
	public static $MAX_RECONNECT_COUNT = 10;

	private static $in_transaction_mode = false;
	private static $instance_list = array();

	// select查询去重
	// 这部分逻辑可能针对某些业务逻辑有影响，如：做某些操作之后立即查询这种
	// so，如果程序需要，可以通过 DBAbstract::distinctQueryOff() 关闭这个选项
	private static $QUERY_DISTINCT = true;
	private static $query_cache = array();

	/**
	 * @var Query current processing db query, support for exception handle
	 */
	private static $processing_query;
	
	/**
	 * database config
	 * @var array
	 */
	private $config = array();

	/**
	 * db record construct, connect to database
	 * @param array $config
	 */
	private function __construct($config){
		$this->config = $config;
		$this->connect($this->config);
		
		//charset
		if($this->config['charset']){
			$this->setCharset($this->config['charset']);
		}
		
		//timezone
		if($this->config['timezone']){
			$this->setTimeZone($this->config['timezone']);
		}
	}

	/**
	 * debug sql
	 */
	public static function debug(){
		Hooker::add(self::EVENT_BEFORE_DB_QUERY, function($query){
			dump($query);
		});
	}

	/**
	 * @param $sql
	 * @return array
	 * @throws Exception
	 */
	public function explain($sql){
		$sql = "EXPLAIN $sql";
		$rst = $this->query($sql);
		$data = $this->fetchAll($rst);
		return $data[0];
	}
	
	/**
	 * set charset to connection session
	 * @param $charset
	 * @throws \Lite\Exception\Exception
	 */
	public function setCharset($charset){
		$charset = str_replace('-', '', $charset);
		$this->query("SET NAMES '".$charset."'");
	}
	
	/**
	 * set timezone to connection session
	 * @param $timezone
	 * @throws \Lite\Exception\Exception
	 */
	public function setTimeZone($timezone){
		if(preg_match('/[a-zA-Z]/', $timezone)){
			$def_tz = date_default_timezone_get();
			date_default_timezone_set('UTC');
			date_default_timezone_set($timezone);
			$timezone = date('P');
			date_default_timezone_set($def_tz); //reset system default timezone setting
		}
		$this->query("SET time_zone = '$timezone'");
	}
	
	/**
	 * 单例
	 * @param array $config
	 * @return static
	 * @throws \Lite\Exception\Exception
	 */
	final public static function instance(array $config){
		$key = self::getInstanceKey($config);
		if(!self::$instance_list[$key]){
			/** @var self $class */
			$db_type = strtolower($config['type']) ?: 'mysql';
			$driver = strtolower($config['driver']) ?: 'pdo';
			
			if(($driver == 'mysql' || $driver == 'mysqli') && $db_type != 'mysql'){
				throw new Exception("database driver: [$driver] no fix type: [$db_type]");
			}
			
			switch($driver){
				case 'mysql':
					$ins = new DriverMySQL($config);
					break;
				
				case 'mysqli':
					$ins = new DriverMysqli($config);
					break;
				
				case 'pdo':
					$ins = new DriverPDO($config);
					break;
				
				default:
					throw new Exception("database config driver: [$driver] no support", 0, $config);
			}
			self::$instance_list[$key] = $ins;
		}
		return self::$instance_list[$key];
	}
	
	/**
	 * get database config
	 * @param null $key
	 * @return array|mixed
	 */
	public function getConfig($key = null){
		return $key ? $this->config[$key] : $this->config;
	}
	
	/**
	 * get instance key
	 * @param array $config
	 * @return string
	 */
	private static function getInstanceKey(array $config){
		return md5(serialize($config));
	}

	/**
	 * get current distinct query switch state
	 * @return bool
	 */
	public static function distinctQueryState(){
		return self::$QUERY_DISTINCT;
	}

	/**
	 * turn on distinct query cache
	 */
	public static function distinctQueryOn(){
		self::$QUERY_DISTINCT = true;
	}
	
	/**
	 * turn off distinct query cache
	 */
	public static function distinctQueryOff(){
		self::$QUERY_DISTINCT = false;
	}

	/**
	 * @return mixed
	 */
	public static function getProcessingQuery(){
		return self::$processing_query;
	}

	/**
	 * quote param by database connector
	 * @param string $data
	 * @param string $type
	 * @return mixed
	 */
	public function quote($data, $type = null){
		if(is_array($data)){
			$data = join(',', $data);
		}
		if($data === null){
			return 'null';
		}
		if(is_bool($data)){
			return $data ? 'TRUE' : 'FALSE';
		}
		if(!is_string($data) && is_numeric($data)){
			return $data;
		}
		return "'".addslashes($data)."'";
	}
	
	/**
	 * quote array
	 * @param $data
	 * @param array $types
	 * @return mixed
	 */
	public function quoteArray(array $data, array $types){
		foreach($data as $k => $item){
			$data[$k] = $this->quote($item, $types[$k]);
		}
		return $data;
	}
	
	/**
	 * get data by page
	 * @param \Lite\DB\Query $q
	 * @param PaginateInterface|array|number $pager
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	public function getPage(Query $q, $pager = null){
		$query = clone($q);
		if($pager instanceof PaginateInterface){
			$total = $this->getCount($query);
			$pager->setItemTotal($total);
			$limit = $pager->getLimit();
		} else{
			$limit = $pager;
		}
		if($limit){
			$query->limit($limit);
		}
		$param = new RefParam(array(
			'query'  => $query,
			'result' => null
		));
		Hooker::fire(self::EVENT_BEFORE_DB_GET_LIST, $param);
		if(!is_array($param['result'])){
			if(self::$QUERY_DISTINCT){
				$param['result'] = self::$query_cache[$query.'']; //todo 这里通过 isFRQuery 可以做全表cache
			}
			if(!isset($param['result'])){
				$rs = $this->query($param['query']);
				if($rs){
					$param['result'] = $this->fetchAll($rs);
					if(self::$QUERY_DISTINCT){
						self::$query_cache[$query.''] = $param['result'];
					}
				}
			} else{
				Hooker::fire(self::EVENT_ON_DB_QUERY_DISTINCT, $param);
			}
			Hooker::fire(self::EVENT_AFTER_DB_GET_LIST, $param);
		}
		return $param['result'] ?: array();
	}
	
	/**
	 * get all
	 * @param Query $query
	 * @return mixed
	 */
	public function getAll(Query $query){
		return $this->getPage($query, null);
	}
	
	/**
	 * get one row
	 * @param Query $query
	 * @return array | null
	 */
	public function getOne(Query $query){
		$rst = $this->getPage($query, 1);
		if($rst){
			return $rst[0];
		}
		return null;
	}
	
	/**
	 * 获取一个字段
	 * @param Query $query
	 * @param string $key
	 * @return mixed|null
	 */
	public function getField(Query $query, $key){
		$rst = $this->getOne($query);
		if($rst){
			return $rst[$key];
		}
		return null;
	}
	
	/**
	 * 更新数量
	 * @param string $table
	 * @param string $field
	 * @param integer $offset_count
	 * @return boolean
	 */
	public function updateCount($table, $field, $offset_count = 1){
		$prefix = $this->config['prefix'] ?: '';
		$query = $this->genQuery();
		$sql = "UPDATE {$prefix}{$table} SET {$field} = {$field}".($offset_count>0 ? " + {$offset_count}" : " - {$offset_count}");
		$query->setSql($sql);
		$this->query($query);
		return $this->getAffectNum();
	}
	
	/**
	 * 数据更新
	 * @param string $table
	 * @param array $data
	 * @param string $condition
	 * @param int $limit
	 * @return int affect line number
	 * @throws \Lite\Exception\Exception
	 */
	public function update($table, array $data, $condition = '', $limit = 1){
		if(empty($data)){
			throw new BizException('NO UPDATE DATA FOUND');
		}
		$query = $this->genQuery()->update()->from($table)->setData($data)->where($condition)->limit($limit);
		$this->query($query);
		return $this->getAffectNum();
	}
	
	/**
	 * replace data
	 * @param $table
	 * @param array $data
	 * @param string $condition
	 * @param $limit
	 * @return mixed
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 */
	public function replace($table, array $data, $condition = '', $limit = 0){
		if(empty($data)){
			throw new BizException('NO REPLACE DATA FOUND');
		}

		$count = $this->getCount($this->genQuery()->select()->from($table)->where($condition)->limit(1));
		if($count){
			$query = $this->genQuery()->update()->from($table)->setData($data)->where($condition)->limit($limit);
			$this->query($query);
			return $count;
		} else {
			$query = $this->genQuery()->insert()->from($table)->setData($data);
			$this->query($query);
			return $this->getAffectNum();
		}
	}
	
	/**
	 * @param $table
	 * @param $field
	 * @param int $offset
	 * @param string $statement
	 * @param int $limit
	 * @return int
	 */
	public function increase($table, $field, $offset = 1, $statement = '', $limit = 0){
		$off = $offset>0 ? "+ $offset" : "- $offset";
		$where = $statement ? "WHERE $statement" : '';
		$limit_str = $limit>0 ? "LIMIT $limit" : '';
		$query = "UPDATE `$table` SET `$field` = `$field` $off $where $limit_str";
		$this->query($query);
		return $this->getAffectNum();
	}
	
	/**
	 * 删除数据库数据
	 * @param $table
	 * @param $condition
	 * @param int $limit 参数为0表示不进行限制
	 * @return bool
	 */
	public function delete($table, $condition, $limit = 0){
		$query = $this->genQuery()->from($table)->delete()->where($condition);
		if($limit != 0){
			$query = $query->limit($limit);
		}
		$result = $this->query($query);
		return !!$result;
	}
	
	/**
	 * 数据插入
	 * @param $table
	 * @param array $data
	 * @param null $condition
	 * @return mixed
	 * @throws \Lite\Exception\Exception
	 */
	public function insert($table, array $data, $condition = null){
		if(empty($data)){
			throw new Exception('NO INSERT DATA FOUND');
		}
		$query = $this->genQuery()->insert()->from($table)->setData($data)->where($condition);
		return $this->query($query);
	}

	/**
	 * 产生Query对象
	 * @return Query
	 */
	protected function genQuery(){
		$prefix = $this->config['prefix'] ?: '';
		$ins = new Query();
		$ins->setTablePrefix($prefix);
		return $ins;
	}
	
	/**
	 * sql query
	 * @param $query
	 * @return mixed
	 * @throws \Lite\Exception\Exception
	 */
	final public function query($query){
		try{
			Hooker::fire(self::EVENT_BEFORE_DB_QUERY, $query, $this->config);
			self::$processing_query = $query;
			$result = $this->dbQuery($query);
			self::$processing_query = null;
			Hooker::fire(self::EVENT_AFTER_DB_QUERY, $query, $result);
			return $result;
		} catch(\Exception $ex){
			static $reconnect_count;
			if($reconnect_count < self::$MAX_RECONNECT_COUNT && stripos($ex->getMessage(), 'server has gone away')){
				Hooker::fire(self::EVENT_ON_DB_RECONNECT, $ex->getMessage(), $this->config);
				$this->connect($this->config);
				$reconnect_count++;
				return $this->query($query);
			}
			Hooker::fire(self::EVENT_DB_QUERY_ERROR, $ex, $query, $this->config);
			throw new Exception($ex->getMessage(), 0, array(
				'query' => $query.'',
				'host'  => $this->getConfig('host')
			));
		}
	}
	
	/**
	 * 执行查询
	 * 规划dbQuery代替实际的数据查询主要目的是：为了统一对数据库查询动作做统一的行为监控
	 * @param $query
	 * @return mixed
	 */
	public abstract function dbQuery($query);
	
	/**
	 * 获取条数
	 * @param $sql
	 * @return mixed
	 */
	public abstract function getCount($sql);
	
	/**
	 * 获取操作影响条数
	 * @return integer
	 */
	public abstract function getAffectNum();
	
	/**
	 * 获取所有记录
	 * @param $resource
	 * @return mixed
	 */
	public abstract function fetchAll($resource);
	
	/**
	 * 设置限额
	 * @param $sql
	 * @param $limit
	 * @return mixed
	 */
	public abstract function setLimit($sql, $limit);
	
	/**
	 * 获取最后插入ID
	 * @return mixed
	 */
	public abstract function getLastInsertId();
	
	/**
	 * 事务提交
	 * @return mixed
	 */
	public abstract function commit();
	
	/**
	 * 事务回滚
	 * @return mixed
	 */
	public abstract function rollback();
	
	/**
	 * 开始事务操作
	 * @return mixed
	 */
	public abstract function beginTransaction();
	
	/**
	 * 取消事务操作状态
	 * @return mixed
	 */
	public abstract function cancelTransactionState();
	
	/**
	 * connect to specified config database
	 * @param array $config
	 * @param boolean $re_connect 是否重新连接
	 * @throws Exception
	 * @return resource
	 */
	public abstract function connect(array $config, $re_connect = false);
}