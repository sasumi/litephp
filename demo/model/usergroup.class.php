<?php
class Model_UserGroup extends DB_Model {
	protected function getTableName(){
		return "user_group";
	}

	protected function getPrimaryKey(){
		return "id";
	}
}

?>