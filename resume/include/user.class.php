<?php
class User extends DBORM {
	public function __construct($user_info=array()){
		parent::__construct('id', 'user', $user_info);
	}
}