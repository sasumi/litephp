<?
include('../inc.php');

$p = array(
	'num' => 2,
	's' => 0
);

$ret = $c->getBlack($p);
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
		<p class="title">getBlack函数使用（获取黑名单列表）</p>
		<p>参数是一维数组：$arr['num'=>value,'s'=>value]</br>
		num reqnum: 请求个数(1-30)</br>
		s Startindex: 起始位置（第一页填0，继续向下翻页：填：【reqnum*（page-1）】）</br>
		</p>
		<h4>黑名单列表</h4>	
		<p class="title">示例代码:</p>
		<textarea class="codearea" rows="6" cols="50">
$p = array(
	'num' => 2,
	'startindex' => 0
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
</body>
</html>





