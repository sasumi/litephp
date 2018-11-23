<?php
namespace Lite\DB\Driver;
use Lite\Exception\Exception;

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
			return null;
		}
		if($config['pconnect']){
			$this->conn = mysql_pconnect($config['host'], $config['user'], $config['password'], $config['port']);
		} else {
			$this->conn = mysql_connect($config['host'], $config['user'], $config['password'], $config['port']);
		}
		if(!$this->conn){
			throw new Exception(mysql_error(), null, $config);
		}
		mysql_select_db($config['database'], $this->conn);
	}

	public function dbQuery($query){
		return mysql_query($query.'', $this->conn);
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