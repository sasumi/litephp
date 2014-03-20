<?php
class Controller_Index extends Controller {
	public function index(){
		$user = Model_User::find('id=? AND name=?', "'d/\\'", 'a')->one();
		dump($user->name);
	}
}
