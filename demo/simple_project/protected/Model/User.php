<?php
namespace SimpleProject\Model;
use Lite\DB\Meta\Table;
use Lite\DB\Model;

/**
 * Class User
 * @package SimpleProject\Model
 * @property mixed $name
 * @property mixed $age
 * @property mixed $address
 * @property string $description
 */
class User extends Model {
	public function getProperties(){
		return new Table();
	}

	/**
	 * 当前表名接口
	 * @return string
	 */
	public function getTableName() {
		return 'user';
	}

	/**
	 * 获取当前设置主键
	 * @return string
	 */
	public function getPrimaryKey() {
		return 'id';
	}
}