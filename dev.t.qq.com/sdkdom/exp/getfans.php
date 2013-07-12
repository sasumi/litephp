<?
include('../inc.php');

$p = array(
	'n' => '',
	'num' => 2,
	'start' => 0,
	'type' => 0
);

$ret = $c->getfans($p);

$p = array(
	'n' => '',
	'num' => 2,
	'start' => 0,
	'type' => 1
);

$ret1 = $c->getfans($p);

$p = array(
	'n' => 'username',
	'num' => 2,
	'start' => 0,
	'type' => 0
);

$ret2 = $c->getfans($p);

$p = array(
	'n' => 'username',
	'num' => 2,
	'start' => 0,
	'type' => 1
);

$ret3 = $c->getfans($p);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="minimum-scale=1.0, maximum-scale=1.0, initial-scale=1.0, width=device-width, user-scalable=no">
<link href="../css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<div class="main">
		<h4>获取听众列表</h4>
		<p class="title">getfans函数使用（获取听众列表/偶像列表）</p>
		<p>参数是一维数组：$arr['num'=>value,'start'=>value,'n'=>value,'type:'=>value]</br>
		num: 请求个数(1-30)</br>
		start: 起始位置</br>
		n:用户名 空表示本人</br>
		type: 0 听众 1 偶像</br>
		</p>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="8" cols="50">
$p = array(
	'n' => '',
	'num' => 2,
	'start' => 0,
	'type' => 0
);
$ret = $c->getfans($p);
			</textarea>
			<div>
				<p>代码返回值：</p>
				<?php
					$c->printArr($ret);
				?>
			</div>
		</div>
		<h4>获取我收听的列表</h4>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="8" cols="50">
$p = array(
	'n' => '',
	'num' => 2,
	'start' => 0,
	'type' => 1
);
$ret1 = $c->getfans($p);
			</textarea>
			<div>
				<p>代码返回值：</p>
				<?php
					$c->printArr($ret1);
				?>
			</div>
		</div>

		<h4>获取username的听众列表</h4>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="8" cols="50">
$p = array(
	'n' => 'username',
	'num' => 2,
	'start' => 0,
	'type' => 0
);
$ret2 = $c->getfans($p);
			</textarea>
			<div>
				<p>代码返回值：</p>
				<?php
					$c->printArr($ret);
				?>
			</div>
		</div>
		<h4>获取username收听的列表</h4>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="8" cols="50">
$p = array(
	'n' => 'username',
	'num' => 2,
	'start' => 0,
	'type' => 1
);
$ret3 = $c->getfans($p);
			</textarea>
			<div>
				<p>代码返回值：</p>
				<?php
					$c->printArr($ret1);
				?>
			</div>
		</div>

	</div>
</body>
</html>




