<?php
namespace {$namespace};
use {$model_namespace}\{$model_name};
use Lite\Crud\ControllerInterface;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2014/11/5
 * Time: 12:25
 */

class {$controller_name} extends {$extend_controller} implements ControllerInterface {
	public function supportCRUDList(){
		return array(
			self::OP_INDEX=>array(),
			self::OP_UPDATE=>array(),
			self::OP_STATE=>array(),
			self::OP_INFO=>array()
		);
	}

	/**
	 * get relate model name
	 * @return string
	 */
	public function getModelClass(){
		return {$model_name}::class;
	}
}