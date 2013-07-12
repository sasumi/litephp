<?
include('../inc.php');
$p = array(
	'n' => 'username,t',
	'type' => 0
);
$ret = $c->checkFriend($p);

$p = array(
	'n' => 'username,t',
	'type' => 1
);
$ret1 = $c->checkFriend($p);

if($_POST['foname']){
	$p = array(
		'n' => $_POST['foname'],
		'type' => 0
	);
	$key = $_POST['foname'];
	$retfoname = $c->checkFriend($p);
}

if($_POST['ifoname']){
	$p = array(
		'n' => $_POST['ifoname'],
		'type' => 1
	);
	$key = $_POST['ifoname'];
	$retifoname = $c->checkFriend($p);
}
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
		<p class="title">checkFriend函数使用（检测是否我粉丝或偶像）</p>
		<p>参数是一维数组：$arr['n'=>value,'flag'=>value]</br>
		n: 其他人的帐户名列表（最多30个,逗号分隔）</br>
		flag: 0 检测粉丝，1检测偶像</br>
		</p>
		<h4>检测是否收听我</h4>
		<form action="" method="POST">
			<input type="text" value="" name="foname" />
			<input type="submit" value="查询" />
			<label>示例程序，只能输入英文注册用户名，仅限查询一人</label>
		</form>
		<div>
			<?if(isset($retfoname)):?>
				<?if($retfoname['data'][$key]):?>
				<p>是收听我的人</p>
				<?else:?>
				<p>不是收听我的人</p>
				<?endif?>
				<?unset($retfoname);?>
			<?else:?>
				<p>提交查看结果</p>
			<?endif?>
		</div>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="6" cols="50">
$p = array(
	'n' => 'username,t',
	'type' => 0
);
$ret = $c->checkFriend($p);
			</textarea>
			<div>
				<p>代码返回值：</p>
				<?php $c->printArr($ret);?>
			</div>
		</div>
		<h4>检测是否是我收听的.</h4>
		<form action="" method="POST">
			<input type="text" value="" name="ifoname" />
			<input type="submit" value="查询" />
			<label>示例程序，只能输入英文注册用户名，仅限查询一人</label>
		</form>
		<div>
			<?if(isset($retifoname)):?>
				<?if($retifoname['data'][$key]):?>
				<p>是我收听的人</p>
				<?else:?>
				<p>不是我收听的人</p>
				<?endif?>
				<?unset($retifoname);?>
			<?else:?>
				<p>提交查看结果</p>
			<?endif?>
		</div>
		<p class="title">示例代码:</p>
			<textarea class="codearea" rows="6" cols="50">
$p = array(
	'n' => 'username,t',
	'type' => 1
);
$ret = $c->checkFriend($p);
			</textarea>
			<div>
				<p>代码返回值：</p>
				<?php $c->printArr($ret1);?>
			</div>
		</div>
</body>
</html>







