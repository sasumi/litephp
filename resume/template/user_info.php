<?php
$PAGE_CLASS .= 'page-user-info';
$PAGE_HEAD .= css('user.css');
include 'inc/header.inc.php';
include 'inc/usernav.inc.php';
?>
<div class="right-col">
	<h2 class="cap">个人资料</h2>
	<form action="<?php echo url('user/modify');?>" method="POST" class="frm user-common-frm" rel="iframe-form" onresponse="response">
		<div class="avatar">
			<?php $avatar = Q::ini('app_config/UPLOAD_URL').($current_user['album'] ?: 'demo-avatar.jpg');?>
			<a href="<?php echo img_url($avatar)?>" target="_blank" style="width:120px; height:120px; overflow:hidden;">
				<?php echo img($avatar)?>
			</a>
			<p>
				<input type="button" value="重设头像" class="btn btn-strong" id="change-avatar-btn"/>
			</p>
		</div>
		<fieldset>
			<dl>
				<dt>
					<label for="name">姓名</label>
				</dt>
				<dd>
					<input type="text" name="name" id="name" value="<?php echo $current_user['name']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="email">邮箱</label>
				</dt>
				<dd>
					<input type="text" name="email" id="email" value="<?php echo $current_user['email']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="mobile">手机</label>
				</dt>
				<dd>
					<input type="text" name="mobile" id="mobile" value="<?php echo $current_user['mobile']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="ethnic">民族</label>
				</dt>
				<dd>
					<input type="text" name="ethnic" id="ethnic" value="<?php echo $current_user['ethnic']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="hometown">籍贯</label>
				</dt>
				<dd>
					<input type="text" name="hometown" id="hometown" value="<?php echo $current_user['hometown']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="address">现居住</label>
				</dt>
				<dd>
					<input type="text" name="address" id="address" value="<?php echo $current_user['address']?>" class="txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="education">最高学历</label>
				</dt>
				<dd>
					<select name="education" id="education">
						<?php
							$arr = array('小学','初中','高中','本科','中专','大专','硕士','博士','其他');
							foreach($arr as $name){
								echo '<option value="'.$val.'"'.($name == $current_user['education'] ? ' selected': '').'>'.$name.'</option>';
							}
						?>
					</select>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="height">身高</label>
				</dt>
				<dd>
					<input type="text" name="height" id="height" value="<?php echo $current_user['height']?>" class="txt s-txt"/>
					厘米
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="birth">出生日期</label>
				</dt>
				<dd>
					<input type="text" name="birth" id="birth" value="<?php echo $current_user['birth']?>" class="txt date-txt"/>
				</dd>
			</dl>
			<dl>
				<dt>
					<label for="marriage">婚姻状况</label>
				</dt>
				<dd>
					<input type="text" name="marriage" id="marriage" class="txt"/>
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
