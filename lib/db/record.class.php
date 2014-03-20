<?php
final class DB_Record {
	private static $instance;
	private $conn;
	private $config = array();

	private function __construct($config){
		$def_config = Config::get('db');
		$config = array_merge(array(
				'driver' => 'mysql',
				'host' => 'localhost',
				'user' => 'root',
				'database' => '',
				'charset' => '',
				'password' => '',
				'pconnect' => false
		), $def_config);
		$this->config = $config;
		$this->connect($config);
	}

	/**
	 * 单例
	 * @param array $config
	 * @return DB
	 */
	public static function init(array $config=array()){
		if(!self::$instance){
			self::$instance = new self($config);
		}
		return self::$instance;
	}

	/**
	 * 读取config下面的db.php配置，链接到数据库
	 * 同时设置db，设置链接查询编码
	 * @param array $config
	 * @param boolean $reconect 是否重新连接
	 * @return resource
	 **/
	function connect(array $config, $reconect=false){
		if(!$reconect && $this->conn){
			return $this->conn;
		}

		$conn = null;

		if($config['driver'] == 'sqlite'){
			$dns = 'sqlite:'.$config['host'];
		} else {
			$dns = "$config[driver]:dbname=$config[database];host=$config[host]";
			$dns .= $config['charset'] ? ";charset=$config[charset]":"";
		}

		$conn = new PDO($dns, $config['user'], $config['password']);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->conn = $conn;
		return $conn;
	}

	/**
	 * 数据库查询
	 * @param string $sql
	 * @return resource
	 **/
	function query($sql){
		try {
			Hooker::fire('BEFORE_DB_QUERY', $sql, $this->conn);
			$result = $this->conn->query($sql);
			Hooker::fire('AFTER_DB_QUERY', $result);
			return $result;
		} catch(\PDOException $ex){
			Hooker::fire('DB_QUERY_ERROR', $ex, $sql, $this->conn);
			$new_ex = new LException($ex->getMessage());
			$new_ex->setData($ex);
			throw $new_ex;
		}
	}

	public function quote($string, $type=null){
		return $this->conn->quote($string, $type);
	}


	/**
	 * 设置sql limit信息
	 * @param string $sql
	 * @param mix $limit
	 * @return string
	 **/
	function setLimit($sql, $limit){
		if(preg_match("/\slimit\s/", $sql)){
			throw 'SQL LIMIT SETTED:'.$sql;
		}
		if(is_array($limit)){
			return $sql.' LIMIT '.$limit[0].','.$limit[1];
		}
		return $sql.' LIMIT '.$limit;
	}

	/**
	 * 获取一行
	 * @param resource $resource
	 * @return array()
	 **/
	function fetchRow($resource){
		$resource->setFetchMode(PDO::FETCH_NUM);
		return $resource->fetch();
	}

	/**
	 * 获取关联行
	 * @param resource $resource
	 * @return array()
	 **/
	function fetchAssoc($resource){
		$resource->setFetchMode(PDO::FETCH_ASSOC);
		return $resource->fetch();
	}

	function fetchAll($resource){
		$resource->setFetchMode(PDO::FETCH_ASSOC);
		return $resource->fetchAll();
	}

	function fetchColumn($rs){
		return $rs->fetchColumn();
	}

	/**
	 * get data count
	 * @param string $sql
	 * @return integer
	 **/
	function getCount($sql){
		$count = 0;
		if(preg_match("/^select\s+/i", $sql)){
			$sql = preg_replace("/^select\s.*?(from.*)/i", 'SELECT COUNT(*) AS __NUM__ $1', $sql);
			$result = $this->getOne($sql);
			if($result){
				return (int)array_pop($result);
			}
		}
		return $count;
	}

	/**
	 * 查询所有数据，以数组形式返回
	 * @param string $sql
	 * @param array||int 限制，格式如 array(0,3) || 3
	 * @return array
	 **/
	function getPage($sql, $pager=null){
		if($pager instanceof Pager){
			$total = $this->getCount($sql);
			$pager->setItemTotal($total);
			$limit = $pager->getLimit();
		} else {
			$limit = $pager;
		}

		if($limit){
			$sql = $this->setLimit($sql, $limit);
		}

		$rs = $this->query($sql);
		if($rs){
			return $this->fetchAll($rs);
		}
		return array();
	}

	/**
	 * get all
	 * @param  String $sql
	 * @return mix
	 */
	function getAll($sql){
		return $this->getPage($sql, null);
	}

	/**
	 * 获取一行
	 * @param stirng $sql
	 * @return mix
	 **/
	function getOne($sql){
		$rst = $this->getPage($sql, 1);
		if($rst){
			return $rst[0];
		}
		return null;
	}

	/**
	 * 获取一个字段
	 * @param stirng $sql
	 * @return mix
	 **/
	function getField($sql, $key=''){
		$rst = $this->getOne($sql);
		if($rst){
			return $$key ? $rst[$key] : array_pop($rst);
		}
		return null;
	}

	/**
	 * 更新数据库字段计数
	 * @param string $table 表
	 * @param string $field 字段
	 * @param integer $offset_count 更新增量
	 * @param boolean $increase 增加还是减少（减少的话，底线是0）
	 * @return boolean 操作成功
	 **/
	function updateCount($table, $field, $offset_count=1, $increase=true){

	}

	/**
	 * 更新数据库数据
	 * @param string $table 表
	 * @param array $data 数据
	 * @param string $cond 条件
	 * @return integer 影响行数
	 **/
	function update($table, array $data, $cond=''){
		$sql = $this->sql()->from($table)->update()->setData($data)->where($cond);
		$this->query($sql);
		return $this->getAffectNum();
	}

	/**
	 * 查询最近db执行影响行数
	 * @return integer
	 **/
	function getAffectNum(){
		return 1;//$conn->rowCount();
	}

	/**
	 * 删除数据库数据
	 * @param string $table 表
	 * @param string $cond 条件
	 * @return integer 成功删除数量
	 **/
	function delete($table, $cond, $limit=1){
		$sql = $this->sql()->from($table)->delete()->where($cond)->limit($limit);
		query($sql);
		return affect_num();
	}

	/**
	 * 插入数据库数据
	 * @param string $table 表
	 * @param array $data 数据
	 * @return boolean 操作成功
	 **/
	function insert($table, array $data){
		$sql = $this->sql()->from($table)->insert()->setData($data)->where($cond);
		return $this->query($sql);
	}

	/**
	 * 产生sql语句
	 * @return DB_Query
	 **/
	function sql(){
		return new DB_Query();
	}
}