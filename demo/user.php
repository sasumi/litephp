<?php
include 'config/app.php';
if(ACTION == 'index'){
	$user_list = array(
		array(
			'id' => 'asdf',
			'qq' => 'asfdasdf'
		)
	);
	include tpl('user_list.php');
}

if(ACTION == 'add'){
	if(is_post()){
		$test_rules = array(
		'id' => array(
		'require' => '必须填id'
		),
		'name' => array(
		'require' => '必须填',
		'max20'=> '5',
		'min15' => 'xx',
		),
		'em' => array('require'=>'必须填em')
		);


		try {
			filte_array($_POST, $test_rules);
			} catch(Exception $e){
			$msg = $e->getMsgList();
			debug($msg, 1);
		}
	}
	include tpl('user_add.php');
}