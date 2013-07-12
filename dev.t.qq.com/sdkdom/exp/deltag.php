<?
include('../inc.php');

if(isset($_POST['id']) ){
	$p = array(
		'i' => $_POST['id']
	);
	$ret = $c->delTag($p);
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
		<h4>删除tag</h4>
		<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
		  <ul>
			<li>tagid：<input type="text" name="id" id="id" /></li>
		  </ul>
		  <input type="submit" name="button" id="button" value="提交" />
		</form>
		<p class="title">示例代码:</p>
		<div>
			<textarea class="codearea" rows="7" cols="50">
if(isset($_POST['id']) ){
	$p = array(
		'i' => $_POST['id']
	);
	$ret = $c->delTag($p);
}
			</textarea>
			<div>
				<p>代码返回结果：</p>
				<?$c->printArr($ret);?>
			</div>
		</div>
	</div>
</body>
</html>


