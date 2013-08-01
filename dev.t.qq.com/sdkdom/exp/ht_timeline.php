<?
include('../inc.php');

$p =array(
	'f' => 0,
	'n' => 2,		
	't' => 'hello',
	'p' => '',
);
$ret = $c->getTopic($p);
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
		<p class="title">getTopic函数使用（获取话题下的消息）</p>
		<p>参数是一维数组：$arr['t'=>value,'f'=>value,'p'=>value,'n'=>value]</br>
		t: 话题名字</br>
		f 分页标识（PageFlag = 1表示向后（下一页）查找；PageFlag = 2表示向前（上一页）查找；PageFlag = 3表示跳到最后一页  PageFlag = 4表示跳到最前一页）</br>
		p: 分页标识（第一页 填空，继续翻页：根据返回的 pageinfo决定）</br>
		n: 每次请求记录的条数（1-20条）
		</p>
		<h4>话题时间线</h4>
		<p class="title">代码示例：</p>
		<textarea class="codearea" rows="8" cols="50">
$p =array(
	'f' => 0,
	'n' => 2,		
	't' => 'hello',
	'p' => '',
);
$ret = $c->getTopic($p);
		</textarea>
		<div>
			<p>代码返回值：</p>
			<?php
				$c->printArr($ret);
			?>
		</div>
	</div>
</body>
</html>


