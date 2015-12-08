<?php
namespace {$namespace};

/**
 * User: Lite Scaffold
 */
use Lite\DB\Model as Model;
use Lite\DB\Query as Query;

/**
 * Class {$class_name}
{$class_comment}
 * @method static {$class_name}|Query meta()
 * @method boolean onBeforeSave()
 * @method boolean onBeforeUpdate()
 * @method boolean onBeforeInsert()
 * @method static string getDbTablePrefix($type = {$class_name}::DB_READ)
 * @method static {$class_name}|Query setQuery($query = null, $db_config = array())
 * @method Query getQuery()
 * @method static \Exception transaction($handler)
 * @method \PDOStatement execute()
 * @method static {$class_name}|Query find($statement = '', $var = null, ...$var2)
 * @method static {$class_name}|Query order($statement='')
 * @method static {$class_name}|Query|bool create($data)
 * @method static {$class_name}|Query|array findOneByPk($val, $as_array = false)
 * @method static {$class_name}|Query|array findByPks(array $pks, $as_array = false)
 * @method static bool delByPk($val)
 * @method static bool updateByPk($val, $data)
 * @method static bool updateWhere(array $data, $limit = 1, $statement, ...$var2)
 * @method static bool deleteWhere($limit = 1, $statement, ...$var2)
 * @method array all($as_array = false)
 * @method {$class_name}|Query|array|null one($as_array = false)
 * @method mixed|null ceil($key)
 * @method bool chunk($size = 1, $handler)
 * @method int count()
 * @method array|null paginate($page = null, $as_array = false)
 * @method number update()
 * @method string|bool insert()
 * @method static array|bool insertMany($data_list, $break_on_fail = true)
 * @method bool delete()
 * @method bool save()
 * @method setPropertiesDefine(array $defines)
 * @method array getPropertiesDefine($key=null)
 * @method array getEntityPropertiesDefine($key=null)
 * @method array getAllPropertiesKey()
 * @method string getPrimaryKey()
 */
abstract class {$class_name} extends Model {
	public function __construct($data=array()){
		$this->setPropertiesDefine(array({$properties_defines}
		));
		parent::__construct($data);
	}

	/**
	 * current model table name
	 * @return string
	 */
	public function getTableName() {
		return '{$table_name}';
	}

	/**
	* 获取模块名称
	* @return string
	*/
	public function getModelDesc(){
		return '{$model_desc}';
	}
}