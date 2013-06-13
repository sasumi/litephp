<?php
include 'config/app.inc.php';

//注册
if(ACTION == 'register'){
	$ori_info = array(
		'name' => rand_string(6),
		'email' => rand_string(4).'@qq.com',
		'password' => '12345',
		'password2' => '12345'
	);

	if(is_post()){
		$ori_info = posts(null, array(), false);
		try {
			$data = posts(null, array(
				'name' => array(
					'require' => '请输入用户名',
					'id' => '用户名称必须是字母开始，包含数字、下划线的单词',
					'min5' => '用户名必须大于5个字母',
					'max40' => '用户名不长于40个字母'
				),

				'password' => array(
					'require' => '请输入密码',
					'min4' => '密码长度不得小于4个字母'
				),

				'password2' => array(
					'require' => '请输入重复密码',
					'same' => function($value, $ori_info=null){
						$ori = posts('password', array(), false);
						return $value ==  $ori ? '' : 'no same';
					}
				)
			));
			unset($data['password2']);

			$user = new User($data);
			$add_result = $user->save();
			if($add_result){
				Access::init()->setLoginInfo($data);
			}
		} catch(FilteException $ex){
			$err_msg = $ex->getOneMsg();
		}
	}
	include tpl();
}

//退出登录
else if(ACTION == 'logout'){
	Access::init()->logout();
	jump_to('index');
}

//登录
else if(ACTION == 'login'){
	if(is_post()){
		$ori_info = posts(null, array(), false);
		try {
			$data = posts(null, array(
				'name' => array(
					'require' => 'please input name'
				),
				'password' => array(
					'require' => 'please input password'
				)
			));
		} catch(Exception $ex){
			dump($ex);
		}
		$user = (new User())->findByName($data['name']);
		if($user && $user['password'] == md5($data['password'])){
			Access::init()->login($user);
			jump_to('index');
		}
	}
	include tpl('index.php');
}


else if(ACTION == 'index'){
	$page = Pager::instance();
	$page->setPageSize(3);
	$data = (new User())->getByPage(null, $page);
	include tpl('user_list.php');
}