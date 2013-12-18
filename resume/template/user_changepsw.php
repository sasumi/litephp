<?php
$PAGE_CLASS .= 'page-user-info';
$PAGE_HEAD .= css('user.css');
include 'inc/header.inc.php';
include 'inc/usernav.inc.php';
?>
<div class="right-col">
	<h2 class="cap">修改密码</h2>
	<form action="<?php echo url('user/changepsw');?>" method="POST" id="changepsw-frm" class="frm user-common-frm" data-trans='async' onresponse="response">
		<fieldset>
			<dl>
				<dt>
					<label for="new-password">新密码</label>
				</dt>
				<dd>
					<input type="password" name="new" id="new-password" value="" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="rp-new-password">重复新密码</label>
				</dt>
				<dd>
					<input type="password" name="rpnew" id="rp-new-password" value="" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="password">旧密码</label>
				</dt>
				<dd>
					<input type="password" name="password" id="password" value="" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
				</dt>
				<dd>
					<input type="submit" value="保存修改" class="btn btn-strong"/>
				</dd>
			</dl>
		</fieldset>
	</form>
</div>
<script>
var response = function(msg, type){
	YSL.showTip(msg, type);
	if(type == 'succ'){
		setTimeout(function() {
			location.href = R.url('user/login');
		}, 2000);
	}
};

YSL.use('widget.Validator', function(Y, Val){
	var validator = new Val({
		form: '#changepsw-frm',
		rules: {
			'new': {
				require: '请输入新密码',
				min6:'新密码至少6位'
			},
			'rpnew': {
				reuiqre: '请再次输入密码',
				'function': function(val,element){
					if (Y.dom.one("#new-password").getValue() != val){
						return "两次密码不一致";
					}
				}
			},
			'password': {
				reuiqre: '请输入旧密码',
				min6: '请输入旧密码'
			}
		}
	});
});
</script>
<?php include 'inc/footer.inc.php'?>
