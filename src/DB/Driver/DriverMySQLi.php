<?php
namespace Lite\DB\Driver;

use Lite\Component\Server;
use Lite\DB\Exception\ConnectException;
use Lite\Exception\Exception;
use mysqli;
use mysqli_result;
use function Lite\func\_tl;

/**
 * MySQLi驱动类
 * @package Lite\DB\Driver
 */
class DriverMySQLi extends DBAbstract{
	/** @var \mysqli $conn */
	private $conn;

	public function dbQuery($query){
		return $this->conn->query($query.'');
	}

	public function getAffectNum(){
		return $this->conn->affected_rows;
	}

	/**
	 * @param mysqli_result $resource
	 * @return array
	 */
	public function fetchAll($resource){
		$ret = $resource->fetch_all(MYSQLI_ASSOC);
		return $ret;
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
		return $this->conn->insert_id;
	}

	public function commit(){
		$this->conn->commit();
	}

	public function rollback(){
		$this->conn->rollback();
	}

	public function beginTransaction(){
		$this->conn->autocommit(true);
	}

	public function cancelTransactionState(){
		$this->conn->autocommit(false);
	}
	
	/**
	 * connect to specified config database
	 * @param array $config
	 * @param boolean $re_connect 是否重新连接
	 * @return void
	 */
	public function connect(array $config, $re_connect = false){
		$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
		if(!$conn || $conn->connect_error){
			$err = Server::inWindows() ? mb_convert_encoding($conn->connect_error, 'utf-8', 'gb2312') : $conn->connect_error;
			throw new ConnectException(_tl('Database connect failed:{error}, HOST：{host}', [
				'error' => $err,
				'host'  => $config['host'],
			]), null, $config, $conn->connect_errno);
		}
		$this->conn = $conn;
	}
}
