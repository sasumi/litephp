<?php
return array(
	'id' => 'career',
	'title' => '工作经历',
	'multiInstance' => true,
	'data' => array(
		'start' => array(
			'type' => 'yearmonth',
			'label' => '开始时间',
			'placeholder' => '开始时间(年-月)',
			'require' => true
		),
		'end' => array(
			'type' => 'yearmonth',
			'label' => '结束时间',
			'placeholder' => '结束时间(年-月)',
			'require' => true
		),
		'address' => array(
			'type' => 'string',
			'label' => '工作单位',
			'placeholder' => '工作单位或地点',
			'maxlength' => 50,
			'require' => true
		),
		'post' => array(
			'type' => 'string',
			'label' => '职位',
			'placeholder' => '任职职位',
			'maxlength' => 20,
			'require' => false
		),
		'description' => array(
			'type' => 'description',
			'label' => '工作内容',
			'placeholder' => '任职期间工作内容',
			'maxlength' => 200,
			'require' => true
		)
	)
);