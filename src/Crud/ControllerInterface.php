<?php
namespace Lite\Crud;

use Lite\DB\Model;

/**
 * CURL控制器接口
 * User: Sasumi
 * Date: 2015/10/19
 * Time: 20:32
 */
interface ControllerInterface{
	const OP_ALL = 'all';
	const OP_INDEX = 'index';
	const OP_UPDATE = 'update';
	const OP_QUICK_UPDATE = 'quick_update';

	/** @deprecated drop */
	const OP_STATE = 'state';
	const OP_DELETE = 'delete';
	const OP_INFO = 'info';

	const OP_QUICK_SEARCH = 'quick_search';

	/**
	 * get curd support operation list
	 * @return array
	 */
	public function supportCRUDList();

	/**
	 * get relate model name
	 * @return string|Model
	 */
	public function getModelClass();
}