<?php
$PAGE_HEAD .= css('register.css');
include 'inc/header.inc.php';
?>
<form action="<?php echo url('user/register')?>" data-trans='async' id="register-form" class="frm user-register-frm" method="POST" onresponse="response">
	<div class="error">
	</div>
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
					最少6位
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
				<input type="submit" value="立即注册" class="btn btn-strong" />
			</dd>
		</dl>

	</fieldset>
</form>
<script type="text/javascript">
var response = function(msg, code, data){
	YSL.showTip(msg, code);
	if(code == 'succ'){
		setTimeout(function(){
			location.href = R.url('user');
		}, 1000);
	}
};
YSL.use('widget.Validator', function(Y, Val){
	var validator = new Val({
		form: '#register-form',
		rules: {
			'name': {
				require: '请输入注册用户名',
				min6:'用户名至少6位'
			},
			'email': {
				require: '请输入电子邮箱',
				email: '请输入正确格式的邮箱'
			},
			'password': {
				reuiqre: '请输入您的密码',
				min6: '密码至少6位'
			},
			'repasword': {
				reuiqre: '请再次输入密码',
				'function': function(val,element){
					if (Y.dom.one("#password").getValue() != val){
						return "两次密码不一致";
					}
				}
			}
		},

		onCheckPass: function(item){
			var pn = Y.dom.one(item.parentNode);
			var span = pn.one('span.msg') || pn.create('span', 2).addClass('msg');
			span.addClass('pass').removeClass('error').setHtml('ok');
			if(item.type == 'text' || item.type == 'password'){
				Y.dom.one(item).removeClass('txt-error');
			}
		},
		onError: function(item, errs){
			var err = errs[0];
			var pn = Y.dom.one(item.parentNode);
			var span = pn.one('span.msg') || pn.create('span', 2).addClass('msg');
			span.addClass('error').removeClass('pass').setHtml(err);
			if(item.type == 'text' || item.type == 'password'){
				Y.dom.one(item).addClass('txt-error');
			}
		},
		resetError: function(form){
			Y.dom.one(form).all('span.msg').each(function(span){
				span.removeClass('pass').removeClass('error');
				span.setHtml('')
			});
			Y.dom.one(form).all('input[type=text].txt').removeClass('txt-error');
			Y.dom.one(form).all('input[type=password].txt').removeClass('txt-error');
		}
	});
});
</script>
<?php include 'inc/footer.inc.php';?>
