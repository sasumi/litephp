<?php
include 'config/app.inc.php';
if(ACTION == 'login'){
	if(is_post()){
		$ori_data = posts(null, array(), false);
		try {
			$post_data = posts(null, array(
				'username' => array(
					'require' => 'asdfasfd'
				),
				'password' => array(
					'require' => 'asdf'
				)
			));
			$result = Access::init()->setLoginInfo($post_data);
			jump_to('index');
		} catch(Exception $ex){
			//$err_msg = $ex->dump();
			$err_msg = 'login fail';
		}
	}

	include tpl('login.php');
}

if(ACTION == 'logout'){
	Access::init()->logout();
	jump_to('index');
}