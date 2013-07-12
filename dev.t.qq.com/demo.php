<?php
session_start();
header('Conent-Type:text/html, charset=utf-8');
include_once 'interface.php';

//微博DEBUG模式
define('IN_DEBUG', 1);

//腾讯微博配置
$qq_config = array(
	'WB_TYPE'=>'qq',
	'WB_AKEY'=>'ecf881e6530548ecb65f21d55a046d56',
	'WB_SKEY'=>'42e06365d8291b2d92e88ff17015639d'
	//'WB_OFFCIAL_PS' => 'qq800010000',				//该参数已废除
	//'WB_CALLBACK'=>'http://hd.im.ct10000.com/lib/twitter/demo.php' //该参数已废除
);

$msg =array(
	'c' => rand(1,1000).'测试',
	'ip' => $_SERVER['REMOTE_ADDR'], 
	'j' => '',
	'w' => '',
	'type' => 2,
	'r' => '103034085839062'
);

//初始化微博
$wb = Twitter_Interface::init($qq_config);

//微博自动连接（内涵验证、验证授权过程、登录态验证）
$wb->autoConnect();

//命令。这里需要对不同微博api执行不同命令
$ret1 = $wb->postOne($msg);

$msg['c'] .= rand(1,1000);
$ret2 = $wb->postOne($msg);

$msg['c'] .= rand(1, 1000);
$ret3 = $wb->postOne($msg);

echo '<PRE>';
var_dump($ret1, $ret2, $ret3);
die;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title></title>
</head>
<body>
	<form action="?" method="POST">
		<textarea name="msg" id="" cols="30" rows="10">发送到微博的消息</textarea>
		<input type="submit" value="submit"/>
	</form>
</body>
</html>