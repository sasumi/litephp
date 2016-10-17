<?php
namespace Lite\DB\Driver;

use Lite\DB\Query;
use Lite\Exception\Exception;
use mysqli;
use mysqli_result;

class DriverMysqli extends DBAbstract{
	/** @var \mysqli $conn */
	private $conn;

	public function dbQuery($query){
		return $this->conn->query($query.'');
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
		$this->conn->insert_id;
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
	 * @throws Exception
	 * @return resource
	 */
	public function connect(array $config, $re_connect = false){
		$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
		if($config['charset']){
			$config['charset'] = str_replace('-', '', $config['charset']);
			$conn->query("SET NAMES '".$config['charset']."'");
		}
		$this->conn = $conn;
	}
}