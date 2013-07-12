<!DOCTYPE html>
<html class="dialog">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title>append catalog</title>
	<?php echo css('reset.css', 'global.css');?>
</head>
<body>
	<form action="" class="frm">
		<fieldset>
			<dl>
				<dt>栏目类型：</dt>
				<dd>
					<select name="" id="">
						<?php foreach($all_mods as $mod_id=>$mod):?>
						<option value="<?php echo $mod_id;?>" <?php if(in_array($mod_id, $cur_mods)):?>disabled="true"<?php endif;?>>
							<?php echo $mod['title']?><?php if(in_array($mod_id, $cur_mods)):?> - [已添加]<?php endif;?>
						</option>
						<?php endforeach;?>
						<option value="0">空白栏目</option>
					</select>
				</dd>
			</dl>
			<dl>
				<dt>栏目标题：</dt>
				<dd><input type="text" name="" class="txt" placeholder="默认为栏目类型"/></dd>
			</dl>
		</fieldset>
	</form>

</body>
</html>
