<?php
namespace Lite\DB;

use Lite\Component\Paginate;
use Lite\Core\Hooker;
use Lite\Core\RefParam;
use Lite\Exception\Exception;
use \PDO as PDO;
use \PDOStatement as PDOStatement;
use \PDOException as PDOException;

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
final class Record {
	const EVENT_BEFORE_DB_QUERY = 'EVENT_BEFORE_DB_QUERY';
	const EVENT_AFTER_DB_QUERY = 'EVENT_AFTER_DB_QUERY';
	const EVENT_DB_QUERY_ERROR = 'EVENT_DB_QUERY_ERROR';
	const EVENT_BEFORE_DB_GET_LIST = 'EVENT_BEFORE_DB_GET_LIST';
	const EVENT_AFTER_DB_GET_LIST = 'EVENT_AFTER_DB_GET_LIST';

	private static $in_transaction_mode = false;
	private static $instance_list = array();

	// select查询去重
	// 这部分逻辑可能针对某些业务逻辑有影响，如：做某些操作之后立即查询这种
	// so，如果程序需要，可以关闭这个选项
	public static $QUERY_DEDUPLICATION = true;

	/**
	 * @var PDO pdo connect resource
	 */
	private $conn = null;

	/**
	 * database config
	 * @var array
	 */
	private $config = array();

	/**
	 * @var PDOStatement
	 */
	private $_last_query_result = null;

	/**
	 * db record construct, connect to database
	 * @param array $config
	 */
	private function __construct($config) {
		$this->config = $config;
		$this->connect($this->config);
		if(self::$in_transaction_mode){
			$this->conn->beginTransaction();
		}
	}

	/**
	 * 单例
	 * @param array $config
	 * @return Record
	 */
	public static function instance(array $config) {
		$key = self::getInstanceKey($config);
		if(!self::$instance_list[$key]){
			self::$instance_list[$key] = new self($config);
		}
		return self::$instance_list[$key];
	}

	/**
	 * get instance key
	 * @param array $config
	 * @return string
	 */
	private static function getInstanceKey(array $config) {
		return md5(serialize($config));
	}

	/**
	 * connect to specified config database
	 * @param array $config
	 * @param boolean $re_connect 是否重新连接
	 * @throws Exception
	 * @return resource
	 */
	public function connect(array $config, $re_connect = false) {
		if(!$re_connect && $this->conn){
			return $this->conn;
		}

		if($config['dns']){
			$dns = $config['dns'];
		}
		else if($config['driver'] == 'sqlite'){
			$dns = 'sqlite:' . $config['host'];
		}else{
			$dns = "{$config['driver']}:dbname={$config['database']};host={$config['host']}";
			if($config['port']){
				$dns .= ";port={$config['port']}";
			}
		}

		$opt  = array();
		if($config['pconnect']){
			$opt[PDO::ATTR_PERSISTENT] = true;
		}

		$conn = new PDO($dns, $config['user'], $config['password'], $opt);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if($config['charset']){
			$conn->exec("SET NAMES '".$config['charset']."'");
		}
		$this->conn = $conn;
		return $conn;
	}

	/**
	 * begin all transaction
	 * @return array
	 */
	public static function beginTransactionAll(){
		self::$in_transaction_mode = true;
		$result_list = array();

		/* @var Record $ins **/
		foreach(self::$instance_list as $ins){
			$result_list[] = $ins->beginTransaction();
		}
		return $result_list;
	}

	/**
	 * rollback all transaction
	 */
	public static function rollbackAll(){
		/* @var Record $ins **/
		$result_list = array();
		foreach(self::$instance_list as $ins){
			$result_list[] = $ins->rollback();
		}
		return $result_list;
	}

	/**
	 * commit all transaction
	 * @return array
	 */
	public static function commitAll(){
		/* @var Record $ins **/
		$result_list = array();
		foreach(self::$instance_list as $ins){
			$result_list[] = $ins->commit();
		}
		return $result_list;
	}

	/**
	 * cancel all transaction state
	 * @return array
	 */
	public static function cancelTransactionStateAll(){
		/* @var Record $ins **/
		$result_list = array();
		foreach(self::$instance_list as $ins){
			$ins->cancelTransactionState();
		}
		self::$in_transaction_mode = false;
		return $result_list;
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
	 * get database table description
	 * @param $table
	 */
	public function getTableMeta($table){

	}

	/**
	 * get column description
	 * @param $table
	 * @param $column_id
	 */
	public function getColumnMeta($table, $column_id){

	}

	/**
	 * database query
	 * @param string|Query $sql
	 * @return PDOStatement
	 * @throws Exception
	 */
	public function query($sql){
		$this->_last_query_result = null;
		$sql .= '';
		try {
			Hooker::fire(self::EVENT_BEFORE_DB_QUERY, $sql, $this->conn);
			$result = $this->conn->query($sql);
			$this->_last_query_result = $result;
			Hooker::fire(self::EVENT_AFTER_DB_QUERY, $sql, $result);
			return $result;
		} catch (PDOException $ex){
			Hooker::fire(self::EVENT_DB_QUERY_ERROR, $ex, $sql, $this->conn);
			throw new Exception($ex->getMessage(), null, $sql);
		}
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
	 * @param string $string
	 * @param string $type
	 * @return mixed
	 */
	public function quote($string, $type = null) {
		return $this->conn->quote($string, $type);
	}

	/**
	 * set SQL statement limit info
	 * @param $sql
	 * @param $limit
	 * @return string
	 * @throws Exception
	 */
	public function setLimit($sql, $limit) {
		if(preg_match('/\slimit\s/', $sql)){
			throw new Exception('SQL LIMIT BEEN SET:' . $sql);
		}
		if(is_array($limit)){
			return $sql . ' LIMIT ' . $limit[0] . ',' . $limit[1];
		}
		return $sql . ' LIMIT ' . $limit;
	}

	/**
	 * fetch one row
	 * @param PDOStatement $resource
	 * @return array()
	 */
	public static function fetchRow(PDOStatement $resource) {
		$resource->setFetchMode(PDO::FETCH_NUM);
		return $resource->fetch();
	}

	/**
	 * fetch assoc row
	 * @param PDOStatement $resource
	 * @return array | mixed
	 */
	public static function fetchAssoc(PDOStatement $resource) {
		$resource->setFetchMode(PDO::FETCH_ASSOC);
		return $resource->fetch();
	}

	/**
	 * fetch all row
	 * @param PDOStatement $resource
	 * @return array | mixed
	 */
	public static function fetchAll(PDOStatement $resource) {
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
	 * get data count
	 * @param string $sql
	 * @return integer
	 */
	public function getCount($sql) {
		$sql .= '';
		$sql = str_replace(array("\n", "\r"), '', $sql);
		if(preg_match('/^\s*select.*?\s+from\s+/i', $sql)){
			if(preg_match('/\sgroup\s+by\s/i', $sql) ||
				preg_match('/^\s*select\s+distinct\s/i', $sql)){
				$sql = "SELECT COUNT(*) AS __NUM_COUNT__ FROM ($sql) AS cnt_";
			} else {
				$sql = preg_replace('/^\s*select.*?\s+from/i', 'SELECT COUNT(*) AS __NUM_COUNT__ FROM', $sql);
			}
			$result = $this->getOne(new Query($sql));
			if($result){
                return (int) $result['__NUM_COUNT__'];
			}
		}
		return 0;
	}

	/**
	 * get data by page
	 * @param $query
	 * @param Paginate | mixed $pager
	 * @return array
	 */
	public function getPage(Query $query, $pager = null) {
		if($pager instanceof Paginate){
			$total = $this->getCount($query);
			$pager->setItemTotal($total);
			$limit = $pager->getLimit();
		}else{
			$limit = $pager;
		}
		if($limit){
			$query->limit($limit);
		}
		$param = new RefParam(array(
			'query' => $query,
			'result' => null
		));
		Hooker::fire(self::EVENT_BEFORE_DB_GET_LIST, $param);
		if(!is_array($param['result'])){
			$rs = $this->query($param['query']);
			if($rs){
				$param['result'] = self::fetchAll($rs);
				Hooker::fire(self::EVENT_AFTER_DB_GET_LIST, $param);
			}
		}
		return $param['result'] ?: array();
	}

	/**
	 * get all
	 * @param Query $query
	 * @return mixed
	 */
	public function getAll(Query $query) {
		return $this->getPage($query, null);
	}

	/**
	 * get one row
	 * @param Query $query
	 * @return array | null
	 */
	public function getOne(Query $query) {
		$rst = $this->getPage($query, 1);
		if($rst){
			return $rst[0];
		}
		return null;
	}

	/**
	 * get one field
	 * @param Query $query
	 * @param string $key
	 * @return mixed|null
	 */
	public function getField(Query $query, $key) {
		$rst = $this->getOne($query);
		if($rst){
			return $rst[$key];
		}
		return null;
	}

	/**
	 * update count for specified field
	 * @todo to be test
	 * @param string $table
	 * @param string $field
	 * @param integer $offset_count
	 * @return boolean
	 */
	public function updateCount($table, $field, $offset_count = 1) {
		$prefix = $this->config['prefix'] ?: '';
		$query = $this->genQuery();
		$sql = "UPDATE {$prefix}{$table} SET {$field} = {$field}".
			($offset_count > 0 ? " + {$offset_count}" : " - {$offset_count}");
		$query->setSql($sql);
		$this->query($query);
		return $this->getAffectNum();
	}

	/**
	 * update database
	 * @param string $table
	 * @param array $data
	 * @param string $condition
	 * @param int $limit
	 * @return int affect line number
	 * @throws \Lite\Exception\Exception
	 */
	public function update($table, array $data, $condition = '', $limit=1) {
		if(!empty($data)){
			foreach($data as $k=>$item){
				$data[$k] = $this->conn->quote($item);
			}
		} else {
			throw new Exception('NO UPDATE DATA FOUND');
		}
		$query = $this->genQuery()
			->update()
			->from($table)
			->setData($data)
			->where($condition)
			->limit($limit);
		$this->query($query);
		return $this->getAffectNum();
	}

	/**
	 * 查询最近db执行影响行数
	 * @description 该方法调用时候需要谨慎，需要避免_last_query_result被覆盖
	 * @return integer
	 *
	 */
	public function getAffectNum() {
		return $this->_last_query_result ? $this->_last_query_result->rowCount() : 0;
	}

	/**
	 * 删除数据库数据
	 * @param $table
	 * @param $condition
	 * @param int $limit 参数为0表示不进行限制
	 * @return bool
	 */
	public function delete($table, $condition, $limit = 0) {
		$query = $this->genQuery()->from($table)->delete()->where($condition);
		if($limit != 0){
			$query = $query->limit($limit);
		}
		$result = $this->query($query);
		return !!$result;
	}

	/**
	 * insert data to database
	 * @param $table
	 * @param array $data
	 * @param null $condition
	 * @return mixed
	 */
	public function insert($table, array $data, $condition=null) {
		if(! empty($data)){
			foreach($data as $k=>$item){
				$data[$k] = $this->conn->quote($item);
			}
		}
		$query = $this->genQuery()->insert()->from($table)->setData($data)->where($condition);
		return $this->query($query);
	}

	/**
	 * generate DB Query Object
	 * @return Query
	 */
	private function genQuery() {
		$prefix = $this->config['prefix'] ?: '';
		$ins = new Query();
		$ins->setTablePrefix($prefix);
		return $ins;
	}
}
