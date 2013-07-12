<?
include('../inc.php');

$p = array(
	'f' => 0,
	't' => 0,
	'n' => 20,
	'type' => 0	
);
$ret = $c->getMailBox($p);

$p = array(
	'f' => 0,
	't' => 0,
	'n' => 20,
	'type' => 1	
);
$ret1 = $c->getMailBox($p);
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
		<h4>获取发件箱列表</h4>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="9" cols="50">
$p = array(
	'f' => 0,
	't' => 0,
	'n' => 20,
	'type' => 0	
);
$ret = $c->getMailBox($p);
$ret3 = $c->getfans($p);
			</textarea>
			<div>
				<p>代码返回结果：</p>
				<?$c->printArr($ret);?>
			</div>
		</div>
		<h4>获取收件箱列表</h4>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="9" cols="50">
$p = array(
	'f' => 0,
	't' => 0,
	'n' => 20,
	'type' => 1	
);
$ret = $c->getMailBox($p);
$ret3 = $c->getfans($p);
			</textarea>
			<div>
				<p>代码返回结果：</p>
				<?$c->printArr($ret1);?>
			</div>
		</div>
	</div>
</body>
</html>


