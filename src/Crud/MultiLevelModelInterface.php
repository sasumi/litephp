<?php
namespace Lite\Crud;

/**
 * 多级分类CRUD接口
 */
interface MultiLevelModelInterface {
	/**
	 * 上级ID标识
	 * @return mixed
	 */
	public function getParentIdField();

	/**
	 * 用于显示的字段名
	 * @return mixed
	 */
	public function getDisplayField();

	/**
	 * 最大限制层级深度（缺省|0 为不限制）
	 * @return mixed
	 */
	public function getMaxLevelCount();
}