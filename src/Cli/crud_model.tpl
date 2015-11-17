<?php
namespace {$namespace};
use Lite\CRUD\ModelInterface;
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
	* 获取模型状态key
	* @return string
	*/
	public function getStateKey(){
		return 'state';
	}
}