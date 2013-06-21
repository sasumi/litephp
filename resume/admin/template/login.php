<?php include 'header.inc.php';?>
<?php
echo $err_msg ? "<p class=\"error_message\">$err_msg</p>" : '';
echo form(url('access/login'), array(
		'username' => array(
			'label' => 'Name',
			'value' => $ori_data['username'],
			'placeholder' => 'login name',
			'class' => 'txt'
		),
		'password' => array(
			'type' => 'password',
			'label' => 'Password',
			'value' => $ori_data['password'],
			'placeholder' => 'login password',
			'class' => 'txt'
		),
		'submit' => array(
			'class' => 'btn',
			'value' => 'login',
			'type' => 'submit'
		)
	),array(
		'class' => 'frm',
		'title' => 'user login'
	));
?>
<?php include 'footer.inc.php';?>