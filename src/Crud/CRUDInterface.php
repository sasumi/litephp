<?php
/**
 * CRUD公共接口，由于trait不能继承接口，只能使用trait实现
 */
namespace Lite\Crud;
trait CRUDInterface {
	/**
	 * 获取绑定模型
	 * @return string Model class
	 */
	abstract public function getModelClass();
}
