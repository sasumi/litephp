<?php
/**
 * Created by PhpStorm.
 * User: Sasumi
 * Date: 2015/10/19
 * Time: 20:32
 */
namespace Lite\CRUD;

interface ControllerInterface {
	const OP_ALL = 0x001;
	const OP_INDEX = 0x002;
	const OP_UPDATE = 0x003;
	const OP_STATE = 0x004;
	const OP_DELETE = 0x005;
	const OP_INFO = 0x006;

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