<?php
namespace Lite\DB\Driver;

use Lite\Component\Server;
use Lite\DB\Exception\ConnectException;
use Lite\Exception\Exception;
use mysqli_result;
use function Lite\Func\_tl;

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
		return $this->conn->commit();
	}

	public function rollback(){
		return $this->conn->rollback();
	}

	public function beginTransaction(){
		$this->conn->autocommit(false);
	}

	public function cancelTransactionState(){
		$this->conn->autocommit(true);
	}
	
	/**
	 * connect to specified config database
	 * @param array $config
	 * @param boolean $re_connect 是否重新连接
	 * @return void
	 */
	public function connect(array $config, $re_connect = false){
		$connection = mysqli_init();

		//最大超时时间
		$max_connect_timeout = isset($config['connect_timeout']) ? $config['connect_timeout'] : Server::getMaxSocketTimeout(2);

		if($max_connect_timeout){
			mysqli_options($connection, MYSQLI_OPT_CONNECT_TIMEOUT, $max_connect_timeout);
		}

		//通过mysqli error方式获取数据库连接错误信息，转接到Exception
		$ret = @mysqli_real_connect($connection, $config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
		if(!$ret){
			$code = mysqli_connect_errno();
			$error = mysqli_connect_error();
			if(Server::inWindows()){
				$error = mb_convert_encoding($error, 'utf-8', 'gb2312');
			}
			$config['password'] = $config['password'] ? '******' : 'no using password';
			throw new ConnectException(_tl('Database connect failed:{error}, HOST：{host}', [
				'error' => $error,
				'host'  => $config['host'],
			]), null, $config, $code);
		}
		$this->conn = $connection;
	}
}
