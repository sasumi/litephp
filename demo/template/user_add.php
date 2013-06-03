<?php include 'header.inc.php'?>
<a href="<?php echo url('user');?>">user</a>
<h2>添加用户</h2>

<?php
echo form(url('user/add'), array(
	array(
		'name' => 'username',
		'label' => '用户名称',
		'type' => 'text',
		'placeholder' => '请输入用户名称'
	),
	array(
		'name' => 'password',
		'label' => '密码',
		'type' => 'password',
		'placeholder' => '请输入用户名称'
		),
	array(
		'name' => 'description',
		'label' => '备注',
		'type' => 'textarea'
	),
));?>

<?php if(is_post()){?>
结果：<br/>
<?php debug($msg, $data);?>
<?php }?>

<?php include 'footer.inc.php'?>