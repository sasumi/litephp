<?php
namespace Lite\DB;

use Lite\Core\Config;
use Lite\Core\DAO;
use Lite\Core\Filter;
use Lite\Exception\BizException;
use Lite\Exception\Exception;

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
	 * 当前表名接口
	 * @return string
	 */
	abstract public function getTableName();

	/**
	 * 获取当前设置主键
	 * @return string
	 */
	abstract public function getPrimaryKey();

	/**
	 * 获取db记录实例对象
	 * @param int $recode_type
	 * @return Record
	 */
	protected function getDbRecord($recode_type = self::DB_WRITE){
		$configs = $this->getDbConfig();
		$config = $this->parseConfig($recode_type, $configs);
		return Record::instance($config);
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
	 * @param string $recode_type
	 * @param array $all_config
	 * @throws Exception
	 * @internal param array $all
	 * @return array
	 */
	private function parseConfig($recode_type = null, array $all_config){
		$read_list = array();
		$write_list = array();
		$depKey = 'host';

		// 解析数据库读写配置
		if($all_config[$depKey]){
			$read_list = $write_list = array(
				$all_config
			);
		}else if($all_config['read']){
			if($all_config['read'][$depKey]){
				$read_list = array(
					$all_config['read']
				);
			}else{
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
				}else{
					$write_list = $all_config['write'];
				}
			}
		}

		switch($recode_type){
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
			'driver'  => 'mysql',
			'charset' => 'utf-8'
		), $all_config);

		$host_config['driver'] = strtolower($host_config['driver']);
		$host_config['charset'] = strtolower($host_config['charset']);

		// 矫正mysql charset
		if($host_config['driver'] == 'mysql'){
			$host_config['charset'] = str_replace('-', '', $host_config['charset']);
		}

		if(empty($host_config[$depKey])){
			throw new Exception('DB CONFIG ERROR FOR RECORD TYPE:'.$recode_type);
		}
		return $host_config;
	}

	/**
	 * 设置查询SQL语句
	 * @param string | Query $query
	 * @param array $db_config
	 * @throws Exception
	 * @return Model
	 */
	public static function setQuery($query = null, $db_config = array()){
		if(is_string($query)){
			$query = new Query($query);
		}
		if($query){
			$obj = new __ModelHelper__();
			if($db_config){
				$obj->setDbConfig($db_config);
			}
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
	 * @return Exception
	 */
	public static function transaction($handler){
		$exception = null;
		try{
			Record::beginTransactionAll();
			if(call_user_func($handler) === false){
				throw new Exception('DB transaction fail');
			}
			Record::commitAll();
		} catch(Exception $exception){
			Record::rollbackAll();
		}
		Record::cancelTransactionStateAll();
		return $exception;
	}

	/**
	 * 执行当前查询
	 * @return \PDOStatement
	 */
	public function execute(){
		$type = Query::isWriteOperation($this->query) ? self::DB_WRITE : self::DB_READ;
		$result = $this->getDbRecord($type)->query($this->query);
		return $result;
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
	 * @param $data
	 * @return bool
	 */
	public static function updateByPk($val, $data){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();

		/** @var Model $class_name */
		return static::updateWhere($data, 1, "$pk = ?", $val);
	}

	/**
	 * 根据条件更新数据
	 * @param array $data
	 * @param int $limit
	 * @param string $statement
	 * @return bool;
	 */
	public static function updateWhere(array $data, $limit = 1, $statement){
		$args = func_get_args();
		$args = array_slice($args, 2);
		$obj = static::meta();
		$statement = self::parseConditionStatement($args, $obj);
		$table = $obj->getTableName();
		$result = $obj->getDbRecord(self::DB_WRITE)->update($table, $data, $statement, $limit);
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
		$result = $obj->getDbRecord(self::DB_WRITE)->delete($table, $statement, $limit);
		return $result;
	}

	/**
	 * 获取所有记录
	 * @param bool $as_array return as array
	 * @return array
	 */
	public function all($as_array = false){
		$list = $this->getDbRecord(self::DB_READ)->getAll($this->query);
		if($as_array){
			return $list;
		}

		$result = array();
		if($list){
			foreach($list as $item){
				$tmp = clone $this;
				$tmp->setValues($item);
				$tmp->resetValueChangeState();
				$result[] = $tmp;
			}
		}
		return $result;
	}

	/**
	 * 获取一条记录
	 * @param bool $as_array
	 * @return Model|Array|NULL
	 */
	public function one($as_array = false){
		$data = $this->getDbRecord(self::DB_READ)->getOne($this->query);
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
	 * @param $key
	 * @return mixed|null
	 */
	public function ceil($key){
		$this->query->field($key);
		$data = $this->getDbRecord(self::DB_READ)->getOne($this->query);
		return $data ? array_pop($data) : null;
	}

	/**
	 * 根据分段进行数据处理
	 * @param int $size
	 * @param $handler
	 * @return bool
	 */
	private $query_result;
	public function chunk($size = 1, $handler){
		if(!isset($this->query_result)){
			$this->query_result = $this->getDbRecord(self::DB_READ)->query($this->query);
		}

		$i = 0;
		$result = array();
		while($item = Record::fetchAssoc($this->query_result) && $i++ < $size){
			$result[] = $item;
		}

		$ret = null;
		if(!empty($result)){
			$ret = call_user_func($handler, $result);
			$this->chunk($size, $handler);
		}
		if($ret === false){
			return false;
		}
		return true;
	}

	/**
	 * 获取当前查询条数
	 * @return int
	 */
	public function count(){
		$count = $this->getDbRecord(self::DB_READ)->getCount($this->query);
		return $count;
	}

	/**
	 * 分页查询记录
	 * @param string $page
	 * @param bool $as_array return as array
	 * @return array || null
	 */
	public function paginate($page = null, $as_array = false){
		$list = $this->getDbRecord(self::DB_READ)->getPage($this->query, $page);
		if($as_array){
			return $list;
		}
		$result = array();
		if($list){
			foreach($list as $item){
				$tmp = clone $this;
				$tmp->setValues($item);
				$tmp->resetValueChangeState();
				$result[] = $tmp;
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
	final public function update(){
		if($this->onBeforeUpdate() === false){
			return false;
		}

		$data = $this->getValues();
		$pk_val = $data[$this->getPrimaryKey()];
		if(!$pk_val){
			throw new Exception("primary value no found");
		}
		unset($data[$this->getPrimaryKey()]);

		$update_data = array();
		$filters = array();
		$item_change_keys = $this->getValueChangeKeys();
		$all_filter_rules = $this->getFilterRules();
		foreach($data as $key => $pro){
			if(isset($item_change_keys[$key]) && (empty($all_filter_rules) || $all_filter_rules[$key])){
				$update_data[$key] = $pro;
				$filters[$key] = $all_filter_rules[$key];
			}
		}

		if(!empty($update_data)){
			$result = Filter::init()->filterArray($update_data, $filters);
			if(!empty($result)){
				throw new BizException(array_pop($result), json_encode($result));
			}

			//unique
			$properties_defines = $this->getPropertiesDefine();
			if(!empty($properties_defines)){
				foreach($properties_defines as $field_key => $rule){
					if(isset($rule['unique']) && $data[$field_key]){
						$c = self::find($this->getPrimaryKey()." != '".addslashes($pk_val).
							"' AND $field_key = '".addslashes($data[$field_key])."'")->count();
						if($c){
							throw new BizException($rule['unique']);
						}
					}
				}
			}
			return $this->getDbRecord(self::DB_WRITE)->update($this->getTableName(), $update_data, $this->getPrimaryKey().'='.$pk_val);
		}else{
			throw new Exception("no properties update");
		}
	}

	/**
	 * 插入当前对象
	 * @throws \Lite\Exception\BizException
	 * @return string | bool 返回插入的id，或者失败(false)
	 */
	final public function insert(){
		if($this->onBeforeInsert() === false){
			return false;
		}

		$data = $this->getValues();
		$filter_rules = $this->getFilterRules();

		//插入需要过所有的过滤器
		foreach($filter_rules as $key => $filter){
			if(!isset($data[$key])){
				$data[$key] = null;
			}
		}

		//remove no scalar
		foreach($data as $key => $item){
			if(!is_scalar($item)){
				unset($data[$key]);
			}
		}

		$result = Filter::init()->filterArray($data, $filter_rules);
		if(!empty($result)){
			throw new BizException(array_pop($result));
		}

		//unique
		$properties_defines = $this->getPropertiesDefine();
		if(!empty($properties_defines)){
			foreach($properties_defines as $field_key => $rule){
				if(isset($rule['unique']) && $data[$field_key]){
					$c = self::find("$field_key = '".addslashes($data[$field_key])."'")->count();
					if($c){
						throw new BizException($rule['unique']);
					}
				}
			}
		}
		$result = $this->getDbRecord(self::DB_WRITE)->insert($this->getTableName(), $data);
		if($result){
			$pk_val = $this->getDbRecord(self::DB_WRITE)->getLastInsertId();
			$this->setValue($this->getPrimaryKey(), $pk_val);
			return $pk_val;
		}
		return false;
	}

	/**
	 * 批量插入数据
	 * 由于这里插入会涉及到数据检查，最终效果还是一条一条的插入
	 * @param $data_list
	 * @param bool $break_on_fail
	 * @throws \Lite\Exception\BizException
	 * @throws \Lite\Exception\Exception
	 * @return array | bool
	 */
	final public static function insertMany($data_list, $break_on_fail = true){
		if(count($data_list, COUNT_RECURSIVE) == count($data_list)){
			throw new Exception('2 dimension array needed');
		}

		$obj = static::meta();
		$return_list = array();
		foreach($data_list as $data){
			$tmp = clone($obj);
			$tmp->setValues($data);
			if($tmp->onBeforeInsert() === false){
				if($break_on_fail){
					return false;
				}else{
					continue;
				}
			}
			$filter_rules = $tmp->getFilterRules();

			//插入需要过所有的过滤器
			foreach($filter_rules as $key => $filter){
				if(!isset($data[$key])){
					$data[$key] = null;
				}
			}

			//remove no scalar
			foreach($data as $key => $item){
				if(!is_scalar($item)){
					unset($data[$key]);
				}
			}

			$result = Filter::init()->filterArray($data, $filter_rules);
			if(!empty($result)){
				if($break_on_fail){
					throw new BizException(array_pop($result));
				}else{
					continue;
				}
			}

			$result = $tmp->getDbRecord(self::DB_WRITE)->insert($tmp->getTableName(), $data);
			if($result){
				$pk_val = $tmp->getDbRecord(self::DB_WRITE)->getLastInsertId();
				$tmp->setValue($tmp->getPrimaryKey(), $pk_val);
				$return_list[] = $pk_val;
			}else{
				if($break_on_fail){
					return false;
				}
			}
		}
		return $return_list;
	}

	/**
	 * 从数据库从删除当前对象对应的记录
	 * @return bool
	 */
	public function delete(){
		$pk_val = $this[$this->getPrimaryKey()];
		if(!$pk_val){
			return false;
		}
		$statement = $this->getPrimaryKey().'='.$pk_val;
		$result = $this->getDbRecord(self::DB_WRITE)->delete($this->getTableName(), $statement, 1);
		return !!$result;
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
			foreach($args as $key => $val) {
				if(is_array($val)){
					array_walk($val, function (&$item) use ($obj){
						$item = $obj->getDbRecord(self::DB_READ)->quote($item);
					});
					if(!empty($val)){
						$rst .= $arr[$key] . '(' . join(',', $val) . ')';
					} else {
						$rst .= $arr[$key] . '(' . $obj->getDbRecord(self::DB_READ)->quote('') . ')';
					}
				} else {
					$rst .= $arr[$key] . $obj->getDbRecord(self::DB_READ)->quote($val);
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
	final public function save(){
		if($this->onBeforeSave() === false){
			return false;
		}

		$data = $this->getValues();
		$has_pk = !empty($data[$this->getPrimaryKey()]);

		if($has_pk){
			return $this->update();
		}else if(!empty($data)){
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
	 * 支持：'name' => array('has_one'=>callable, 'target_key'=>'category_id','source_key'=>默认当前对象PK)
	 * 支持：'children' => array('has_many'=>callable, 'target_key'=>'category_id', 'source_key' => 默认当前对象PK)
	 * 支持：'name' => array('getter' => function($k){})
	 * 支持：'name' => array('setter' => function($k, $v){})
	 * @param $key
	 * @throws \Lite\Exception\Exception
	 * @return mixed
	 */
	public function __get($key){
		$defines = $this->getPropertiesDefine($key);
		if($defines && ($defines['has_one'] || $defines['has_many'])){
			$source_key = $defines['source_key'];
			$target_key = $defines['target_key'];

			if($defines['has_one']){
				if(!$target_key && !$source_key){
					throw new Exception('has one config must define target key or source key');
				}
				$match_val = $this->getValue($source_key ?: $this->getPrimaryKey());
				/** @var Model $class */
				$class = $defines['has_one'];
				if(!$target_key){
					return $class::findOneByPk($match_val);
				}else{
					return $class::find("$target_key = ?", $match_val)->one();
				}
			}
			if($defines['has_many']){
				if(!$target_key){
					throw new Exception('has many config must define target key');
				}
				/** @var Model $class */
				$class = $defines['has_many'];
				$match_val = $this->getValue($source_key ?: $this->getPrimaryKey());
				return $class::find("$target_key = ?", $match_val)->all();
			}
		}
		return parent::__get($key);
	}

	/**
	 * 转换当前查询对象为字符串
	 * @return string|void
	 */
	public function __toString(){
		return $this->query.'';
	}
}

/**
 * DB Helper，主要提供给Model内部使用。
 * 外部还是尽量调用DB\Model::setQuery来使用
 */
class __ModelHelper__ extends Model{
	public function getTableName(){
	}

	public function getPrimaryKey(){
	}
}