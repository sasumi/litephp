<?php include 'header.inc.php';?>
<?php echo css('register.css');?>
<?php
if(is_post()){
	echo '<h1>'.($add_result ? 'success' : 'error').'</h1>';
}
echo form(url('user/register'), array(
	'name' => array(
		'label' => '用户名',
		'placeholder' => '请输入账号名称',
		'class' => 'txt',
		'value' => $ori_info['name']
	),

	'email' => array(
		'label' => '邮箱址',
		'placeholder' => '请输入邮箱地址',
		'type' => 'email',
		'class' => 'txt',
		'value' => $ori_info['email']
	),

	'password' => array(
		'label' => '密码',
		'placeholder' => '请输入密码',
		'value' => $ori_info['password'],
		'class' => 'txt',
		'type' => 'password'
	),

	'password2' => array(
		'label' => '重输密码',
		'placeholder' => '请重新输入密码',
		'class' => 'txt',
		'value' =>( $ori_info['password'] == $ori_info['password2'] ? $ori_info['password'] : ''),
		'type' => 'password'
	),

	'submit' => array(
		'type' => 'submit',
		'value' => '立即注册',
		'class' => 'btn'
	)
), array(
	'title' => '用户注册',
	'class' => 'frm user-register-frm'
));
?>
<?php include 'footer.inc.php';?>
