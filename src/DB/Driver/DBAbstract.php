<?php
namespace Lite\DB\Driver;

use Lite\Component\UI\PaginateInterface;
use Lite\Core\Hooker;
use Lite\Core\RefParam;
use Lite\DB\Query;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use function Lite\func\dump;

/**
 * 数据库接口抽象类
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
	const EVENT_ON_DB_CONNECT = 'EVENT_ON_DB_CONNECT';
	const EVENT_ON_DB_QUERY_DISTINCT = 'EVENT_ON_DB_QUERY_DISTINCT';
	const EVENT_ON_DB_RECONNECT = 'EVENT_ON_DB_RECONNECT';
	
	//最大重试次数，如果该数据配置为0，将不进行重试
	public static $MAX_RECONNECT_COUNT = 10;
	
	//重新连接间隔时间（毫秒）
	public static $RECONNECT_INTERVAL = 1000;
	
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
	 * 数据库连接初始化，连接数据库，设置查询字符集，设置时区
	 * @param array $config
	 */
	private function __construct($config){
		$this->config = $config;
		if(!$this->config['type']){
			$this->config['type'] = 'mysql';
		}
		
		if($this->config['charset']){
			$this->config['charset'] = static::fixCharsetCode($this->config['charset'], $this->config['type']);
		}
		
		Hooker::fire(self::EVENT_ON_DB_CONNECT, $this->config);
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
		Hooker::add(self::EVENT_ON_DB_CONNECT, function($config){
			dump('DB connecting: '.json_encode($config));
		});
		Hooker::add(self::EVENT_BEFORE_DB_QUERY, function($query){
			dump($query.'');
		});
	}
	
	/**
	 * 解析SQL语句
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
	 * 设置查询字符集
	 * @param $charset
	 * @throws \Lite\Exception\Exception
	 */
	public function setCharset($charset){
		$this->query("SET NAMES '".$charset."'");
	}
	
	/**
	 * 修正MySQL数据库驱动编码问题
	 * @param $charset
	 * @param $type
	 * @return string
	 */
	public static function fixCharsetCode($charset, $type){
		if($type == 'mysql'){
			$charset = str_replace('-', '', $charset);
		} else if($charset == 'utf8'){
			$charset = 'utf-8';
		}
		return $charset;
	}
	
	/**
	 * 设置时区
	 * @param string $timezone
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
		static $instance_list;
		if(!$instance_list){
			$instance_list = [];
		}
		
		if(!$instance_list[$key]){
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
					$ins = new DriverMySQLi($config);
					break;
				
				case 'pdo':
					$ins = new DriverPDO($config);
					break;
				
				default:
					throw new Exception("database config driver: [$driver] no support", 0, $config);
			}
			$instance_list[$key] = $ins;
		}
		return $instance_list[$key];
	}
	
	/**
	 * 获取数据库配置
	 * @param null $key
	 * @return array|mixed
	 */
	public function getConfig($key = null){
		return $key ? $this->config[$key] : $this->config;
	}
	
	/**
	 * 获取单例键值
	 * @param array $config
	 * @return string
	 */
	private static function getInstanceKey(array $config){
		return md5(serialize($config));
	}
	
	/**
	 * 获取当前去重查询开启状态
	 * @return bool
	 */
	public static function distinctQueryState(){
		return self::$QUERY_DISTINCT;
	}
	
	/**
	 * 打开去重查询模式
	 */
	public static function distinctQueryOn(){
		self::$QUERY_DISTINCT = true;
	}
	
	/**
	 * 关闭去重查询模式
	 */
	public static function distinctQueryOff(){
		self::$QUERY_DISTINCT = false;
	}
	
	/**
	 * 以非去重模式（强制查询模式）进行查询
	 * @param callable $callback
	 */
	public static function noDistinctQuery(callable $callback){
		$st = self::$QUERY_DISTINCT;
		self::distinctQueryOn();
		call_user_func($callback);
		self::$QUERY_DISTINCT = $st;
	}
	
	/**
	 * 获取正在提交中的查询
	 * @return mixed
	 */
	public static function getProcessingQuery(){
		return self::$processing_query;
	}
	
	/**
	 * 转义数据，缺省为统一使用字符转义
	 * @param string $data
	 * @param string $type @todo 支持数据库查询转义数据类型
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
	 * 转义数组
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
	 * 获取一页数据
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
	 * 获取所有查询记录
	 * @param Query $query
	 * @return mixed
	 */
	public function getAll(Query $query){
		return $this->getPage($query, null);
	}
	
	/**
	 * 获取一条查询记录
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
		$off = $offset>0 ? "+ $offset" : "$offset";
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
			if($reconnect_count<static::$MAX_RECONNECT_COUNT && static::isConnectionLost($ex)){
				//间隔时间之后重新连接
				if(static::$RECONNECT_INTERVAL){
					usleep(static::$RECONNECT_INTERVAL*1000);
				}
				Hooker::fire(self::EVENT_ON_DB_RECONNECT, $ex->getMessage(), $this->config);
				$reconnect_count++;
				try{
					$this->connect($this->config, true);
				} catch(\Exception $e){
					//ignore reconnect exception
				}
				return $this->query($query);
			}
			Hooker::fire(self::EVENT_DB_QUERY_ERROR, $ex, $query, $this->config);
			throw new Exception($ex->getMessage().$query.'', 0, array(
				'query' => $query.'',
				'host'  => $this->getConfig('host')
			));
		}
	}
	
	/**
	 * 根据message检测服务器是否丢失、断开、重置链接
	 * @param \Exception $exception
	 * @return bool
	 */
	protected static function isConnectionLost(\Exception $exception){
		$error = $exception->getMessage();
		$ms = ['server has gone away', 'shut down'];
		foreach($ms as $kw){
			if(stripos($kw, $error) !== false){
				return true;
			}
		}
		return false;
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
	public function getCount($sql){
		$sql .= '';
		$sql = str_replace(array("\n", "\r"), '', trim($sql));
		if(preg_match('/^\s*SELECT.*?\s+FROM\s+/i', $sql)){
			if(preg_match('/\sGROUP\s+by\s/i', $sql) ||
				preg_match('/^\s*SELECT\s+DISTINCT\s/i', $sql)){
				$sql = "SELECT COUNT(*) AS __NUM_COUNT__ FROM ($sql) AS cnt_";
			} else {
				$sql = preg_replace('/^\s*select.*?\s+from/i', 'SELECT COUNT(*) AS __NUM_COUNT__ FROM', $sql);
				$sql = preg_replace('/\sorder\s+by\s.*$/i', '', $sql); //为了避免order中出现field，在select里面定义，select里面被删除了，导致order里面的field未定义。
			}
			$result = $this->getOne(new Query($sql));
			if($result){
				return (int) $result['__NUM_COUNT__'];
			}
		}
		return 0;
	}
	
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
	 * 连接数据库接口
	 * @param array $config <p>数据库连接配置，
	 * 格式为：['type'=>'', 'driver'=>'', 'charset' => '', 'host'=>'', 'database'=>'', 'user'=>'', 'password'=>'', 'port'=>'']
	 * </p>
	 * @param boolean $re_connect 是否重新连接
	 * @throws Exception
	 * @return resource
	 */
	public abstract function connect(array $config, $re_connect = false);
}