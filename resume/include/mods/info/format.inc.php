<?php
return array(
	'id' => 'info',
	'title' => '基本资料',
	'multiInstance' => false,
	'placeholder' => '',
	'data' => array(
		'name' => array(
			'type' => 'string',
			'label' => '姓名',
			'placeholder' => '请输入您的姓名',
			'maxlength' => 50,
			'require' => true
		),
		'avatar' => array(
			'type' => 'image',
			'label' => '照片'
		),
		'gender' => array(
			'type' => 'radio',
			'label' => '性别',
			'options' => array(
				'male' => '男',
				'female' => '女'
			),
			'require' => true
		),
		'phone' => array(
			'type' => 'phone',
			'label' => '联系电话',
			'placeholder' => '请输入您的联系电话',
			'maxlength' => 20,
			'require' => true
		),
		'email' => array(
			'type' => 'email',
			'label' => '电子邮箱',
			'placeholder' => '请输入您的电子邮箱',
			'maxlength' => 50,
			'require' => false
		),
		'birth' => array(
			'type' => 'yearmonth',
			'label' => '出生年月',
			'require' => false
		),
		'education' => array(
			'type' => 'select',
			'label' => '最高学历',
			'options' => array(
				'0' => '小学',
				'1' => '初中',
				'2' => '高中',
				'3' => '本科',
				'4' => '中专',
				'5' => '大专',
				'6' => '硕士',
				'7' => '博士',
				'8' => '其他',
			),
			'require' => false
		),
		'hometown' => array(
			'type' => 'string',
			'label' => '户籍所在地',
			'maxlength' => 50,
			'require' => false
		),
		'ethnic' => array(
			'type' => 'string',
			'label' => '民族',
			'maxlength' => 50,
			'require' => false
		),
		'specialty' => array(
			'type' => 'string',
			'label' => '学校专业',
			'maxlength' => 50,
			'require' => false
		),
		'ce' => array(
			'type' => 'string',
			'label' => '主要证书',
			'maxlength' => 50,
			'require' => false
		),
		'target' => array(
			'type' => 'description',
			'label' => '求职意向',
			'maxlength' => 50,
			'require' => false
		),
	)
);