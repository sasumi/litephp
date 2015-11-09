<?php
namespace Lite\CRUD;

/**
 * Created by PhpStorm.
 * User: Sasumi
 * Date: 2015/10/19
 * Time: 20:47
 */
interface ModelInterface {
	/**
	 * 获取模块名称
	 * @return string
	 */
	public function getModelDesc();

	/**
	 * 获取模型状态key
	 * @return string
	 */
	public function getStateKey();
}