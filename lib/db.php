<?php
include 'db_query.php';
$__DB_CONNECTION__;
$__DB_QUERY_CACHE__;

/**
 * 获取db配置
 * @param string $key
 * @return array
 **/
function db_get_config($key=null){
	$config = include CONFIG_PATH.'db.php';
	if(!$config){
		db_exception('NO DB CONFIG IN '.CONFIG_PATH.'db.php');
	}

	$config = array_merge(array(
		'driver' => 'mysql',
		'host' => 'localhost',
		'user' => 'root',
		'database' => '',
		'charset' => 'utf8',
		'password' => '',
		'pconnect' => false
	), $config);

	return $key ? $config[$key] : $config;
}

/**
 * 异常
 * @param string $message
**/
function db_exception($message, $param1=null, $param2=null){
	die($message);
}

/**
 * 读取config下面的db.php配置，链接到数据库
 * 同时设置db，设置链接查询编码
 * @param boolean $reconect 是否重新连接
 * @return resource
 **/
function db_connect($reconect=false){
	if(!$reconect && $__DB_CONNECTION__){
		return $__DB_CONNECTION__;
	}

	$config = db_get_config();
	$conn = null;
	if($config['driver'] == 'mysql'){
		if($config['pconnect']){
			$conn = mysql_pconnect($config['host'], $config['user'], $config['password']);
		} else {
			$conn = mysql_connect($config['host'], $config['user'], $config['password']);
		}
		mysql_query('USE '.$config['database'], $conn);
		mysql_query('SET CHARSET '.$config['charset'], $conn);
	}
	else {
		db_exception('NO DB TYPE SUPPORT YET');
	}

	$__DB_CONNECTION__ = $conn;
	return $conn;
}

/**
 * 数据库查询
 * @param string $sql
 * @param resource $conn
 * @return resource
 **/
function db_query($sql, $conn=null){
	$conn = $conn ?: db_connect();
	fire_hook('BEFORE_DB_QUERY', $sql, $conn);
	if(db_get_config('driver') == 'mysql'){
		$result = mysql_query($sql, $conn);
	}
	else {
		db_exception('NO DB TYPE SUPPORT YET2');
	}
	fire_hook('AFTER_DB_QUERY', $result);
	return $result;
}


/**
 * 设置sql limit信息
 * @param string $sql
 * @param mix $limit
 * @return string
 **/
function db_set_sql_limit($sql, $limit){
	if(db_get_config('driver') == 'mysql'){
		if(preg_match("/\slimit\s/", $sql)){
			throw 'SQL LIMIT SETTED:'.$sql;
		}
		if(is_array($limit)){
			return $sql.' LIMIT '.$limit[0].','.$limit[1];
		}
		return $sql.' LIMIT '.$limit;
	}
	else {
		db_exception('NO DB TYPE SUPPORT YET');
	}
}

/**
 * 获取一行
 * @param resource $resource
 * @return array()
**/
function db_fetch_row($resource){
	if(db_get_config('driver') == 'mysql'){
		$data = mysql_fetch_row($resource);
	}
	else {
		db_exception('NO DB TYPE SUPPORT YET');
	}
	return $data;
}

/**
 * 获取关联行
 * @param resource $resource
 * @return array()
**/
function db_fetch_assoc($resource){
	if(db_get_config('driver') == 'mysql'){
		$data = mysql_fetch_assoc($resource);
	}
	else {
		db_exception('NO DB TYPE SUPPORT YET');
	}
	return $data;
}

/**
 * 获取总数
 * @param string $sql
 * @param resource $conn
 * @return integer
 **/
function db_get_count($sql, $conn=null){
	$count = 0;
	if(preg_match("/^select\s+/i", $sql)){
		$sql = preg_replace("/^select\s.*?(from.*)/i", 'SELECT COUNT(*) AS __NUM__ $1', $sql);
		$result = db_query($sql, $conn);
		if($result){
			$row = db_fetch_row($result);
			if($row){
				$count = array_pop($row);
			}
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
function db_get_page($sql, $pager=null, $conn=null){
	if($pager instanceof Pager){
		$total = db_get_count($sql, $conn);
		$pager->setItemTotal($total);
		$limit = $pager->getLimit();
	} else {
		$limit = $pager;
	}
	
	if($limit){
		$sql = db_set_sql_limit($sql, $limit);
	}
	
	$rs = db_query($sql, $conn);
	$result = array();
	if($rs){
		while($row = db_fetch_assoc($rs)){
			$result[] = $row;
		}
	}
	return $result;
}

/**
 * 获取一行
 * @param stirng $sql
 * @return mix
 **/
function db_get_row($sql){
	$rst = db_get_page($sql, 1);
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
function db_get_one($sql){
	$rst = db_get_row($sql);
	if($rst){
		return array_pop($rst);
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
function db_update_count($table, $field, $offset_count=1, $increase=true){
	
}

/**
 * 更新数据库数据
 * @param string $table 表
 * @param array $data 数据
 * @param string $cond 条件
 * @return integer 影响行数
 **/
function db_update($table, array $data, $cond=''){
	$sql = db_sql()->update($table)->setData($data)->where($cond);
	db_query($sql);
	return db_affect_num();
}

/**
 * 查询最近db执行影响行数
 * @param resource $conn
 * @return integer
 **/
function db_affect_num($conn=null){
	$conn = $conn ?: db_connect();
	if(db_get_config('driver') == 'mysql'){
		return mysql_affected_rows($conn);
	}
	else {
		db_exception('NO DB TYPE SUPPORT YET');
	}
}

/**
 * 删除数据库数据
 * @param string $table 表
 * @param string $cond 条件
 * @return integer 成功删除数量
 **/
function db_delete($table, $cond, $limit=1){
	$sql = db_sql()->delet($table)->where($cond)->limit($limit);
	db_query($sql);
	return db_affect_num();
}

/**
 * 插入数据库数据
 * @param string $table 表
 * @param array $data 数据
 * @return boolean 操作成功
 **/
function db_insert($table, array $data){
	$sql = db_sql()->insert($table)->setData($data)->where($cond);
	return db_query($sql);
}

/**
 * 产生sql语句
 * @param string $driver
 * @return DB_Query
 **/
function db_sql($driver=''){
	$driver = $driver ?: db_get_config('driver'); 
	return new DB_Query($driver);
}

