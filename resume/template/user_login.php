<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<title>user login</title>
	<?php
		echo css('global.css');
		echo css('login-dialog.css');
		echo js(YSL_URL);
	?>
	<style>
	#err-msg {color:red; font-size:12px; }
	</style>
</head>
<body>
	<form action="<?php echo url('user/login');?>" method="POST" class="frm user-login-frm" data-trans='async' onresponse="response">
		<fieldset>
			<dl>
				<dt><label for="name">用户名</label></dt>
				<dd><input type="text" name="name" id="name" class="txt"></dd>
			</dl>
			<dl>
				<dt><label for="password">密码</label></dt>
				<dd><input type="password" name="password" id="password" class="txt"></dd>
			</dl>
			<dl id="err-msg" style="display:none">
				<dt></dt>
				<dd id="err-msg-con">您输入的账号或密码有误，请检查后重新输入</dd>
			</dl>
			<dl>
				<dt></dt>
				<dd>
					<input type="submit" value="用户登录" class="btn btn-strong">
					<span style="float:right; font-size:12px; margin-top:10px;">
						<a href="<?php echo url('user/register');?>" target="_top">用户注册</a>
						<a href="<?php echo url('user/findpassword');?>" target="_top">忘记密码</a>
					</span>
				</dd>
			</dl>
		</fieldset>
	</form>
	<?php echo js('global.js');?>
	<script>
		var response = function(msg, type, data){
			if(type == 'succ'){
				YSL.showTip(msg, type);
				setTimeout(function(){
					YSL.use('widget.Popup', function(Y, Pop){
						Pop.getIO('loginSucc', function(fn){fn(data);});
						Pop.closeCurrentPopup();
					});
				}, 2000);
			} else {
				YSL.dom.one('#err-msg').show();
				YSL.dom.one('#err-msg-con').setHtml(msg);
			}
		};
	</script>
</body>
</html>
