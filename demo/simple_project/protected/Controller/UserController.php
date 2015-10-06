<?php
namespace SimpleProject\Controller;
use Lite\Core\Controller;
use Lite\Core\Filter;
use Lite\DB\Meta\Field;
use Lite\DB\Meta\Table;

/**
* Created by PhpStorm.
 * User: sasumi
* Date: 2015/5/4
* Time: 20:02
*/

class UserController extends Controller {
	public function index(){
		$fields = array();
		$fields[] = new Field(true, array(
			'name' => 'id',
			'alias' => 'iidd',
			'type' => 'int',
			'require' => true,
			'length' => 10,
			'auto_increment' => true
		));
		$table = new Table('xxxa', null, $fields);
	}
}