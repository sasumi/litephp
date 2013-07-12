<?
include('../inc.php');

$p = array(
		'n' => 2, 
		'f' => 0,
		'reid' => 31107113996801,
		'flag' => 0
	);
$ret = $c->getReplay($p);
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
		<p class="title">getReplay函数使用（获取转播和点评消息列表）</p>
		<p>参数是一维数组：$arr['reid'=>value,'f'=>value,'t'=>value,'tid'=>value,'n'=>value,'flag'=>value]</br>
		reid：转发或者回复根结点ID</br>
		f：（根据dwTime），0：第一页，1：向下翻页，2向上翻页</br>
		t：起始时间戳，上下翻页时才有用，取第一页时忽略</br>
		tid：起始id，用于结果查询中的定位，上下翻页时才有用</br>
		n：要返回的记录的条数(1-20)</br>
		Flag:标识0 转播列表，1点评列表 2 点评与转播列表
		</p>
		<h4>获取单条微博的转发或点评列表</h4>		
		<p class="title">代码示例：</p>
		<textarea class="codearea" rows="8" cols="50">
$p = array(
		'n' => 2, 
		'f' => 0,
		'reid' => 31107113996801,
		'flag' => 0 
	);
$ret = $c->getReplay($p);
		</textarea>
		<div>
		<p>代码返回结果</p>
		<?php
			$c->printArr($ret);
		?>
		</div>
	</div>
</body>
</html>


