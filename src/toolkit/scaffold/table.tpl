<?php
namespace {$namespace};

/**
 * User: Lite Scaffold
 */
use Lite\DB\Model as Model;

/**
 * Class {$class_name}
{$class_comment}
 */
abstract class {$class_name} extends Model {
{$class_const_string}	public function __construct($data=array()){
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
	* get database config
	* @return array
	*/
	protected function getDbConfig(){
		return include dirname(__DIR__).'/db.inc.php';
	}

	/**
	* 获取模块名称
	* @return string
	*/
	public function getModelDesc(){
		return '{$model_desc}';
	}
}