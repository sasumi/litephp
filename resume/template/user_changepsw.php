<?php
$PAGE_CLASS .= 'page-user-info';
$PAGE_HEAD .= css('user.css');
include 'inc/header.inc.php';
include 'inc/usernav.inc.php';
?>
<div class="right-col">
	<h2 class="cap">修改密码</h2>
	<form action="<?php echo url('user/modify');?>" method="POST" class="frm user-common-frm" rel="iframe-form" onresponse="response">
		<fieldset>
			<dl>
				<dt>
					<label for="">新密码</label>
				</dt>
				<dd>
					<input type="text" name="name" id="name" value="<?php echo $current_user['name']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="email">重复新密码</label>
				</dt>
				<dd>
					<input type="text" name="email" id="email" value="<?php echo $current_user['email']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="mobile">旧密码</label>
				</dt>
				<dd>
					<input type="text" name="mobile" id="mobile" value="<?php echo $current_user['mobile']?>" class="txt"/>
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
var response = function(){
	alert(1);
};
(function(Y){
	Y.dom.one('#change-avatar-btn').on('click', function(){
		R.changeAvatar(function(src){
			Y.dom.one('.avatar img').setAttr('src', src);
			R.scaleAvaImg(Y.dom.one('.avatar img').getDomNode());
		});
	});
	R.scaleAvaImg(Y.dom.one('.avatar img').getDomNode());
})(YSL);
</script>
<?php include 'inc/footer.inc.php'?>
