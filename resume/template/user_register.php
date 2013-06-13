<?php include 'header.inc.php';?>
<?php
if(is_post()){
	echo '<h1>'.($add_result ? 'success' : 'error').'</h1>';
}
echo form(url('user/register'), array(
	'name' => array(
		'label' => '用户名称',
		'placeholder' => '请输入账号名称',
		'value' => $ori_info['name']
	),

	'email' => array(
		'label' => '邮箱地址',
		'placeholder' => '请输入邮箱地址',
		'type' => 'email',
		'value' => $ori_info['email']
	),

	'password' => array(
		'label' => '密码',
		'placeholder' => '请输入密码',
		'value' => $ori_info['password'],
		'type' => 'password'
	),

	'password2' => array(
		'label' => '重新输入密码',
		'placeholder' => '请重新输入密码',
		'value' =>( $ori_info['password'] == $ori_info['password2'] ? $ori_info['password'] : ''),
		'type' => 'password'
	),

	'submit' => array(
		'type' => 'submit',
		'value' => '提交注册'
	)
), array(
	'title' => 'user register'
));
?>
<?php include 'footer.inc.php';?>
