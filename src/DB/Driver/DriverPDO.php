<?php
namespace Lite\DB\Driver;

use Lite\Component\Server;
use Lite\DB\Exception\ConnectException;
use Lite\DB\Query;
use Lite\Exception\Exception;
use Lite\Performance\Statistics;
use PDO as PDO;
use PDOStatement as PDOStatement;
use function Lite\func\_tl;

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
		if(isset($config['dns']) && $config['dns']){
			$dns = $config['dns'];
		}else if(isset($config['type']) && $config['type'] == 'sqlite'){
			$dns = 'sqlite:'.$config['host'];
		}else{
			$dns = "{$config['type']}:dbname={$config['database']};host={$config['host']}";
			if(isset($config['port']) && $config['port']){
				$dns .= ";port={$config['port']}";
			}
			if(isset($config['charset']) && $config['charset']){
				$dns .= ";charset={$config['charset']}";
			}
		}
		
		//build in connect attribute
		$opt = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];
		
		//PHP进程最大执行时间
		$max_exec_time = ini_get('max_execution_time');
		
		//最大连接超时时间与PHP进程超时时间差值
		$ttf = 2;
		
		//用户设定超时时间
		if(isset($config['connect_timeout']) && $config['connect_timeout'] > 0){
			$opt[PDO::ATTR_TIMEOUT] = $config['connect_timeout'];
		}
		//用户未设定超时时间，使用系统默认超时时间
		else if(!isset($config['connect_timeout']) && ($max_exec_time - $ttf > 0)){
			$opt[PDO::ATTR_TIMEOUT] = $max_exec_time - $ttf;
		}

		if(isset($config['pconnect']) && $config['pconnect']){
			$opt[PDO::ATTR_PERSISTENT] = true;
		}

		//connect & process windows encode issue
		try{
			$conn = new PDO($dns, $config['user'], $config['password'], $opt);
		}catch(\PDOException $e){
			$err = Server::inWindows() ? mb_convert_encoding($e->getMessage(), 'utf-8', 'gb2312') : $e->getMessage();
			throw new ConnectException(_tl('Database connect failed:{error}, HOST：{host}', [
				'error' => $err,
				'host'  => $config['host'],
			]), null, $config, $e->getCode(), $e);
		}
		$this->toggleStrictMode(isset($config['strict']) ? !!$config['strict'] : false, $conn);
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
	 * PDO判别是否为连接丢失异常
	 * @param \Exception $exception
	 * @return bool
	 */
	protected static function isConnectionLost(\Exception $exception){
		if($exception instanceof \PDOException){
			$lost_code_map = ['08S01', 'HY000'];
			if(in_array($exception->getCode(), $lost_code_map)){
				return true;
			}
		}
		return parent::isConnectionLost($exception);
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
		$result = $this->conn->query($sql.'');
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
	 * 设置SQL查询条数限制信息
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
	 * 获取所有行
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
	
	/**
	 * 数据库数据字典
	 * @return array
	 */
	public function getDictionary(){
		$tables = self::getTables();
		foreach($tables as $k=>$tbl_info){
			$fields = self::getFields($tbl_info['TABLE_NAME']);
			$tables[$k]['FIELDS'] = $fields;
		}
		return $tables;
	}
	
	/**
	 * 获取数据库表清单
	 * @return array
	 */
	public function getTables(){
		$query = "SELECT `table_name`, `engine`, `table_collation`, `table_comment` FROM `information_schema`.`tables` WHERE `table_schema`=?";
		$db = $this->getConfig('database');
		$sth = $this->conn->prepare($query);
		$sth->execute([$db]);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}
	
	/**
	 * 获取数据库表字段清单
	 * @param $table
	 * @return array
	 */
	public function getFields($table){
		$query = "SELECT `column_name`, `column_type`, `collation_name`, `is_nullable`, `column_key`, `column_default`, `extra`, `privileges`, `column_comment`
				    FROM `information_schema`.`columns`
				    WHERE `table_schema`=? AND `table_name`=?";
		$sth = $this->conn->prepare($query);
		$db = $this->getConfig('database');
		$sth->execute([$db, $table]);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
}
