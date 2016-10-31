<?php
namespace {$namespace};
use Lite\Crud\ModelInterface;
use {$namespace}\table\{$table_model};

/**
 * User: Lite Scaffold
 * Date: {$generate_date}
 * Time: {$generate_time}
 */
class {$model_name} extends {$table_model} implements ModelInterface {
	public function __construct($data = array()){
		parent::__construct($data);
	}

	/**
	* get state key
	* @return string
	*/
	public function getStateKey(){
		return 'state';
	}

	/**
	 * 获取模块名称
	 * @return string
	 */
	public function getModelDesc(){
		return "{$model_desc}";
	}
}