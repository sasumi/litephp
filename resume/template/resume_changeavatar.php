<!DOCTYPE html>
<html class="dialog">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title>append catalog</title>
	<?php echo css('reset.css', 'global.css');?>
	<style>
		.img {width:128px; height:128px; overflow: hidden; float:left;}
		.op {float:right;}
		.op .file {width:235px;}
	</style>
</head>
<body  style="height:150px; overflow:hidden;">
	<form action="<?php echo url('resume/changeAvatar')?>" method="POST" class="frm">
		<fieldset>
			<div class="img"><img src="<?php echo $org_src;?>" alt=""></div>
			<div class="op">
				<input type="file" name="file" class="file" id=""><br/><br/>
				<input type="submit" class="btn" value="保存">
			</div>
		</fieldset>
	</form>

</body>
</html>
