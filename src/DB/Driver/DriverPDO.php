<?php
namespace Lite\DB\Driver;

use Lite\DB\Query;
use Lite\Exception\Exception;
use PDO as PDO;
use PDOStatement as PDOStatement;

/**
 *
 * Database operate class
 * this class no care current operate database read or write able
 * u should check this yourself
 *
 * 当前类不关注调用方的操作是读操作还是写入操作，
 * 这部分选择有调用方自己选择提供不同的初始化config配置
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
class DriverPDO extends DBAbstract {
	/**
	 * @var PDOStatement
	 */
    private $_last_query_result = null;

    /**
	 * PDO TYPE MAP
	 * @var array
	 */
	private static $PDO_TYPE_MAP = array(
		'bool'    => PDO::PARAM_BOOL,
		'null'    => PDO::PARAM_BOOL,
		'int'     => PDO::PARAM_INT,
		'float'   => PDO::PARAM_INT,
		'decimal' => PDO::PARAM_INT,
		'double'  => PDO::PARAM_INT,
		'string'  => PDO::PARAM_STR,
	);

	/**
	 * @var PDO pdo connect resource
	 */
	private $conn = null;

	/**
	 * @param array $config
	 * @param bool $re_connect
	 * @return \PDO
	 */
	public function connect(array $config, $re_connect = false) {
		if(!$re_connect && $this->conn){
			return $this->conn;
		}
		
		//process dns
		if($config['dns']){
			$dns = $config['dns'];
		} else if($config['type'] == 'sqlite'){
			$dns = 'sqlite:'.$config['host'];
		} else{
			$dns = "{$config['type']}:dbname={$config['database']};host={$config['host']}";
			if($config['port']){
				$dns .= ";port={$config['port']}";
			}
			if($config['charset']){
				$dns .= ";charset={$config['charset']}";
			}
		}
		
		//build connect attribute
		$opt = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];

		//PHP进程最大执行时间
		$max_exec_time = ini_get('max_execution_time');

		//最大连接超时时间与PHP进程超时时间差值
		$ttf = 2;

		//用户设定超时时间
		if($config['connect_timeout'] > 0){
			$opt[PDO::ATTR_TIMEOUT] = $config['connect_timeout'];
		}
		//用户未设定超时时间，使用系统默认超时时间
		else if(!isset($config['connect_timeout']) && ($max_exec_time - $ttf > 0)){
			$opt[PDO::ATTR_TIMEOUT] = $max_exec_time - $ttf;
		}

		if($config['pconnect']){
			$opt[PDO::ATTR_PERSISTENT] = true;
		}
		
		//connect & process windows encode issue
		if(stripos(PHP_OS, 'win') !== false){
			try{
				$conn = new PDO($dns, $config['user'], $config['password'], $opt);
			} catch(\PDOException $e){
				$msg = '数据库连接失败：“'.mb_convert_encoding($e->getMessage(), 'utf-8', 'gb2312').'”，HOST：'.$config['host'];
				throw new \PDOException($msg, $e->getCode(), $e);
			}
		} else{
			$conn = new PDO($dns, $config['user'], $config['password'], $opt);
		}
		$this->toggleStrictMode($config['strict'], $conn);
		$this->conn = $conn;
		return $conn;
	}

	/**
	 * 是否切换到严格模式
	 * @param bool $to_strict
	 * @param \PDO|null $conn
	 */
	public function toggleStrictMode($to_strict = false, PDO $conn = null){
		if($to_strict){
			$sql = "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
		} else {
			$sql = "set session sql_mode='NO_ENGINE_SUBSTITUTION'";
		}

		$conn = $conn ?: $this->conn;
		$conn->prepare($sql);
	}

	/**
	 * last insert id
	 * @param string $name
	 * @return string
	 */
	public function getLastInsertId($name = null) {
		return $this->conn->lastInsertId($name);
	}

	/**
	 * database query function
	 * @param string|Query $sql
	 * @return PDOStatement
	 */
	public function dbQuery($sql){
		$this->_last_query_result = null;
		$sql .= '';
		$result = $this->conn->query($sql);
		$this->_last_query_result = $result;
		return $result;
	}

	/**
	 * begin transaction
	 * @return bool
	 */
	public function beginTransaction(){
		return $this->conn->beginTransaction();
	}

	/**
	 * rollback
	 * @return bool
	 */
	public function rollback(){
		return $this->conn->rollBack();
	}

	/**
	 * commit transaction
	 * @return bool
	 */
	public function commit(){
		return $this->conn->commit();
	}

	/**
	 * cancelTransactionState
	 * @return bool
	 */
	public function cancelTransactionState(){
		return $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
	}

	/**
	 * quote param by database connector
	 * @param string $data
	 * @param string $type
	 * @return mixed
	 */
	public function quote($data, $type = null) {
		if(is_array($data)){
			$data = join(',', $data);
		}
		$type = in_array($type, self::$PDO_TYPE_MAP) ? $type : PDO::PARAM_STR;
		return $this->conn->quote($data, $type);
	}

	/**
	 * set SQL statement limit info
	 * @param $sql
	 * @param $limit
	 * @return string
	 * @throws Exception
	 */
	public function setLimit($sql, $limit) {
		if(preg_match('/\sLIMIT\s/i', $sql)){
			throw new Exception('SQL LIMIT BEEN SET:' . $sql);
		}
		if(is_array($limit)){
			return $sql . ' LIMIT ' . $limit[0] . ',' . $limit[1];
		}
		return $sql . ' LIMIT ' . $limit;
	}

	/**
	 * fetch all row
	 * @param PDOStatement $resource
	 * @return array | mixed
	 */
	public function fetchAll($resource) {
		$resource->setFetchMode(PDO::FETCH_ASSOC);
		return $resource->fetchAll();
	}

	/**
	 * fetch one column
	 * @param PDOStatement $rs
	 * @return string
	 */
	public static function fetchColumn(PDOStatement $rs) {
		return $rs->fetchColumn();
	}

	/**
	 * 查询最近db执行影响行数
	 * @description 该方法调用时候需要谨慎，需要避免_last_query_result被覆盖
	 * @return integer
	 */
	public function getAffectNum() {
		return $this->_last_query_result ? $this->_last_query_result->rowCount() : 0;
	}
}
