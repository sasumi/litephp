<?php
/**
 * Index 列表操作
 */
namespace Lite\Crud;
trait CRUDInterface {
	/**
	 * 获取绑定模型
	 * @return string Model class
	 */
	abstract public function getModelClass();
}
