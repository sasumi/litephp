<?php
namespace Lite\Core;

/**
 * 分页抽象接口
 */
interface PaginateInterface {
	/**
	 * 设置总数
	 * @param int $total
	 * @return bool
	 */
	public function setItemTotal($total);

	/**
	 * 获取分页限定信息[$start, $offset]
	 * @return mixed
	 */
	public function getLimit();
}