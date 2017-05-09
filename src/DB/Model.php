<?php
namespace Lite\DB;

use Lite\Core\Config;
use Lite\Core\DAO;
use Lite\Core\Hooker;
use Lite\DB\Driver\DBAbstract;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use function Lite\func\array_clear_fields;
use function Lite\func\array_group;
use function Lite\func\dump;

/**
 * 数据库结合数据模型提供的操作抽象类, 实际业务逻辑最好通过集成该类来实现
 * 相应的业务数据功能逻辑.
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
abstract class Model extends DAO{
	const DB_READ = 1;
	const DB_WRITE = 2;

	/** @var array database config */
	private $db_config = array();

	/** @var Query db query object * */
	private $query = null;

	/**
	 * get current called class object
	 * @return Model
	 */
	public static function meta(){
		$class_name = get_called_class();
		$obj = new $class_name;
		return $obj;
	}

	/**
	 * on before save event
	 * @return boolean
	 */
	public function onBeforeSave(){
		return true;
	}

	/**
	 * on before update
	 * @return boolean
	 */
	public function onBeforeUpdate(){
		return true;
	}

	/**
	 * 记录插入之前事件
	 * @return boolean
	 */
	public function onBeforeInsert(){
		return true;
	}

	/**
	 * records on change event
	 */
	protected static function onBeforeChanged(){
		return true;
	}

	/**
	 * get current model related table name
	 * @return string
	 */
	abstract public function getTableName();

	/**
	 * get table full name
	 * @return string
	 */
	public function getTableFullName(){
		return $this->getDbTablePrefix().$this->getTableName();
	}

	/**
	 * get current table primary key
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public function getPrimaryKey(){
		$defines = $this->getEntityPropertiesDefine();
		foreach($defines as $k => $def){
			if($def['primary']){
				return $k;
			}
		}
		throw new Exception('no primary key found in table defines');
	}

	/**
	 * 获取db记录实例对象
	 * @param int $operate_type
	 * @return DBAbstract
	 */
	protected function getDbDriver($operate_type = self::DB_WRITE){
		$configs = $this->getDbConfig();
		$config = $this->parseConfig($operate_type, $configs);
		return DBAbstract::instance($config);
	}

	/**
	 * @param $query
	 * @return array
	 */
	public static function explainQuery($query){
		$obj = self::meta();
		return $obj->getDbDriver(self::DB_READ)->explain($query);
	}

	/**
	 * 获取数据库配置
	 * 该方法可以被覆盖重写
	 * @return array
	 */
	protected function getDbConfig(){
		return $this->db_config ?: Config::get('db');
	}

	/**
	 * 设置数据库配置到当前ORM
	 * 该方法可被覆盖、调用
	 * @param $db_config
	 */
	protected function setDbConfig($db_config){
		$this->db_config = $db_config;
	}

	/**
	 * 获取数据库表前缀
	 * @param int $type
	 * @return string
	 */
	public static function getDbTablePrefix($type = self::DB_READ){
		/** @var Model $obj */
		$obj = static::meta();
		$configs = $obj->getDbConfig();
		$config = $obj->parseConfig($type, $configs);
		return $config['prefix'] ?: '';
	}

	/**
	 * 解析数据库配置
	 * 分析出配置中的读、写配置
	 * @param string $operate_type
	 * @param array $all_config
	 * @throws Exception
	 * @internal param array $all
	 * @return array
	 */
	private function parseConfig($operate_type = null, array $all_config){
		$read_list = array();
		$write_list = array();
		$depKey = 'host';

		// 解析数据库读写配置
		if($all_config[$depKey]){
			$read_list = $write_list = array(
				$all_config
			);
		} else if($all_config['read']){
			if($all_config['read'][$depKey]){
				$read_list = array(
					$all_config['read']
				);
			} else{
				$read_list = $all_config['read'];
			}
		}

		// 写表额外判断，预防某些系统只需要读的功能
		if(empty($write_list)){
			if($all_config['write']){
				if($all_config['write'][$depKey]){
					$write_list = array(
						$all_config['write']
					);
				} else{
					$write_list = $all_config['write'];
				}
			}
		}

		switch($operate_type){
			case self::DB_WRITE:
				$k = array_rand($write_list, 1);
				$host_config = $write_list[$k];
				break;

			case self::DB_READ:
			default:
				$k = array_rand($read_list, 1);
				$host_config = $read_list[$k];
				break;
		}

		$host_config = array_merge($host_config, array(
			'driver' => 'pdo',
			'type'   => 'mysql',
		), $all_config);

		if(empty($host_config[$depKey])){
			throw new Exception('DB CONFIG ERROR FOR DRIVER TYPE:'.$operate_type);
		}
		return $host_config;
	}

	/**
	 * 设置查询SQL语句
	 * @param string | Query $query
	 * @throws Exception
	 * @return Model
	 */
	public static function setQuery($query){
		if(is_string($query)){
			$obj = self::meta();
			$args = func_get_args();
			$query = new Query(self::parseConditionStatement($args, $obj));
		}
		if($query){
			$obj = self::meta();
			$obj->query = $query;
			return $obj;
		}
		throw new Exception('QUERY STRING REQUIRED');
	}

	/**
	 * 获取当前查询对象
	 * @return \Lite\DB\Query
	 */
	public function getQuery(){
		return $this->query;
	}

	/**
	 * 开始一个事务
	 * @param callable $handler
	 * @throws \Lite\Exception\Exception
	 * @throws null
	 */
	public static function transaction($handler){
		$driver = self::meta()->getDbDriver(Model::DB_READ);
		try{
			$driver->beginTransaction();
			if(call_user_func($handler) === false){
				throw new Exception('database transaction interrupt');
			}
			$driver->commit();
		} catch(Exception $exception){
			$driver->rollback();
			throw $exception;
		}
	}

	/**
	 * 执行当前查询
	 * @return \PDOStatement
	 */
	public function execute(){
		$type = Query::isWriteOperation($this->query) ? self::DB_WRITE : self::DB_READ;
		$result = $this->getDbDriver($type)->query($this->query);
		return $result;
	}

	/**
	 * set model query cache
	 * @param Model[] $model_list
	 * @param callable $getter
	 * @param callable $setter
	 * @param callable $flusher
	 */
	public static function bindTableCacheHandler(array $model_list, callable $getter, callable $setter, callable $flusher){
		$check_table_hit = function ($query, $full_compare = true) use ($model_list){
			if($query && $query instanceof Query){
				foreach($model_list as $model){
					$tbl = Query::escapeKey($model::meta()->getTableFullName());
					if(($full_compare && $query->tables == [$tbl]) || in_array($tbl, $query->tables)){
						return $model;
					}
				}
			}
			return false;
		};
		
		//before get list, check cache
		Hooker::add(DBAbstract::EVENT_BEFORE_DB_GET_LIST, function($param) use ($check_table_hit, $model_list, $getter){
			$model = $check_table_hit($param['query']);
			if($model){
				$result = call_user_func($getter, $model, $param['query']);
				if(isset($result)){
					$param['result'] = $result;
				}
			}
		});

		//after get list, set cache
		Hooker::add(DBAbstract::EVENT_AFTER_DB_GET_LIST, function($param) use ($check_table_hit, $model_list, $setter){
			$model = $check_table_hit($param['query']);
			if($model){
				call_user_func($setter, $model, $param['query'], $param['result']);
			}
		});

		//flush table cache
		Hooker::add(DBAbstract::EVENT_AFTER_DB_QUERY, function($query) use($check_table_hit, $model_list, $flusher){
			$model = $check_table_hit($query);
			if($model && Query::isWriteOperation($query)){
				call_user_func($flusher, $model, $query);
			}
		});
	}

	/**
	 * 查找
	 * @param string $statement 条件表达式
	 * @param string $var 条件表达式扩展
	 * @param string $var,... 条件表达式扩展
	 * @return Model | Query
	 */
	public static function find($statement = '', $var = null){
		$obj = static::meta();
		$prefix = self::getDbTablePrefix();
		$query = new Query();
		$query->setTablePrefix($prefix);

		$args = func_get_args();
		$statement = self::parseConditionStatement($args, $obj);
		$query->select()->from($obj->getTableName())->where($statement);
		$obj->query = $query;
		return $obj;
	}

	/**
	 * add more find condition
	 * @param array $args
	 * @return mixed
	 */
	public function where(...$args){
		$statement = self::parseConditionStatement($args, $this);
		$this->query->where($statement);
		return $this;
	}

	/**
	 * 快速查询用户请求过来的信息，只有第二个参数为不为空的时候才去查询。
	 * @param $st
	 * @param $val
	 * @return $this
	 */
	public function whereOnSet($st, $val){
		$args = func_get_args();
		foreach($args as $k=>$arg){
			$args[$k] = trim($arg);
		}
		if(strlen($args[1])){
			$statement = self::parseConditionStatement($args, $this);
			$this->query->where($statement);
		}
		return $this;
	}
	
	/**
	 * 快速LIKE查询用户请求过来的信息，当LIKE内容为空时，不执行查询，如 %%。
	 * @param $st
	 * @param $val
	 * @return $this
	 */
	public function whereLikeOnSet($st, $val){
		$args = func_get_args();
		if(strlen(trim(str_replace('%','',$val)))){
			return call_user_func_array(array($this, 'whereOnSet'), $args);
		}
		return $this;
	}

	/**
	 * query where field between min & max (include equal)
	 * @param $field
	 * @param null $min
	 * @param null $max
	 * @return $this
	 */
	public function between($field, $min = null, $max = null){
		if(isset($min)){
			$min = addslashes($min);
			$this->query->where($field, ">=", $min);
		}
		if(isset($max)){
			$max = addslashes($max);
			$this->query->where($field, "<=", $max);
		}
		return $this;
	}

	/**
	 * 创建新对象
	 * @param $data
	 * @return bool| Model
	 */
	public static function create($data){
		$obj = static::meta();
		$obj->setValues($data);
		return $obj->save() ? $obj : false;
	}

	/**
	 * 由主键查询一条记录
	 * @param string $val
	 * @param bool $as_array
	 * @return Model | array
	 */
	public static function findOneByPk($val, $as_array = false){
		$obj = static::meta();
		return self::find($obj->getPrimaryKey().'=?', $val)->one($as_array);
	}

	/**
	 * 有主键列表查询多条记录
	 * 单主键列表为空，该方法会返回空数组结果
	 * @param array $pks
	 * @param bool $as_array
	 * @return array
	 */
	public static function findByPks(array $pks, $as_array = false){
		if(empty($pks)){
			return array();
		}
		$obj = static::meta();
		return self::find($obj->getPrimaryKey().' IN ?', $pks)->all($as_array);
	}

	/**
	 * 根据主键值删除一条记录
	 * @param string $val
	 * @return bool
	 */
	public static function delByPk($val){
		$obj = static::meta();
		return static::deleteWhere(0, $obj->getPrimaryKey()."='$val'");
	}

	/**
	 * 根据主键值更新记录
	 * @param string $val 主键值
	 * @param array $data
	 * @return bool
	 */
	public static function updateByPk($val, array $data){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();
		return static::updateWhere($data, 1, "$pk = ?", $val);
	}

	/**
	 * 根据主键值更新记录
	 * @param $pks
	 * @param array $data
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public static function updateByPks($pks, array $data){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();
		return static::updateWhere($data, count($pks), "$pk IN ?", $pks);
	}

	/**
	 * 根据条件更新数据
	 * @param array $data
	 * @param int $limit
	 * @param string $statement
	 * @return bool;
	 */
	public static function updateWhere(array $data, $limit = 1, $statement){
		if(self::onBeforeChanged() === false){
			return false;
		}

		$args = func_get_args();
		$args = array_slice($args, 2);
		$obj = static::meta();
		$statement = self::parseConditionStatement($args, $obj);
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->update($table, $data, $statement, $limit);
		return $result;
	}

	/**
	 * 根据条件从表中删除记录
	 * @param int $limit
	 * @param $statement
	 * @return bool
	 */
	public static function deleteWhere($limit = 1, $statement){
		$args = func_get_args();
		$args = array_slice($args, 1);

		$obj = static::meta();
		$statement = self::parseConditionStatement($args, $obj);
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->delete($table, $statement, $limit);
		return $result;
	}

	/**
	 * 获取所有记录
	 * @param bool $as_array return as array
	 * @param string $unique_key 用于组成返回数组的唯一性key
	 * @return array
	 */
	public function all($as_array = false, $unique_key = ''){
		$list = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		if(!$list){
			return array();
		}
		if($as_array){
			if($unique_key){
				$list = array_group($list, $unique_key, true);
			}
			return $list;
		}

		$result = array();
		foreach($list as $item){
			$tmp = clone $this;
			$tmp->setValues($item);
			$tmp->resetValueChangeState();
			if($unique_key){
				$result[$item[$unique_key]] = $tmp;
			} else{
				$result[] = $tmp;
			}
		}
		return $result;
	}

	/**
	 * 以关联数组方式返回
	 * @deprecated 请使用 all，第二个参数已经支持
	 * @param bool $as_array
	 * @param null $key
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	public function allAsAssoc($as_array = false, $key = null){
		$key = $key ?: $this->getPrimaryKey();
		return $this->all($as_array, $key);
	}

	/**
	 * 获取一条记录
	 * @param bool $as_array
	 * @return Model|array|NULL
	 */
	public function one($as_array = false){
		$data = $this->getDbDriver(self::DB_READ)->getOne($this->query);
		if($as_array){
			return $data;
		}
		if(!empty($data)){
			$this->setValues($data);
			$this->resetValueChangeState();
			return $this;
		}
		return null;
	}

	/**
	 * 获取一个记录字段
	 * @param string|null $key 如字段为空，则取第一个结果
	 * @return mixed|null
	 */
	public function ceil($key = ''){
		$obj = self::meta();
		$pro_defines = $obj->getEntityPropertiesDefine();
		if($key && $pro_defines[$key]){
			$this->query->field($key);
		}
		$data = $this->getDbDriver(self::DB_READ)->getOne($this->query);
		return $data ? array_pop($data) : null;
	}

	public function sum($field){
		$k = '__SUM__';
		$str = "SUM($field) AS $k";
		$this->query->field($str);
		$data = $this->getDbDriver(self::DB_READ)->getOne($this->query);
		return $data[$k];
	}

	/**
	 * 获取指定列
	 * @param $key
	 * @return array
	 */
	public function column($key){
		$obj = self::meta();
		$pro_defines = $obj->getEntityPropertiesDefine();
		if($pro_defines[$key]){
			$this->query->field($key);
		}
		$data = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		return $data ? array_column($data, $key) : array();
	}

	/**
	 * 根据分段进行数据处理，常见用于节省WebServer内存操作
	 * @param int $size 分块大小
	 * @param callable $handler 回调函数
	 * @param bool $as_array 查询结果作为数组格式回调
	 * @return bool 是否执行了分块动作
	 * @throws Exception
	 */
	public function chunk($size = 1, $handler, $as_array = false){
		$total = $this->count();
		$start = 0;
		if(!$total){
			return false;
		}

		$ds = DBAbstract::distinctQueryState();
		if($ds){
			DBAbstract::distinctQueryOff();
		}
		$page_index = 0;
		$page_total = ceil($total/$size);
		while($start<$total){
			$data = $this->paginate(array($start, $size), $as_array);
			if(call_user_func($handler, $data, $page_index++, $page_total) === false){
				break;
			}
			$start += $size;
		}
		if($ds){
			DBAbstract::distinctQueryOn();
		}
		return true;
	}

	/**
	 * 获取当前查询条数
	 * @return int
	 */
	public function count(){
		$count = $this->getDbDriver(self::DB_READ)->getCount($this->query);
		return $count;
	}

	/**
	 * 分页查询记录
	 * @param string $page
	 * @param bool $as_array return as array
	 * @param string $unique_key 用于组成返回数组的唯一性key
	 * @return array | null
	 */
	public function paginate($page = null, $as_array = false, $unique_key = ''){
		$list = $this->getDbDriver(self::DB_READ)->getPage($this->query, $page);
		if($as_array){
			if($unique_key){
				$list = array_group($list, $unique_key, true);
			}
			return $list;
		}
		$result = array();
		if($list){
			foreach($list as $item){
				$tmp = clone $this;
				$tmp->setValues($item);
				$tmp->resetValueChangeState();
				if($unique_key){
					$result[$item[$unique_key]] = $tmp;
				} else{
					$result[] = $tmp;
				}
			}
		}
		return $result;
	}

	/**
	 * 更新当前对象
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 * @return number
	 */
	public function update(){
		if($this->onBeforeUpdate() === false || self::onBeforeChanged() === false){
			return false;
		}

		$data = $this->getValues();
		$pk = $this->getPrimaryKey();

		//只更新改变的值
		$change_keys = $this->getValueChangeKeys();
		$data = array_clear_fields(array_keys($change_keys), $data);
		list($data) = self::validate($data, Query::UPDATE, $this->$pk);
		return $this->getDbDriver(self::DB_WRITE)->update($this->getTableName(), $data, $this->getPrimaryKey().'='.$this->$pk);
	}

	/**
	 * 插入当前对象
	 * @throws \Lite\Exception\BizException
	 * @return string | bool 返回插入的id，或者失败(false)
	 */
	public function insert(){
		if($this->onBeforeInsert() === false || self::onBeforeChanged() === false){
			return false;
		}

		$data = $this->getValues();
		list($data) = self::validate($data, Query::INSERT);

		$result = $this->getDbDriver(self::DB_WRITE)->insert($this->getTableName(), $data);
		if($result){
			$pk_val = $this->getDbDriver(self::DB_WRITE)->getLastInsertId();
			$this->setValue($this->getPrimaryKey(), $pk_val);
			return $pk_val;
		}
		return false;
	}

	/**
	 * replace data
	 * @param array $data
	 * @param int $limit
	 * @param array ...$args
	 * @return mixed
	 * @throws \Lite\Exception\BizException
	 */
	public static function replace(array $data, $limit = 0, ...$args){
		$obj = self::meta();
		$statement = self::parseConditionStatement($args, $obj);

		$obj = static::meta();
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->replace($table, $data, $statement, $limit);
		return $result;
	}

	/**
	 * increase or decrease offset
	 * @param $field
	 * @param $offset
	 * @param int $limit
	 * @param array ...$args
	 * @return int
	 */
	public static function increase($field, $offset, $limit = 0, ...$args){
		$obj = self::meta();
		$statement = self::parseConditionStatement($args, $obj);

		$obj = static::meta();
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->increase($table, $field, $offset, $statement, $limit);
		return $result;
	}

	/**
	 * @param array $src_data
	 * @param string $query_type
	 * @param null $pk_val
	 * @param bool $throw_exception
	 * @return array [data,error_message]
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 */
	private static function validate($src_data = array(), $query_type = Query::INSERT, $pk_val = null, $throw_exception = true){
		$obj = self::meta();
		$pro_defines = $obj->getEntityPropertiesDefine();
		$pk = $obj->getPrimaryKey();

		//转换set数据
		foreach($src_data as $k => $d){
			if($pro_defines[$k]['type'] == 'set' && is_array($d)){
				$src_data[$k] = join(',', $d);
			}
		}

		//移除矢量数值
		$data = array_filter($src_data, function($item){
			return is_scalar($item);
		});

		//unique校验
		foreach($pro_defines as $field => $def){
			if($def['unique']){
				if($query_type == Query::INSERT){
					$count = $obj->find("`$field`=?", $data[$field])->count();
				} else{
					$count = $obj->find("`$field`=? AND `$pk` <> ?", $data[$field], $pk_val)->count();
				}
				if($count){
					$msg = "{$def['alias']}：{$data[$field]}已经存在，不能重复添加";
					if($throw_exception){
						throw new BizException($msg);
					}
					return array($data, $msg);
				}
			}
		}

		//移除readonly属性
		$pro_defines = array_filter($pro_defines, function($def){
			return !$def['readonly'];
		});

		//清理无用数据
		$data = array_clear_fields(array_keys($pro_defines), $data);

		//插入时填充default值
		array_walk($pro_defines, function($def, $k) use (&$data, $query_type){
			if(isset($def['default'])){
				if($query_type == Query::INSERT){
					if((!isset($data[$k]) || strlen($data[$k]) == 0)){
						$data[$k] = $def['default'];
					}
				} else if(isset($data[$k]) && !strlen($data[$k])){
					$data[$k] = $def['default'];
				}
			}
		});

		//更新时，只需要处理更新数据的属性
		if($query_type == Query::UPDATE || $query_type == Query::REPLACE){
			foreach($pro_defines as $k => $define){
				if(!isset($data[$k])){
					unset($pro_defines[$k]);
				}
			}
		}


		//处理date日期默认为NULL情况
		foreach($data as $k => $val){
			if(in_array($pro_defines[$k]['type'], array(
					'date',
					'datetime',
					'time'
				)) && array_key_exists('default', $pro_defines[$k]) && $pro_defines[$k]['default'] === null && !$data[$k]
			){
				$data[$k] = null;
			}
		}

		//属性校验
		foreach($pro_defines as $k => $def){
			if(!$def['readonly']){
				if($msg = self::validateField($data[$k], $k)){
					if($throw_exception){
						throw new BizException($msg, null, array('data' => $data, 'key' => $k));
					} else{
						return array($data, $msg);
					}
				}
			}
		}
		return array($data, null);
	}

	/**
	 * 字段校验
	 * @param $value
	 * @param $field
	 * @return string
	 */
	private static function validateField(&$value, $field){
		/** @var Model $obj */
		$obj = self::meta();
		$define = $obj->getPropertiesDefine($field);

		$err = '';
		$val = $value;
		$name = $define['alias'];
		if(is_callable($define['options'])){
			$define['options'] = call_user_func($define['options'], null);
		}

		$required = $define['required'];

		//type
		if(!$err){
			switch($define['type']){
				case 'int':
					if($val != intval($val)){
						$err = $name.'格式不正确';
					}
					break;

				case 'float':
				case 'double':
					if(!(!$required && !strlen($val.'')) && isset($val) && !is_numeric($val)){
						$err = $name.'格式不正确';
					}
					break;

				case 'enum':
					$err = !(!$required && !strlen($val.'')) && !isset($define['options'][$val]) ? '请选择'.$name : '';
					break;

				//string暂不校验
				case 'string':
					break;
			}
		}

		//required
		if(!$err && $define['required'] && strlen($val) == 0){
			$err = "请输入{$name}";
		}

		//length
		if(!$err && $define['length'] && $define['type'] != 'datetime' && $define['type'] != 'date' && $define['type'] != 'time'){
			$err = strlen($val)>$define['length'] ? "{$name}长度超出" : '';
		}

		if(!$err){
			$value = $val;
		}
		return $err;
	}

	/**
	 * 批量插入数据
	 * 由于这里插入会涉及到数据检查，最终效果还是一条一条的插入
	 * @param $data_list
	 * @param bool $break_on_fail
	 * @return array|bool
	 * @throws \Exception
	 * @throws \Lite\Exception\Exception
	 */
	public static function insertMany($data_list, $break_on_fail = true){
		if(count($data_list, COUNT_RECURSIVE) == count($data_list)){
			throw new Exception('2 dimension array needed');
		}
		$obj = static::meta();
		$return_list = array();
		foreach($data_list as $data){
			try{
				$tmp = clone($obj);
				$tmp->setValues($data);
				$result = $tmp->insert();
				if($result){
					$pk_val = $tmp->getDbDriver(self::DB_WRITE)->getLastInsertId();
					$return_list[] = $pk_val;
				}
			} catch(\Exception $e){
				if($break_on_fail){
					throw $e;
				}
			}
		}
		return $return_list;
	}

	/**
	 * 快速批量插入数据，不进行ORM检查
	 * @param $data_list
	 * @return mixed
	 * @throws \Lite\Exception\Exception
	 */
	public static function insertManyQuick($data_list){
		if(self::onBeforeChanged() === false){
			return false;
		}
		$obj = static::meta();
		$result = $obj->getDbDriver(self::DB_WRITE)->insert($obj->getTableName(), $data_list);
		return $result;
	}

	/**
	 * 从数据库从删除当前对象对应的记录
	 * @return bool
	 */
	public function delete(){
		$pk_val = $this[$this->getPrimaryKey()];
		return self::delByPk($pk_val);
	}

	/**
	 * 解析SQL查询中的条件表达式
	 * @param array $args
	 * @param \Lite\DB\Model $obj
	 * @return string
	 */
	private static function parseConditionStatement($args = array(), Model $obj){
		$statement = $args[0];
		$args = array_slice($args, 1);
		if(!empty($args)){
			$arr = explode('?', $statement);
			$rst = '';
			foreach($args as $key => $val){
				if(is_array($val)){
					array_walk($val, function(&$item) use ($obj){
						$item = $obj->getDbDriver(self::DB_READ)->quote($item);
					});

					if(!empty($val)){
						$rst .= $arr[$key].'('.join(',', $val).')';
					} else{
						$rst .= $arr[$key].'(NULL)'; //This will never match, since nothing is equal to null (not even null itself.)
					}
				} else{
					$rst .= $arr[$key].$obj->getDbDriver(self::DB_READ)->quote($val);
				}
			}
			$rst .= array_pop($arr);
			$statement = $rst;
		}
		return $statement;
	}

	/**
	 * 保存当前对象变更之后的数值
	 * @return bool
	 */
	public function save(){
		if($this->onBeforeSave() === false){
			return false;
		}

		$data = $this->getValues();
		$has_pk = !empty($data[$this->getPrimaryKey()]);
		if($has_pk){
			return $this->update();
		} else if(!empty($data)){
			return $this->insert();
		}
		return false;
	}

	/**
	 * 调用查询对象其他方法
	 * @param $method_name
	 * @param $params
	 * @return $this
	 * @throws Exception
	 */
	final public function __call($method_name, $params){
		if(method_exists($this->query, $method_name)){
			call_user_func_array(array($this->query, $method_name), $params);
			return $this;
		}

		throw new Exception("METHOD NO EXIST:".$method_name);
	}

	/**
	 * 初始化DAO setter
	 * @param $key
	 * @param $val
	 */
	public function __set($key, $val){
		parent::__set($key, $val);
	}

	/**
	 * 配置getter
	 * <p>
	 * 支持：'name' => array(
	 *          'has_one'=>callable,
	 *          'target_key'=>'category_id',
	 *          'source_key'=>默认当前对象PK)
	 * 支持：'children' => array(
	 *          'has_many'=>callable,
	 *          'target_key'=>'category_id',
	 *          'source_key' => 默认当前对象PK)
	 * 支持：'name' => array(
	 *          'getter' => function($k){
	 *          }
	 *      )
	 * 支持：'name' => array(
	 *          'setter' => function($k, $v){
	 *          }
	 *      )
	 * </p>
	 * @param $key
	 * @throws \Lite\Exception\Exception
	 * @return mixed
	 */
	public function __get($key){
		$define = $this->getPropertiesDefine($key);

		if($define){
			if($define['getter']){
				return call_user_func($define['getter'], $this);
			} else if($define['has_one'] || $define['has_many']){
				$source_key = $define['source_key'];
				$target_key = $define['target_key'];

				if($define['has_one']){
					if(!$target_key && !$source_key){
						throw new Exception('has one config must define target key or source key');
					}
					$match_val = $this->getValue($source_key ?: $this->getPrimaryKey());
					/** @var Model $class */
					$class = $define['has_one'];
					if(!$target_key){
						return $class::findOneByPk($match_val);
					} else{
						return $class::find("$target_key = ?", $match_val)->one();
					}
				}
				if($define['has_many']){
					if(!$target_key){
						throw new Exception('has many config must define target key');
					}
					/** @var Model $class */
					$class = $define['has_many'];
					$match_val = $this->getValue($source_key ?: $this->getPrimaryKey());
					return $class::find("$target_key = ?", $match_val)->all();
				}
			}
		}
		$v = parent::__get($key);

		//如果当前属性未定义，或者未从数据库中获取相应字段
		//则抛异常
		$kvs = array_keys($this->getValues());
		if(!isset($v) && !in_array($key, $kvs)){
			//@todo 这里由于在update/add模板共用情况下，很可能使用 $model->$field 进行直接拼接action，需要重新审视这里抛出exception是否合理
			//throw new Exception('model fields not set in query result', null, $key);
		}
		return $v;
	}

	public function getModelDesc(){
		return $this->getTableName();
	}

	/**
	 * 转换当前查询对象为字符串
	 * @return string|void
	 */
	public function __toString(){
		return $this->query.'';
	}
}