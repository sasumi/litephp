<?php echo form(url('test/add'), array(
	'name' => array(
		'label' => 'User Name',
		'placeholder' => 'please input your name',
		'style' => 'background:url("http://www.baidu.com/a.gif") no-repeat; font-family:"Microsoft Yahei"'
	),

	'location' => array(
		'label' => 'address',
		'type' => 'select',
		'options' => array(
			'1' => 'RPC China',
			'2' => 'TW',
			'3' => 'HK',
			'0' => 'Others'
		)
	),

	'gender' => array(
		'type' => 'radio',
		'options' => array(
			'1' => 'male',
			'2' => 'female',
			'3' => 'Keep As Secrect'
		)
	),

	'gender2' => array(
		'label' => 'default gender',
		'value' => 2,
		'type' => 'radio',
		'options' => array(
			'1' => 'male',
			'2' => 'female',
			'3' => 'Keep As Secrect'
		)
	),

	'pic' => array(
		'label' => 'Thumb Picture',
		'type' => 'file'
	),

	'intro' => array(
		'type' => 'textarea',
		'placeholder' => 'acc',
		'style' => 'color:green; border:5px solid green; font-size:16px',
		'cols' => 50,
		'rows' => 3
	),

	'agree' => array(
		'type' => 'checkbox',
		'value' => 'check'
	)
));
?>