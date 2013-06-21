<?php include 'header.inc.php';?>
<?php echo css('register.css');?>
<form action="<?php echo url('user/register')?>" data-trans='async' class="frm user-register-frm" method="POST">
	<fieldset>
		<legend>用户注册</legend>
		<dl>
			<dt><label for="name">用户名</label></dt>
			<dd>
				<input type="text" name="name" id="name" class="txt" />
				<p class="field-desc">用户名称只能是字母开始，包含数字和下划线的单词</p>
			</dd>
		</dl>
		<dl>
			<dt><label for="email">邮箱</label></dt>
			<dd>
				<input type="text" name="email" id="email" class="txt" />
				<p class="field-desc">该邮箱仅做找回密码使用</p>
			</dd>
		</dl>
		<dl>
			<dt><label for="password">密码</label></dt>
			<dd>
				<input type="password" name="password" id="password" class="txt" />
				<p class="field-desc">
					密码强度：高
				</p>
			</dd>
		</dl>
		<dl>
			<dt><label for="re-password">重复密码</label></dt>
			<dd>
				<input type="password" name="repasword" id="re-password" class="txt" />
			</dd>
		</dl>
		<dl>
			<dd>
				<input type="submit" value="立即注册" class="btn b-btn" />
			</dd>
		</dl>

	</fieldset>
</form>
<?php include 'footer.inc.php';?>
