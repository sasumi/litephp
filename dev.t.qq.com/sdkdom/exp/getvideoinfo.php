<?
include('../inc.php');

$p = array(
		'u' => 'http://v.youku.com/v_playlist/f6098954o1p0.html'
	);
$ret = $c->getVideo($p);
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
		<p class="title">getVideo函数使用（获取视频信息）</p>
		<p>参数是一维数组：$arr['u'=>value]</br>
		u: 视频url
		</p>
		<h4>获取视频信息</h4>
		<p class="title">代码示例：</p>
		<textarea class="codearea" rows="8" cols="50">
$p = array(
		'u' => 'http://v.youku.com/v_playlist/f6098954o1p0.html'
	);
$ret = $c->getVideo($p);
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

