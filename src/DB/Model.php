<?php
namespace Lite\DB;

use Lite\Cache\CacheVar;
use Lite\Core\Config;
use Lite\Core\DAO;
use Lite\Core\Hooker;
use Lite\DB\Driver\DBAbstract;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use Lite\Exception\RouterException;
use function Lite\Func\_tl;
use function LFPhp\Func\array_clear_fields;
use function LFPhp\Func\array_first;
use function LFPhp\Func\array_group;
use function LFPhp\Func\array_index;
use function LFPhp\Func\array_orderby;
use function LFPhp\Func\time_range_v;

/**
 * 数据库结合数据模型提供的操作抽象类, 实际业务逻辑最好通过集成该类来实现
 * 相应的业务数据功能逻辑.
 * @method static[]|Query order
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
abstract class Model extends DAO{
	const DB_READ = 1;
	const DB_WRITE = 2;

	const LAST_OP_SELECT = Query::SELECT;
	const LAST_OP_UPDATE = Query::UPDATE;
	const LAST_OP_DELETE = Query::DELETE;
	const LAST_OP_INSERT = Query::INSERT;

	/** @var string current model last operate type */
	private $last_operate_type = self::LAST_OP_SELECT;

	/** @var array database config */
	private $db_config = array();

	/** @var Query db query object * */
	private $query = null;

	/**
	 * @var array model prefetch fields group
	 * @example [`db1`.`table` => ['name', 'extra'], `db2`.`table2` => ['pro1', 'pro2'],...]
	 */
	private static $prefetch_groups = [];

	/**
	 * 获取当前调用ORM对象
	 * @return static|Query
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
	 * on after update
	 */
	public function onAfterUpdate(){}

	/**
	 * 记录插入之前事件
	 * @return boolean
	 */
	public function onBeforeInsert(){
		return true;
	}

	/**
	 * 记录插入之后
	 */
	public function onAfterInsert(){}

	/**
	 * 记录删除之前
	 * @return bool
	 */
	public function onBeforeDelete(){
		return true;
	}

	/**
	 * 记录删除之后
	 */
	public function onAfterDelete(){}

	/**
	 * records on change event
	 */
	protected static function onBeforeChanged(){
		return true;
	}

	/**
	 * 获取当前数据库表表名（不含前缀）
	 * @return string
	 */
	abstract public function getTableName();

	/**
	 * 获取数据库表描述名称
	 * @return string
	 */
	public function getModelDesc(){
		return $this->getTableName();
	}

	/**
	 * 获取数据库表全名
	 * @return string
	 */
	public function getTableFullName(){
		return $this->getDbTablePrefix().$this->getTableName();
	}

	/**
	 * @param int $op_type
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public function getTableFullNameWithDbName($op_type = self::DB_READ){
		$all_configs = $this->getDbConfig();
		$config = $this->parseConfig($op_type, $all_configs);
		$db = $config['database'];
		$table = $this->getTableFullName();
		return "`$db`.`$table`";
	}

	/**
	 * 获取数据库表主键
	 * @return string
	 */
	public function getPrimaryKey(){
		$defines = $this->getEntityPropertiesDefine();
		foreach($defines as $k => $def){
			if($def['primary']){
				return $k;
			}
		}
		throw new Exception('No primary key found in table defines');
	}

	/**
	 * 获取主键值
	 * @return mixed
	 * @throws \Lite\Exception\Exception
	 */
	public function getPrimaryKeyValue(){
		$pk = $this->getPrimaryKey();
		return $this->$pk;
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
	 * 解释SQL语句
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
		return (isset($config['prefix']) && $config['prefix']) ? $config['prefix'] : '';
	}

	/**
	 * 解析数据库配置
	 * 分析出配置中的读、写配置
	 * @param string $operate_type
	 * @param array $all_config
	 * @return array
	 */
	private function parseConfig($operate_type, array $all_config){
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
			throw new Exception('DB config error for driver type:'.$operate_type);
		}
		return $host_config;
	}

	/**
	 * 设置查询SQL语句
	 * @param string|Query $query
	 * @return static|Query
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
		throw new Exception('Query string required');
	}

	/**
	 * 获取当前查询对象
	 * @return \Lite\DB\Query
	 */
	public function getQuery(){
		return $this->query;
	}

	/**
	 * 事务处理
	 * @param callable $handler 处理函数，若函数返回false或抛出Exception，将停止提交，执行事务回滚
	 * @return mixed 闭包函数返回值透传
	 */
	public static function transaction($handler){
		$driver = self::meta()->getDbDriver(Model::DB_WRITE);
		try{
			$driver->beginTransaction();
			$ret = call_user_func($handler);
			if($ret === false){
				throw new Exception('Database transaction interrupt');
			}
			if(!$driver->commit()){
				throw new Exception('Database commit fail');
			}
			return $ret;
		}catch(\Exception $exception){
			$driver->rollback();
			throw $exception;
		}finally{
			$driver->cancelTransactionState();
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
	 * 批量Model缓存处理绑定
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
		Hooker::add(DBAbstract::EVENT_BEFORE_DB_GET_LIST, function($param) use ($check_table_hit, $getter){
			$model = $check_table_hit($param['query']);
			if($model){
				$result = call_user_func($getter, $model, $param['query']);
				if(isset($result)){
					$param['result'] = $result;
				}
			}
		});

		//after get list, set cache
		Hooker::add(DBAbstract::EVENT_AFTER_DB_GET_LIST, function($param) use ($check_table_hit, $setter){
			$model = $check_table_hit($param['query']);
			if($model){
				call_user_func($setter, $model, $param['query'], $param['result']);
			}
		});

		//flush table cache
		Hooker::add(DBAbstract::EVENT_AFTER_DB_QUERY, function($query) use($check_table_hit, $flusher){
			$model = $check_table_hit($query);
			if($model && Query::isWriteOperation($query)){
				call_user_func($flusher, $model, $query);
			}
		});
	}

	/**
	 * 查找
	 * @param string $statement 条件表达式
	 * @param string $var,... 条件表达式扩展
	 * @return static|Query
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
	 * 添加更多查询条件
	 * @param array $args 查询条件
	 * @return static|Query
	 */
	public function where(...$args){
		$statement = self::parseConditionStatement($args, $this);
		$this->query->where($statement);
		return $this;
	}

	/**
	 * 快速查询用户请求过来的信息，只有第二个参数为不为空的时候才去查询，空数组还是会去查。
	 * @param $st
	 * @param $val
	 * @return static
	 */
	public function whereOnSet($st, $val){
		$args = func_get_args();
		foreach($args as $k=>$arg){
			if(is_string($arg)){
				$args[$k] = trim($arg);
			}
		}
		if(is_array($val) || strlen($val)){
			$statement = self::parseConditionStatement($args, $this);
			$this->query->where($statement);
		}
		return $this;
	}

	/**
	 * Prefetch relate fields [via foreign keys]
	 * @param array $fields
	 * @return $this
	 */
	public function prefetch(array $fields){
		if(!$fields){
			return $this;
		}
		$defines = $this->getPropertiesDefine();
		$table_full = $this->getTableFullNameWithDbName();
		foreach($fields as $f){
			if(!$defines[$f]){
				throw new Exception(_tl("Prefetch field:{field} no defined", ['field'=>$f]));
			}
			if(!$defines[$f]['foreign'] && !$defines[$f]['has_many'] && !$defines[$f]['has_one']){
				throw new Exception(_tl('Prefetch field:{field} must define foreign|has_one|has_many target', ['field'=>$f]));
			}
			if(!isset(self::$prefetch_groups[$table_full]) || !self::$prefetch_groups[$table_full]){
				self::$prefetch_groups[$table_full] = [];
			}
			self::$prefetch_groups[$table_full][] = $f;
		}
		return $this;
	}

	/**
	 * 自动检测变量类型、变量值设置匹对记录
	 * 当前操作仅做“等于”，“包含”比对，不做其他比对
	 * @param $fields
	 * @param $param
	 * @return $this
	 */
	public function whereEqualOnSetViaFields(array $fields, array $param = []){
		foreach($fields as $field){
			$val = $param[$field];
			if(is_array($val) || strlen($val)){
				$comparison =  is_array($val) ? 'IN' : '=';
				$this->whereOnSet("$field $comparison ?", $val);
			}
		}
		return $this;
	}

	/**
	 * 快速LIKE查询用户请求过来的信息，当LIKE内容为空时，不执行查询，如 %%。
	 * @param $st
	 * @param $val
	 * @return static|Query
	 */
	public function whereLikeOnSet($st, $val){
		$args = func_get_args();
		if(strlen(trim(str_replace(DBAbstract::LIKE_RESERVED_CHARS, '', $val)))){
			return call_user_func_array(array($this, 'whereOnSet'), $args);
		}
		return $this;
	}

	/**
	 * 批量LIKE查询（whereLikeOnSet方法快捷用法）
	 * @param array $fields
	 * @param $val
	 * @return static
	 */
	public function whereLikeOnSetBatch(array $fields, $val){
		$st = join(' LIKE ? OR ', $fields).' LIKE ?';
		$values = array_fill(0, count($fields), $val);
		array_unshift($values, $st);
		return call_user_func_array([$this, 'whereLikeOnSet'], $values);
	}

	/**
	 * 检测字段是否处于指定范围之中
	 * @param string $field
	 * @param number|null $min 最小端
	 * @param number|null $max 最大端
	 * @param bool $equal_cmp 是否包含等于
	 * @return Query|static
	 */
	public function between($field, $min = null, $max = null, $equal_cmp = true){
		$cmp = $equal_cmp ? '=' : '';
		$hit = false;
		if(strlen($min)){
			$min = addslashes($min);
			$this->query->where($field, ">$cmp", $min);
			$hit = true;
		}
		if(strlen($max)){
			$max = addslashes($max);
			$this->query->where($field, "<$cmp", $max);
			$hit = true;
		}
		if($hit){
			$this->query->where("`$field` IS NOT NULL");
		}
		return $this;
	}

	/**
	 * 创建新对象
	 * @param $data
	 * @return bool|static|Query
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
	 * @return static|Query|array
	 */
	public static function findOneByPk($val, $as_array = false){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();
		//只有在开启去重查询时，cache才生效，
		//由于可能存在多方关联到当前对象，因此这里不考虑prefetch是否启用
		if(DBAbstract::distinctQueryState()){
			$cache_key = $obj->getTableFullNameWithDbName()."/$pk/$val";
			if($data = CacheVar::instance()->get($cache_key)){
				return $as_array ? $data : new static($data);
			}
		}
		return static::find($pk.'=?', $val)->one($as_array);
	}

	/**
	 * @param $val
	 * @param bool $as_array
	 * @return static
	 */
	public static function findOneByPkOrFail($val, $as_array = false){
		$data = static::findOneByPk($val, $as_array);
		if(!$data){
			throw new RouterException('找不到相关数据(pk:'.$val.')。');
		}
		return $data;
	}

	/**
	 * 有主键列表查询多条记录
	 * 单主键列表为空，该方法会返回空数组结果
	 * @param array $pk_values
	 * @param bool $as_array
	 * @return static[]
    */
	public static function findByPks(array $pk_values, $as_array = false){
		if(!$pk_values){
			return [];
		}

		$obj = static::meta();
		$pk = $obj->getPrimaryKey();

		//只有在开启去重查询时，cache才生效
		//由于可能存在多方关联到当前对象，因此这里不考虑prefetch是否启用
		if(DBAbstract::distinctQueryState()){
			$result = $obj->_getObjectCacheList($pk, $pk_values, $as_array, $miss_matches);
			if($miss_matches){
				$rests = static::find($obj->getPrimaryKey().' IN ?', $miss_matches)->all($as_array);
				$result = array_merge($result, $rests);
			}
			return $result;
		}
		return static::find("$pk IN ?", $pk_values)->all($as_array);
	}

	/**
	 * 获取对象行缓存
	 * @param $field
	 * @param array $field_values
	 * @param $as_array
	 * @param array $miss_matches
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	private function _getObjectCacheList($field, array $field_values, $as_array = false, &$miss_matches = []){
		$cache_prefix_key = $this->getTableFullNameWithDbName()."/$field/";
		$result = [];
		foreach($field_values as $k=> $val){
			$item = CacheVar::instance()->get($cache_prefix_key."$val");
			if(isset($item)){
				unset($field_values[$k]);
				$result[] = $as_array ? $item : new static($item);
			}
		}
		$miss_matches = $field_values;
		return $result;
	}

	/**
	 * @param $field
	 * @param $field_value
	 * @param bool $as_array
	 * @param bool $has_many
	 * @return $this|mixed
	 * @throws \Lite\Exception\Exception
	 */
	private function _getObjectCache($field, $field_value, $as_array = false, $has_many = false){
		$cache_key = $this->getTableFullNameWithDbName()."/$field/$field_value";
		$data = CacheVar::instance()->get($cache_key);
		//type adjust
		if(!$data && ($has_many || $as_array)){
			$data = [];
		}
		if($as_array){
			return $data;
		}
		if($has_many){
			$ret = [];
			foreach($data as $item){
				$ret[] = new static($item);
			}
			return $ret;
		}
		return new static($data);
	}

	private function _setObjectCaches($field, $data_list){
		$cache_key = $this->getTableFullNameWithDbName()."/$field/";
		CacheVar::instance()->setDistributed($cache_key, $data_list);
	}

	/**
	 * 根据主键值删除一条记录
	 * @param string $val
	 * @return bool

	 */
	public static function delByPk($val){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();
		static::deleteWhere(0, "$pk=?", $val);
		return static::meta()->getAffectNum();
	}

	/**
	 * 根据主键删除记录
	 * @param $val
	 * @return bool
	 */
	public static function delByPkOrFail($val){
		static::delByPk($val);
		$count = static::meta()->getAffectNum();;
		if(!$count){
			throw new RouterException('记录已被删除');
		}
		return $count;
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

	 */
	public static function updateByPks($pks, array $data){
		$obj = static::meta();
		$pk = $obj->getPrimaryKey();
		return static::updateWhere($data, count($pks), "$pk IN ?", $pks);
	}

	/**
	 * 根据条件更新数据
	 * @param array $data
	 * @param int $limit 为了安全，调用方必须传入具体数值，如不限制更新数量，可设置为0
	 * @param string $statement 为了安全，调用方必须传入具体条件，如不限制，可设置为空字符串
	 * @return bool;

	 */
	public static function updateWhere(array $data, $limit, $statement){
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
	 * @param int $limit 为了安全，调用方必须传入具体数值，如不限制删除数量，可设置为0
	 * @param string $statement 为了安全，调用方必须传入具体条件，如不限制，可设置为空字符串
	 * @return bool
	 */
	public static function deleteWhere($limit, $statement){
		$args = func_get_args();
		$args = array_slice($args, 1);

		$obj = static::meta();
		$statement = self::parseConditionStatement($args, $obj);
		$table = $obj->getTableName();
		$result = $obj->getDbDriver(self::DB_WRITE)->delete($table, $statement, $limit);
		return $result;
	}

	/**
	 * 清空数据
	 * @return bool
	 */
	public static function truncate(){
		$obj = static::meta();
		$table = $obj->getTableFullName();
		return $obj->getDbDriver(self::DB_WRITE)->delete($table, '', 0);
	}

	/**
	 * 获取所有记录
	 * @param bool $as_array return as array
	 * @param string $unique_key 用于组成返回数组的唯一性key
	 * @return static[]

	 */
	public function all($as_array = false, $unique_key = ''){
		$list = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		if(!$list){
			return array();
		}
		return $this->__handleListResult($list, $as_array, $unique_key);
	}

	/**
	 * 分页查询记录
	 * @param string $page
	 * @param bool $as_array 是否以数组方式返回，默认为Model对象数组
	 * @param string $unique_key 用于组成返回数组的唯一性key
	 * @return static[]
	 */
	public function paginate($page = null, $as_array = false, $unique_key = ''){
		$list = $this->getDbDriver(self::DB_READ)->getPage($this->query, $page);
		return $this->__handleListResult($list, $as_array, $unique_key);
	}

	/**
	 * 格式化数据列表，预取数据
	 * @param static[] $list
	 * @param bool $as_array 是否作为二维数组返回，默认为对象数组
	 * @param string $unique_key 数组下标key
	 * @return array
	 */
	private function __handleListResult(array $list, $as_array = false, $unique_key = ''){
		 $this->__doPrefetchList($list);
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
	 * Prefetch relate model list
	 * @param $list
	 * @throws \Lite\Exception\Exception
	 */
	private function __doPrefetchList($list){
		if(!$list){
			return;
		}

		$table_full = $this->getTableFullNameWithDbName();
		$prefetch_fields = isset(self::$prefetch_groups[$table_full]) ? self::$prefetch_groups[$table_full] : null;
		if(!$prefetch_fields){
			return;
		}

		$defines = $this->getPropertiesDefine();
		foreach($prefetch_fields as $field){
			$def = $defines[$field];
			if($def['foreign'] || $def['has_one'] || $def['has_many']){
				if($def['foreign']){
					/** @var Model $target_class */
					$target_class = $def['foreign'];
					$target_instance = $target_class::meta();
					$target_field = $target_instance->getPrimaryKey();
					$current_field = $field;
				} else {
					/** @var Model $target_class */
					list($current_field, $target_class, $target_field) = $def['has_one'] ?: $def['has_many'];
					$target_instance = $target_class::meta();
					$target_field = $target_field ?: $target_instance->getPrimaryKey();
				}

				$field_columns = array_unique(array_column($list, $current_field));

				/** @var array $tmp_data */
				$tmp_data = $target_class::find("$target_field IN ?", $field_columns)->all(true);
				$tmp_data = array_group($tmp_data, $target_field, isset($def['has_many']) ? !$def['has_many'] : true);
				$target_instance->_setObjectCaches($target_field, $tmp_data);
			}
		}
	}

	/**
	 * 获取一条记录
	 * @param bool $as_array 是否以数组方式返回，默认为Model对象
	 * @return static|array|null
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
	 * 获取一条记录，为空时抛异常
	 * @param bool $as_array 是否以数组方式返回，默认为Model对象
	 * @return static
	 */
	public function oneOrFail($as_array = false){
		$data = $this->one($as_array);
		if(!$data){
			throw new RouterException('找不到相关数据。');
		}
		return $data;
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

	/**
	 * 计算字段值总和
	 * @param string|array $fields 需要计算字段名称（列表）
	 * @param array $group_by 使用指定字段（列表）作为合并维度
	 * @return number|array 结果总和，或以指定字段列表作为下标的结果总和
	 * @example
	 * <pre>
	 * $report->sum('order_price', 'original_price');
	 * $report->group('platform')->sum('order_price');
	 *
	 * sum('price'); //10.00
	 * sum(['price','count']); //[10.00, 14]
	 * sum(['price', 'count'], ['platform','order_type']); //
	 * [
	 *  ['platform,order_type'=>'amazon', 'price'=>10.00, 'count'=>14],
	 *  ['platform'=>'ebay', 'price'=>10.00, 'count'=>14],...
	 * ]
	 * sum(['price', 'count'], ['platform', 'order_type']);
	 * </pre>
	 */
	public function sum($fields, $group_by=[]){
		$fields = is_array($fields)?$fields:[$fields];
		$str = [];
		foreach($fields as $_=>$field){
			$str[] = "SUM($field) as $field";
		}

		if($group_by){
			$str = array_merge($str,$group_by);
			$this->query->group(implode(',',$group_by));
		}
		$this->query->field(join(',', $str));

		$data = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		if($group_by){
			return $data;
		}
		if(count($fields) == 1){
			return array_first(array_first($data));
		} else {
			return array_values(array_first($data));
		}
	}

	/**
	 * 对象重排序
	 * 列表中以数值从大到小进行排序，每次调用将重新计算所有排序
	 * 用法：<pre>
	 * $category->render(true, 'sort', 'status=?', $enabled);
	 * </pre>
	 * @param bool $move_up 是否为向上移动
	 * @param string $sort_key 排序字段名称，默认为sort
	 * @param string $statement 排序范围过滤表达式，默认为所有数据
	 * @return bool
	 */
	public function reorder($move_up, $sort_key = 'sort', $statement = ''){
		$pk = $this->getPrimaryKey();
		$pk_v = $this->{$pk};
		$query = static::find()->field($pk, $sort_key);

		//query statement
		if($statement){
			$statement_list = func_get_args();
			$statement_list = array_slice($statement_list, 3);
			if($statement_list){
				call_user_func_array([$query, 'where'], $statement_list);
			}
		}

		$sort_list = $query->all(true);
		$count = count($sort_list);
		$sort_list = array_orderby($sort_list, $sort_key, SORT_DESC);
		$current_idx = array_index($sort_list, function($item) use ($pk, $pk_v){
			return $item[$pk] == $pk_v;
		});
		if($current_idx === false){
			return false;
		}

		//已经是置顶或者置底
		if($move_up && $current_idx == 0 || (!$move_up && $current_idx == $count-1)){
			return true;
		}

		if($move_up){
			$tmp = $sort_list[$current_idx-1];
			$sort_list[$current_idx-1] = $sort_list[$current_idx];
			$sort_list[$current_idx] = $tmp;
		} else {
			$tmp = $sort_list[$current_idx+1];
			$sort_list[$current_idx+1] = $sort_list[$current_idx];
			$sort_list[$current_idx] = $tmp;
		}

		//force reordering
		foreach($sort_list as $k => $v){
			static::updateWhere([$sort_key => $count-$k-1], 1, "$pk = ?", $v[$pk]);
		}
		return true;
	}

	/**
	 * 获取指定列，作为一维数组返回
	 * @param $key
	 * @return array
	 */
	public function column($key){
		$obj = self::meta();
		$pro_defines = $obj->getEntityPropertiesDefine();
		if(isset($pro_defines[$key]) && $pro_defines[$key]){
			$this->query->field($key);
		}
		$data = $this->getDbDriver(self::DB_READ)->getAll($this->query);
		return $data ? array_column($data, $key) : array();
	}

	/**
	 * 以映射数组方式返回
	 * <pre>
	 * $query->map('id', 'name'); //返回： [[id_val=>name_val],...] 格式数据
	 * $query->map('id', ['name']); //返回： [[id_val=>[name=>name_val],...] 格式数据
	 * $query->map('id', ['name', 'gender']); //返回： [[id_val=>[name=>name_val, gender=>gender_val],...] 格式数据
	 * </pre>
	 * @param $key
	 * @param $val
	 * @return array
	 */
	public function map($key, $val){
		if(is_string($val)){
			$this->query->field($key, $val);
			$tmp = $this->getDbDriver(self::DB_READ)->getAll($this->query);
			return array_combine(array_column($tmp, $key), array_column($tmp, $val));
		} else if(is_array($val)){
			$tmp = $val;
			$tmp[] = $key;
			$this->query->field($tmp);
			$tmp = $this->getDbDriver(self::DB_READ)->getAll($this->query);
			$ret = [];
			foreach($tmp as $item){
				$ret[$item[$key]] = [];
				foreach($val as $field){
					$ret[$item[$key]][$field] = $item[$field];
				}
			}
			return $ret;
		}
		throw new Exception('Mapping parameter error', null, [$key, $val]);
	}

	/**
	 * 根据分段进行数据处理，常见用于节省WebServer内存操作
	 * @param int $size 分块大小
	 * @param callable $handler 回调函数
	 * @param bool $as_array 查询结果作为数组格式回调
	 * @return bool 是否执行了分块动作
	 */
	public function chunk($size, $handler, $as_array = false){
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
			if(call_user_func($handler, $data, $page_index++, $page_total, $total) === false){
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
	 * 数据记录监听
	 * @param callable $handler 处理函数，若返回false，则终端监听
	 * @param int $chunk_size 获取数据时的分块大小
	 * @param int $sleep_interval_sec 无数据时睡眠时长（秒）
	 * @param bool|callable|null $debugger 数据信息调试器
	 * @return bool 是否正常执行
	 */
	public function watch(callable $handler, $chunk_size = 50, $sleep_interval_sec = 3, $debugger = true){
		if($debugger === true) {
			$debugger = function(...$args){
				echo "\n".date('Y-m-d H:i:s')."\t".join("\t", func_get_args());
			};
		} else if(!$debugger || !is_callable($debugger)){
			$debugger = function(){};
		}

		$dist_status = DBAbstract::distinctQueryState();
		DBAbstract::distinctQueryOff();
		while(true){
			$obj = clone($this);
			$break = false;
			$start = microtime(true);
			$exists = $obj->chunk($chunk_size, function($data_list, $page_index, $page_total, $item_total) use ($handler, $chunk_size, $debugger, $start, &$break){
				/** @var Model $item */
				foreach($data_list as $k => $item){
					$cur = $page_index*$chunk_size+$k+1;
					$now = microtime(true);
					$left = ($now-$start)*($item_total-$cur)/$cur;
					$left_time = time_range_v($left);
					$debugger('Handling item: ['.$cur.'/'.$item_total." - $left_time]", substr(json_encode($item->toArray()), 0, 200));
					$ret = call_user_func($handler, $item, $page_index, $page_total, $item_total);
					if($ret === false){
						$debugger('Handler Break!');
						$break = true;
						return false;
					}
				}
				return true;
			});
			unset($obj);
			if($break){
				$debugger('Handler Break!');
				return false;
			}
			if(!$exists){
				$debugger('No data found, sleep for '.$sleep_interval_sec.' seconds.');
				sleep($sleep_interval_sec);
			}
		}
		if($dist_status){
			DBAbstract::distinctQueryOn();
		}
		return true;
	}

	/**
	 * 获取当前查询条数
	 * @return int
	 */
	public function count(){
		$driver = $this->getDbDriver(self::DB_READ);
		$count = $driver->getCount($this->query);
		return $count;
	}

	/**
	 * 裁剪属性字符串长度，使得对象属性符合定义
	 * @return array 被裁减字段结果列表信息 [field1=> [define length, cut length], ...]
	 */
	public function trimPropertiesData(){
		$match_fields = [];
		$data = $this->getValues();
		$defines = $this->getPropertiesDefine();
		$cut_by_utf8 = $this->getDbDriver(self::DB_WRITE)->getConfig('type') == 'mysql';
		foreach($defines as $field=>$def){
			if($data[$field] && $def['length'] && $def['entity'] && !$def['readonly'] && $def['type'] == 'string'){
				$len = $cut_by_utf8 ? mb_strlen($data[$field], 'utf-8') : strlen($data[$field]);
				if($len > $def['length']){
					$this->{$field} = $cut_by_utf8 ? mb_substr($data[$field], 0, $def['length'], 'utf-8') : substr($data[$field], 0, $def['length']);
					$match_fields[$field] = [$def['length'], $len - $def['length']];
				}
			}
		}
		return $match_fields;
	}

	/**
	 * 更新当前对象
	 * @param bool $flush_all 是否刷新全部数据，包含readonly数据
	 * @return bool|number
	 */
	public function update($flush_all = false){
		if($this->onBeforeUpdate() === false || self::onBeforeChanged() === false){
			return false;
		}

		$this->last_operate_type = self::LAST_OP_UPDATE;
		$data = $this->getValues();
		$pk = $this->getPrimaryKey();

		//只更新改变的值
		$change_keys = $this->getValueChangeKeys();
		$data = array_clear_fields(array_keys($change_keys), $data);
		$data = $this->validate($data, Query::UPDATE, $flush_all);
		$this->getDbDriver(self::DB_WRITE)->update($this->getTableName(), $data, $this->getPrimaryKey().'='.$this->$pk);
		$this->onAfterUpdate();
		return $this->{$this->getPrimaryKey()};
	}

	/**
	 * 插入当前对象
	 * @param bool $flush_all 是否刷新全部数据，包含readonly数据
	 * @return string|bool 返回插入的id，或者失败(false)
	 */
	public function insert($flush_all = false){
		if($this->onBeforeInsert() === false || self::onBeforeChanged() === false){
			return false;
		}
		$this->last_operate_type = self::LAST_OP_INSERT;
		$data = $this->getValues();
		$data = $this->validate($data, Query::INSERT, $flush_all);

		$result = $this->getDbDriver(self::DB_WRITE)->insert($this->getTableName(), $data);
		if($result){
			$pk_val = $this->getDbDriver(self::DB_WRITE)->getLastInsertId();
			$this->setValue($this->getPrimaryKey(), $pk_val);
			$this->onAfterInsert();
			return $pk_val;
		}
		return false;
	}

	/**
	 * 替换数据
	 * @param array $data
	 * @param int $limit
	 * @param array ...$args 查询条件
	 * @return mixed
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
	 * 增加或减少计数
	 * @param string $field 计数使用的字段
	 * @param int $offset 计数偏移量，如1，-1
	 * @param int $limit 条数限制，默认为0表示不限制更新条数
	 * @param array ...$args 查询条件
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
	 * 获取字段-别名映射表
	 * @return array [field=>name, ...]
	 */
	public static function getEntityFieldAliasMap(){
		$obj = self::meta();
		$ret = [];
		$defines = $obj->getEntityPropertiesDefine();
		foreach($defines as $field=>$def){
			$ret[$field] = $def['alias'] ?: $field;
		}
		return $ret;
	}

	/**
	 * 数据校验
	 * @param array $src_data 元数据
	 * @param string $query_type 数据库操作类型
	 * @param bool $flush_all 是否校验全部数据，包含readonly数据
	 * @return array $data
	 */
	private function validate($src_data = array(), $query_type = Query::INSERT, $flush_all = false){
		$pro_defines = $this->getEntityPropertiesDefine();
		$pk = $this->getPrimaryKey();

		//转换set数据
		foreach($src_data as $k => $d){
			if($pro_defines[$k]['type'] == 'set' && is_array($d)){
				$src_data[$k] = join(',', $d);
			}
		}

		//移除矢量数值
		$data = array_filter($src_data, function($item){
			return is_scalar($item) || is_null($item);
		});

		//unique校验
		foreach($src_data as $field=>$_){
			$def = $pro_defines[$field];
			if($def['unique']){
				if($query_type == Query::INSERT){
					$count = $this::find("`$field`=?", $data[$field])->count();
				} else{
					$count = $this::find("`$field`=? AND `$pk` <> ?", $data[$field], $this->$pk)->count();
				}
				if($count){
					throw new BizException("{$def['alias']}：{$data[$field]}已经存在，不能重复添加");
				}
			}
		}

		//移除readonly属性
		if(!$flush_all){
			$pro_defines = array_filter($pro_defines, function($def){
				return !$def['readonly'];
			});
		}

		//清理无用数据
		$data = array_clear_fields(array_keys($pro_defines), $data);

		//插入时填充default值
		array_walk($pro_defines, function($def, $k) use (&$data, $query_type){
			if(array_key_exists('default', $def)){
				if($query_type == Query::INSERT){
					if(!isset($data[$k])){ //允许提交空字符串
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
			if(!$def['readonly'] || $flush_all){
				if($msg = $this->validateField($data[$k], $k)){
					throw new BizException($msg, null, array('field' => $k, 'value'=>$data[$k], 'row' => $data));
				}
			}
		}
		return $data;
	}

	/**
	 * 字段校验
	 * @param $value
	 * @param $field
	 * @return string
	 */
	private function validateField(&$value, $field){
		$define = $this->getPropertiesDefine($field);

		$err = '';
		$val = $value;
		$name = $define['alias'];
		if(is_callable($define['options'])){
			$define['options'] = call_user_func($define['options'], $this);
		}

		$required = $define['required'];

		//type
		if(!$err){
			switch($define['type']){
				case 'int':
					if(strlen($val) && !is_numeric($val)){
						$err = $name.'格式不正确';
					}
					break;

				case 'float':
				case 'double':
				case 'decimal':
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
		if(!$err && $define['required'] && !isset($val)){
			$err = "请输入{$name}";
		}

		//length
		if(!$err && $define['length'] && $define['type'] != 'datetime' && $define['type'] != 'date' && $define['type'] != 'time'){
			if($define['precision']){
				$int_len = strlen(substr($val, 0, strpos($val, '.')));
				$precision_len = strpos($val, '.') !== false ? strlen(substr($val, strpos($val, '.') + 1)) : 0;
				if($int_len > $define['length'] || $precision_len > $define['precision']){
					$err = "{$name}长度超出：$value";
				}
			}else{
				//mysql字符计算采用mb_strlen计算字符个数
				$db_type = $this->getDbDriver(self::DB_WRITE)->getConfig('type');
				if($define['type'] === 'string' && $db_type == 'mysql'){
					$str_len = mb_strlen($val, 'utf-8');
				}else{
					$str_len = strlen($val);
				}
				$err = $str_len > $define['length'] ? "{$name}长度超出：$value {$str_len} > {$define['length']}" : '';
			}
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
	 */
	public static function insertMany($data_list, $break_on_fail = true){
		if(count($data_list, COUNT_RECURSIVE) == count($data_list)){
			throw new Exception('Two dimension array needed');
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
		if($this->onBeforeDelete() === false){
			return false;
		}
		$pk_val = $this[$this->getPrimaryKey()];
		$this->last_operate_type = self::LAST_OP_DELETE;
		$result = static::delByPk($pk_val);
		$this->onAfterDelete();
		return $result;
	}

	/**
	 * 解析SQL查询中的条件表达式
	 * @param array $args 参数形式可为 [""],但不可为 ["", "aa"] 这种传参
	 * @param \Lite\DB\Model $obj
	 * @return string

	 */
	private static function parseConditionStatement($args, Model $obj){
		$statement = isset($args[0]) ? $args[0] : null;
		$args = array_slice($args, 1);
		if(!empty($args) && $statement){
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
	 * @param bool $flush_all 是否刷新全部数据，包含readonly数据
	 * @return bool

	 */
	public function save($flush_all = false){
		if($this->onBeforeSave() === false){
			return false;
		}

		if(!$this->getValueChangeKeys()){
			return false;
		}

		$data = $this->getValues();
		$has_pk = !empty($data[$this->getPrimaryKey()]);
		if($has_pk){
			return $this->update($flush_all);
		} else if(!empty($data)){
			return $this->insert($flush_all);
		}
		return false;
	}

	/**
	 * 获取影响条数
	 * @return int
	 */
	public function getAffectNum(){
		$type = Query::isWriteOperation($this->query) ? self::DB_WRITE : self::DB_READ;
		return $this->getDbDriver($type)->getAffectNum();
	}

	/**
	 * 获取最后操作类型
	 * @return string
	 */
	public function getLastOperateType(){
		return $this->last_operate_type;
	}

	/**
	 * 获取可读变更项
	 * @param array $original_data [field => [alias, value], ...]
	 * @return array [field => [alias, before_value, after_value], ...]
	 */
	public function getValueChangesHumanReadable(&$original_data = array()){
		$alias_map = self::getEntityFieldAliasMap();
		$changes = parent::getValueChanges($value_before_change);
		$org_values = $this->getValues();
		$ret = [];

		//组装raw数据
		foreach($org_values as $field=>$val){
			$original_data[$field] = [$alias_map[$field], $val];
		}

		//组变更数据
		foreach($changes as $field=>$val){
			$ret[$field] = [$alias_map[$field], $value_before_change[$field], $val];
		}
		return $ret;
	}

	/**
	 * 对象克隆，支持查询对象克隆
	 */
	public function __clone(){
		if(is_object($this->query)){
			$this->query = clone $this->query;
		}
	}

	/**
	 * 调用查询对象其他方法
	 * @param $method_name
	 * @param $params
	 * @return static|Query
	 */
	final public function __call($method_name, $params){
		if(method_exists($this->query, $method_name)){
			call_user_func_array(array($this->query, $method_name), $params);
			return $this;
		}

		throw new Exception("Method no exist:".$method_name);
	}

	/**
	 * 重载DAO属性设置方法，实现数据库SET提交
	 * @param $key
	 * @param $val
	 */
	public function __set($key, $val){
		if(is_array($val)){
			$define = $this->getEntityPropertiesDefine($key);
			if($define && $define['type'] == 'set'){
				$val = join(',', $val);
			}
		}
		parent::__set($key, $val);
	}

	/**
	 * 配置getter
	 * <p>
	 * 支持：'name' => array(
	 *      has_one'=>[current_field, target_class, target_field]
	 * )
	 * 支持：'name' => array(
	 *      has_many'=>[current_field, target_class, target_field]
	 * )
	 * 支持：'name' => array(
	 *     'getter' => function($k){}
	 * )
	 * 支持：'name' => array(
	 *    'setter' => function($k, $v){}
	 * )
	 * </p>
	 * @param $key
	 * @return mixed
	 */
	public function __get($key){
		$define = $this->getPropertiesDefine($key);
		$table_full = $this->getTableFullNameWithDbName();

		if($define){
			if(isset($define['getter']) && $define['getter']){
				$ret = call_user_func($define['getter'], $this);
				$this->{$key} = $ret; //avoid trigger virtual property getter again
				return $ret;
			}
			if((isset($define['has_one']) && $define['has_one']) || (isset($define['has_many']) && $define['has_many'])){
				/** @var static $target_class */
				list($current_field, $target_class, $target_field) = $define['has_one'] ?: $define['has_many'];
				$target_instance = $target_class::meta();
				$target_field = $target_field ?: $target_instance->getPrimaryKey();

				//id, variant, listing_id = listing.id
				$prefetch_list = self::$prefetch_groups[$table_full] ?: [];
				if(DBAbstract::distinctQueryState() && in_array($key, $prefetch_list)){
					$result = $target_instance->_getObjectCache($target_field, $this->{$current_field}, false, $define['has_many']);
					if(isset($result)){
						$this->{$key} = $result;
						return $result;
					}
				}
				$ret = $define['has_one'] ?
					$target_class::find("$target_field=?", $this->{$current_field})->one() :
					$target_class::find("$target_field=?", $this->{$current_field})->all();
				$this->{$key} = $ret; //avoid trigger virtual property getter again
				return $ret;
			}
		}
		$v = parent::__get($key);
		$this->{$key} = $v; //avoid trigger virtual property getter again

		/**
		 * @todo 这里由于在update/add模板共用情况下，很可能使用 $model->$field 进行直接拼接action，需要重新审视这里抛出exception是否合理
		//如果当前属性未定义，或者未从数据库中获取相应字段
		//则抛异常
		$kvs = array_keys($this->getValues());
		if(!isset($v) && !in_array($key, $kvs)){
		throw new Exception('model fields not set in query result', null, $key);
		}
		 **/
		return $v;
	}

	/**
	 * 转换当前查询对象为字符串
	 * @return string
	 */
	public function __toString(){
		return $this->query.'';
	}

	/**
	 * 打印Model调试信息
	 * @return array
	 */
	public function __debugInfo(){
		$cfg = $this->getDbConfig();
		$cfg['password'] = $cfg['password'] ? '***' : '';

		return [
			'data'              => $this->getValues(),
			'data_changed_keys' => $this->getValueChanges(),
			'query'             => $this->getQuery().'',
			'database'          => json_encode($cfg)
		];
	}
}
