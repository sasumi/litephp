<?php
/**
 * Created by PhpStorm.
 * User: Sasumi
 * Date: 2015/10/19
 * Time: 20:32
 */
namespace Lite\CRUD;

interface ControllerInterface {
	const OP_ALL = 'all';
	const OP_INDEX = 'index';
	const OP_UPDATE = 'update';
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
	 * @return string
	 */
	public function getModel();
}