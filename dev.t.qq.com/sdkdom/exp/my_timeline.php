<?
include('../inc.php');

$p =array(
	'f' => 0,
	'n' => 2,		
	't' => 0,
	'l' => '',
	'type' => 0
);
$ret = $c->getMyTweet($p);

$p =array(
	'f' => 0,
	'n' => 2,		
	't' => 0,
	'l' => '',
	'type' => 1
);
$ret1 = $c->getMyTweet($p);
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
		<p class="title">getMyTweet函数使用（获取关于我的消息）</p>
		<p>参数是一维数组：$arr['f'=>value,'n'=>value,'t'=>value,'l'=>value,'type'=>value]</br>
		f 分页标识（0：第一页，1：向下翻页，2向上翻页）</br>
		t: 本页起始时间（第一页 0，继续：根据返回记录时间决定）</br>
		n: 每次请求记录的条数（1-20条）</br>
		l: 当前页最后一条记录，用于精确翻页用</br>
		type : 0 提及我的, other在其他人微博我发表的
		</p>
		<h4>用户提及时间线</h4>
		<p class="title">代码示例：</p>
		<textarea class="codearea" rows="9" cols="50">
$p =array(
	'f' => 0,
	'n' => 2,		
	't' => 0,
	'l' => '',
	'type' => 0
);
$ret = $c->getMyTweet($p);
		</textarea>
		<div>
			<p>代码返回值：</p>
			<?php
				$c->printArr($ret);
			?>
		</div>
		<h4>我发表的时间线</h4>
		<p class="title">代码示例：</p>
		<textarea class="codearea" rows="9" cols="50">
$p =array(
	'f' => 0,
	'n' => 2,		
	't' => 0,
	'l' => '',
	'type' => 1
);
$ret = $c->getMyTweet($p);
		</textarea>
		<div>
			<p>代码返回值：</p>
			<?php
				$c->printArr($ret1);
			?>
		</div>
	</div>
</body>
</html>


