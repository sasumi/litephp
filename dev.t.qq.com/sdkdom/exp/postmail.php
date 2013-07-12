<?
include('../inc.php');


if(isset($_POST['content']) && isset($_POST['name'])){
	$p = array(
		'c' => $_POST['content'],	
		'ip' => $_SERVER['REMOTE_ADDR'],	
		'j' => '',
		'w' => '',
		'n' => $_POST['name']
	);
	$ret = $c->postOneMail($p);
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
		<h4>发表一条私信</h4>
		<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
		  <ul>
			<li>内容：<input type="text" name="content" id="content" /></li>
			<li>收信人: <input type="text" name="name" id="name" /></li>
			<label>相互加关注才能发私信</label>
		  </ul>
		  <input type="submit" name="button" id="button" value="提交" />
		</form>
		<p class="title">示例代码:</p>
		<textarea class="codearea" rows="11" cols="50">
if(isset($_POST['content']) && isset($_POST['name'])){
	$p = array(
		'c' => $_POST['content'],	
		'ip' => $_SERVER['REMOTE_ADDR'],	
		'j' => '',
		'w' => '',
		'n' => $_POST['name']
	);
	$ret = $c->postOneMail($p);
}
		</textarea>
		<p>代码返回结果：</p>
		<?$c->printArr($ret);?>
	</div>
</body>
</html>
