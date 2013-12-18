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
			$exists = DBM::instance('user')->find('name=?', $data['name'])->one();
			if($exists){
				HtmlExt::showIframeMsg('用户名已存在，请重新输入');
				die;
			}
			$data['salt'] = md5($data['password'].APP_SALT_KEY);
			$add_result = DBM::instance('user', $data)->create();
			if($add_result){
				Access::init()->setLoginInfo($data);
				HtmlExt::showIframeMsg('用户注册成功', 'succ');
			} else {
				HtmlExt::showIframeMsg('系统正忙，请稍后重试', 'err');
			}
		} catch(FilteException $ex){
			$err_msg = $ex->getOneMsg();
			HtmlExt::showIframeMsg($err_msg, 'err');
		}
	}
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
		} catch(FilteException $ex){
			HtmlExt::showIframeMsg($ex->getOneMsg(), 'err');
		}

		$user = DBM::instance('user')->find('name=?',$data['name'])->one();
		if($user && $user->password == $data['password']){
			Access::init()->setLoginInfo($user);
			HtmlExt::showIframeMsg('登录成功', 'succ');
		} else {
			HtmlExt::showIframeMsg('登录失败，用户名或密码不正确');
		}
		die;
	}
}

else if(ACTION == 'changeAvatar'){
	if(is_post()){
		$name = $login_user->id.'_'.rand().'.jpg';
		$config = array(
			'upload_dir' => UPLOAD_DIR,
			'file_type'=>'png,gif,jpg,jpeg',
			'file_name' => $name,
			'max_size' => 1024*1024*20,
			'max_file_count'=>1
		);
		$up = new Uploader($config);
		$rst = $up->upload($err);
		if(!empty($rst)){
			$user = DBM::instance('user')->find('id=?', $login_user->id)->one();
			$user->album = $name;
			$user->save();
			HtmlExt::showIframeMsg('提交成功', 'succ', UPLOAD_URL.$name);
		}
		HtmlExt::showIframeMsg('上传失败，请稍候重试', 'err', json_encode($err));
	}
	$org_src = '';
	$this->_view['org_src'] = $org_src;
}

else if(ACTION == 'changepsw'){
	if(is_post()){
		try {
			$data = posts(null, array(
				'password' => array(
					'require' => '请输入旧密码'
				),
				'new' => array(
					'require' => '请输入新密码',
					'min6' => '新密码长度至少需要6位'
				),
				'rpnew' => array(
					'require' => '请重复新密码'
				),
			), false);

			if($data['password'] != $login_user->password){
				HtmlExt::showIframeMsg("您输入的旧密码不正确，请重新输入", 'err');
				die;
			}
			$login_user->password = $data['new'];
			$result = $login_user->save();
			if($result){
				Access::init()->logout();
				HtmlExt::showIframeMsg("密码修改成功，请重新登录系统", 'succ');
			} else {
				HtmlExt::showIframeMsg("系统正忙，请稍后重试", 'err');
			}
		} catch(FilteException $ex){
			HtmlExt::showIframeMsg($ex->getOneMsg(), 'err');
		}
		die;
	}
}

else if(ACTION == 'myresume'){
	$resume_list = DBM::instance('resume')->find('user_id=?', $login_user->id)->limit(20)->all();
}

else if(ACTION == 'info'){
	$login_user->name = "asfdasfd";
}

else if(ACTION == 'index'){
	jump_to('user/myresume');
}

else if(ACTION == 'list'){
}
include tpl();