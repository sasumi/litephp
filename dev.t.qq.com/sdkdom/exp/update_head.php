<?
include('../inc.php');

if(isset($_FILES['pic'])){
	$p = array(
		'pic' => array($_FILES['pic']['type'],$_FILES['pic']['name'],file_get_contents($_FILES['pic']['tmp_name']))
	);
	$ret = $c->updateUserHead($p);
}
$my = $c->getUserInfo();
if($my['ret'] == 0){
	$mys = $my['data'];
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
		<p class="title">updateUserHead函数使用（更新用户头像）</p>
		<p>参数是一维数组：$arr['pic'=>value]</br>
		Pic:文件域表单名 本字段不能放入到签名串中</br>
		</p>
		<h4>更新个人资料</h4>
		<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
		  <ul>
			<li>
				头像: 
				<input type="file" name="pic" />
				<div><img src="<?php echo $mys['head']?>/120" /></div>
			</li>
		  </ul>
  		  <input type="submit" name="button" id="button" value="提交" />
		</form>
		<p class="title">示例代码:</p>
		<textarea class="codearea" rows="5" cols="110">
$p = array(
	'pic' => array($_FILES['pic']['type'],$_FILES['pic']['name'],file_get_contents($_FILES['pic']['tmp_name']))
);
$ret = $c->updateUserHead($p);
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




