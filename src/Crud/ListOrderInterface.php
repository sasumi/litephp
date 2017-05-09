<?php
namespace Lite\Crud;

/**
 * 排序模型接口
 */
interface ListOrderInterface {
	/**
	 * 排序字段
	 * @return string
	 */
	public function getListOrderField();
}