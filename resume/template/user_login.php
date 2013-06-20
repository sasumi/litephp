<?php include 'header.inc.php';?>
<?php echo css('register.css');?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	//echo '<h1>'.($add_result ? 'success' : 'error').'</h1>';
}
echo form(url('user/login'), array(
	'name' => array(
		'label' => '用户名',
		'placeholder' => '请输入账号名称',
		'class' => 'txt',
		'value' => $ori_info['name']
	),

	'email' => array(
		'label' => '密码',
		'placeholder' => '密码',
		'type' => 'password',
		'class' => 'txt',
		'value' => $ori_info['password']
	),

	'submit' => array(
		'type' => 'submit',
		'value' => '登录',
		'class' => 'btn'
	)
), array(
	'title' => '用户登录',
	'class' => 'frm user-register-frm'
));
?>
<?php include 'footer.inc.php';?>
