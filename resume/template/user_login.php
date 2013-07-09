<?php
$PAGE_HEAD .= css('login.css');
include 'header.inc.php';
?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$_SESSION['name'] = $_POST['name'];
	header('Location:'.url('user/myresume'));
}
?>

<form action="" class="frm user-login-frm">
	<h2>用户登录</h2>
	<fieldset>
		<dl>
			<dt><label for="">用户名</label></dt>
			<dd><input type="text" name="" id="" class="txt"></dd>
		</dl>
		<dl>
			<dt><label for="">密码</label></dt>
			<dd><input type="password" name="" id="" class="txt"></dd>
		</dl>
		<dl>
			<dt></dt>
			<dd>
				<input type="submit" value="登 录" class="btn b-btn">
			</dd>
		</dl>

	</fieldset>
</form>

<?php include 'footer.inc.php';?>
