<?php
@header('Content-Type:text/html;charset=utf-8'); 
session_start();
include_once('debug.php');
require_once('config.php');
require_once('oauth.php');
require_once('opent.php');

$o = new MBOpenTOAuth( MB_AKEY , MB_SKEY  );
$keys = $o->getRequestToken('null');//这里的*********************填上你的回调URL
$aurl = $o->getAuthorizeURL( $keys['oauth_token'] ,false,'');

$_SESSION['keys'] = $keys;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="minimum-scale=1.0, maximum-scale=1.0, initial-scale=1.0, width=device-width, user-scalable=no">
<link href="css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<div class="main">
		<h1>生成授权连接</h1>
		<div>
			<a href="<?php echo $aurl;?>">用OAUTH授权登录</a>
		</div>
	</div>
	<div class="tips">
		<ul>
			<li>使用MBOpenTOAuth->getRequestToken 获取授权的时候根据是否需要返回 在填写callback的时候填你的回调url或者填"null"</li>
		</ul>
	</div>
</body>
</html>
