<?php
return array(
	'id' => 'skill',
	'title' => '工作技能',
	'multiInstance' => true,
	'data' => array(
		'title' => array(
			'type' => 'string',
			'label' => '标题',
			'placeholder' => '技能标题',
			'maxlength' => 50,
			'require' => false
		),
		'description' => array(
			'type' => 'description',
			'label' => '技能描述',
			'placeholder' => '技能描述',
			'maxlength' => 200,
			'require' => true
		)
	)
);