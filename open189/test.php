<?php
define('APP_ID', '355098250000031044');
define('APP_SEC', '62a2b6ebfe387daf5d4d806c549d2a09');

include 'common.php';
include 'passport.php';
include 'user.php';

try {
	$result = open189_auto_login(array(
		'app_id' => APP_ID,
		'app_secret' => APP_SEC
	));

	if($result){
		echo '<h1>success</h1>';
		$token = open189_get_access_token();
		$d = open189_get_user_phone_and_province(array('app_id'=>APP_ID, 'access_token'=>$token));
		dump($d, 1);
	}
} catch(Exception $e){
	echo '<pre>';
	dump($e);
}
