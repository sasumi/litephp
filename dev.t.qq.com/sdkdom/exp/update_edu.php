<?
include('../inc.php');

if(isset($_POST['year'])){
	$p = array(
		'feildid' => (int) $_POST['id'],
		'year' => (int) $_POST['year'],
		'schoolid' => (int) $_POST['schoolid'],
		'departmentid' => (int) $_POST['departmentid'],
		'level' => (int) $_POST['level'] 
	);
	$ret = $c->updateMyEdu($p);
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
		<p class="title">updateMyEdu函数使用（更新用户教育信息）</p>
		<p>参数是一维数组：$arr['feildid'=>value,'year'=>value,'schoolid'=>value,'departmentid'=>value,'level'=>value]</br>
		Feildid: 教育信息记录ID （添加feildid=1.修改填返回的ID,删除下面四个参数为空）</br>
		Year: 入学年限</br>
		Schoolid:学校ID</br>
		Departmentid: 院系ID</br>
		Level: 学历
		</p>
		<h4>更新个人资料</h4>
		<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
		  <ul>
			<li>id: <input type="text" name="id" value="<?php echo $mys['edu'][0]['id'];?>" /></li>
			<li>入学年份: <input type="text" name="year" value="<?php echo $mys['edu'][0]['year'];?>" /></li>
			<li>学校id: <input type="text" name="schoolid" value="<?php echo $mys['edu'][0]['schoolid'];?>" /></li>
			<li>系id: <input type="text" name="departmentid" value="<?php echo $mys['edu'][0]['departmentid'];?>" /></li>
			<li>学历: <input type="text" name="level" value="<?php echo $mys['edu'][0]['level'];?>" /></li>
		  </ul>
  		  <input type="submit" name="button" id="button" value="提交" />
		</form>
		<p class="title">示例代码:</p>
		<textarea class="codearea" rows="13" cols="50">
$p = array(
	'nick' => $_POST['nick'],
	'sex' => (int) $_POST['sex'],
	'year' => (int) $_POST['year'],
	'month' => (int) $_POST['month'],
	'day' => (int) $_POST['day'],
	'countrycode' => 0,
	'provincecode' => 0,
	'citycode' => 0,
	'introduction' => $_POST['introduction']
);
$ret = $c->updateMyinfo($p);
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




