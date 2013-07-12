<?
include('../inc.php');
$p =array(
	'id' => 71504108614120
);
$ret = $c->getOne($p);
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
		<p class="title">getOne函数使用（获取一条消息）</p>
		<p>参数是一维数组：$arr['id'=>value]</br>
		id: 微博ID</br>
		</p>
		<h4>获取一条微博</h4>
		<p class="title">代码示例：</p>
		<textarea class="codearea" rows="5" cols="50">
$p =array(
	'id' => 71504108614183
);
$ret = $c->getOne($p);
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





