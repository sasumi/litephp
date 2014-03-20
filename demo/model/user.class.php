<?php
class Model_User extends DB_Model {
	public $group_name;

	protected function __onSetAttribute($key, $val){
		dump($key, $val);
		if($key == 'user_group_id'){
			$group = Model_UserGroup::find('id=?',$val)->one();
			$this->group_name = $group->name;
			return false;
		}
	}

	protected function getTableName(){
		return "user";
	}

	protected function getPrimaryKey(){
		return "id";
	}
}