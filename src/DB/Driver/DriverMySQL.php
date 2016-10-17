<?php
namespace Lite\DB\Driver;
use Lite\DB\Query;
use Lite\Exception\Exception;
use function Lite\func\dump;

/**
 * mysql 数据库引擎
 * @deprecated 请使用mysqli或者pdo，该扩展可能将在未来PHP版本中得不到支持
 * User: sasumi
 * Date: 2016/6/11
 * Time: 17:52
 */
class DriverMySQL extends DBAbstract {
	private $conn;

	/**
	 * connect to specified config database
	 * @param array $config
	 * @param boolean $re_connect 是否重新连接
	 * @throws Exception
	 * @return resource
	 */
	public function connect(array $config, $re_connect = false){
		if(!$re_connect && $this->conn){
			return;
		}
		if($config['pconnect']){
			$this->conn = mysql_connect($config['host'], $config['user'], $config['password'], $config['port']);
		} else {
			$this->conn = mysql_pconnect($config['host'], $config['user'], $config['password'], $config['port']);
		}
		if(!$this->conn){
			throw new Exception(mysql_error(), null, $config);
		}
		mysql_select_db($config['database'], $this->conn);
		if($config['charset']){
			$config['charset'] = str_replace('-', '', $config['charset']);
			$this->query("SET NAMES '".$config['charset']."'");
		}
	}

	public function dbQuery($query){
		return mysql_query($query.'', $this->conn);
	}

	public function getCount($sql){
		$sql .= '';
		$sql = str_replace(array("\n", "\r"), '', $sql);
		if(preg_match('/^\s*SELECT.*?\s+FROM\s+/i', $sql)){
			if(preg_match('/\sGROUP\s+by\s/i', $sql) ||
				preg_match('/^\s*SELECT\s+DISTINCT\s/i', $sql)){
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

	public function getAffectNum(){
		return mysql_affected_rows($this->conn);
	}

	public function fetchAll($resource){
		$ret = array();
		while($row = mysql_fetch_assoc($resource)){
			$ret[] = $row;
		}
		return $ret ? $ret : null;
	}

	public function setLimit($sql, $limit){
		if(preg_match('/\sLIMIT\s/i', $sql)){
			throw new Exception('SQL LIMIT BEEN SET:' . $sql);
		}
		if(is_array($limit)){
			return $sql . ' LIMIT ' . $limit[0] . ',' . $limit[1];
		}
		return $sql . ' LIMIT ' . $limit;
	}

	public function getLastInsertId(){
		return mysql_insert_id($this->conn);
	}

	public function quote($data, $type=null){
		if(is_array($data)){
			$data = join(',', $data);
		} else if(is_numeric($data)){
			return "'$data'";
		}
		return addslashes($data);
	}

	/**
	 * no support for mysql driver
	 * @throws \Lite\Exception\Exception
	 */
	public function commit(){
		throw new Exception('database driver no support current operation');
	}

	/**
	 * no support for mysql driver
	 * @throws \Lite\Exception\Exception
	 */
	public function rollback(){
		throw new Exception('database driver no support current operation');
	}

	/**
	 * no support for mysql driver
	 * @throws \Lite\Exception\Exception
	 */
	public function beginTransaction(){
		throw new Exception('database driver no support current operation');
	}

	/**
	 * no support for mysql driver
	 * @throws \Lite\Exception\Exception
	 */
	public function cancelTransactionState(){
		throw new Exception('database driver no support current operation');
	}

}