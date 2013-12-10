<?php
$PAGE_CLASS .= 'page-resume-create';
$PAGE_HEAD .= css('resume.css','resume_create.css');
$PAGE_HEAD .= '<style>'.$template_css.$theme_css.'</style>';
include 'inc/header.inc.php';
?>
<script>
	var ADD_COL_URL = '<?php echo url("resume/addCol");?>';
	var ADD_CAREER_URL = '<?php echo url("resume/addCar");?>';
	var CHANGE_AVA_URL = '<?php echo url("user/changeAvatar");?>';
</script>
<div class="clearfix">
	<div class="left-col">
		<form action="<?php echo url('resume/create')?>" method="POST" class="resume resume-create-frm resume-theme-<?php echo $resume['theme'];?>" data-theme-id="<?php echo $resume['theme'];?>">
			<input type="hidden" name="id" value="<?php echo $resume['id']?>">
			<input type="hidden" name="theme" value="<?php echo $resume['theme']?>">

			<!-- 简历封面 -->
			<?php if($cover = $resume['resume_data']['cover']):?>
			<div class="resume-cover" <?php echo !$cover['visible'] ? 'style="display:none"' : ''?>>
				<input type="hidden" name="visibility[cover]" value="1" rel="mod-visibility">
				<span class="resume-cover-title"><?php echo $resume['title']?></span>
				<span class="resume-cover-name"><?php echo $user['name']?></span>
				<span class="resume-cover-email">邮箱: <?php echo $user['email']?></span>
				<span class="resume-cover-mobile">电话: <?php echo $user['mobile']?></span>
			</div>
			<?php endif;?>

			<!-- 简历主体 -->
			<div class="resume-main">
				<div class="resume-main-wrap">
					<input type="submit" value="保存简历" class="btn none" title="保存简历(ctrl+s)"/>

					<?php foreach($resume['resume_data'] as $guid=>$mod):?>
					<?php if($mod['item_type'] == 'cover'){continue;}?>
					<?php
						$item_type = $mod['item_type'];
					?>
					<fieldset class="resume-mod resume-mod-<?php echo $item_type;?> resume-mod-<?php echo $item_type;?>-base" <?php echo $mod['invisible'] ? 'style="display:none"' : ''?>>
						<input type="hidden" name="visibility[<?php echo $item_type;?>]" value="1" rel="mod-visibility">
						<input type="hidden" name="order[<?php echo $item_type;?>]" value="0" rel="mod-order">

						<ul class="resume-mod-op-list">
							<?php if(count($mod['templates'])>1):?>
							<li>
								<span class="ti resume-mod-change-temp-btn">模版&#9660;</span>
								<ul rel="template-select">
									<?php foreach($mod['templates'] as $k=>$template):?>
									<li <?php if($k=='base'):?>class="selected"<?php endif;?> rel="template-select" data-class="resume-mod-<?php echo $item_type;?>-<?php echo $k;?>">
										<span class="tick">√</span>
										<span class="item"><?php echo $template['name']?></span>
									</li>
									<?php endforeach;?>
								</ul>
							</li>
							<?php endif;?>
							<li><a href="javascript:;" class="resume-mod-del-btn" rel="resume-mod-del-btn" data-mod-id="<?php echo $item_type;?>">删除栏目</a></li>
						</ul>

						<?php $placeholder = $mod['placeholder'] ?: '请输入标题';?>
						<div class="resume-mod-ti">
							<input class="txt" type="text" name="title[<?php echo $item_type;?>]" value="<?php echo $mod['item_title'];?>" placeholder="<?php echo $placeholder;?>"/>
						</div>

						<div class="resume-mod-con">
							<?php
								if(!is_array($mod['item_value'])){
									$mod['item_value'] = array($mod['item_value']);
								}
							?>
							<?php foreach($mod['item_value'] as $item_value):?>
							<div class="resume-mod-con-instance">
								<?php
								foreach($mod['data'] as $key=>$item){
									$item_id = 'resume-mod-'.$item_type.'-'.$key;
									$html = '<dl class="'.$item_id.'-item resume-mod-item">';
									$html .= $item['label'] ? '<dt><label for="'.$item_id.'">'.$item['label'].'</label></dt>' : '';
									$html .= '<dd>';
									$name = "data[$item_type][$key]";
									switch($item['type']){
										//年月
										case 'yearmonth':
											$min_y = date('Y', $item['min']);
											$max_y = date('Y', $item['max']);

											$html .= '<select size="1" id="'.$item_id.'-year" name="'.$name.'[year][]">';
											$html .= '<option value="">请选择</option>';
											for($i=$max_y; $i>=$min_y; $i--){
												$html .= '<option value="'.$i.'">'.$i.'年</option>';
											}
											$html .= '</select>';

											$html .= '<select size="1" id="'.$item_id.'-month" name="'.$name.'[month][]">';
											$html .= '<option value="">请选择</option>';
											for($i=1; $i<=12; $i++){
												$html .= '<option value="'.$i.'">'.$i.'月</option>';
											}
											$html .= '</select>';
											break;

										//描述
										case 'description':
											$ah = $key != 'info' ? 'data-auto-resize="1"' : '';
											$html .= '<textarea name="'.$name.'[]" id="'.$item_id.'" cols="30" rows="10" class="txt txt-description" '.$ah;
											$html .= $item['placeholder'] ? 'placeholder="'.$item['placeholder'].'" ' : '';
											$html .= '></textarea>';
											break;

										//HTML富文本
										case 'html':
											$html .= '<textarea name="'.$name.'[]" id="'.$item_id.'" cols="30" rows="10" class="txt" ';
											$html .= $item['placeholder'] ? 'placeholder="'.$item['placeholder'].'" ' : '';
											$html .= '></textarea>';
											break;

										//日期
										case 'date':
											$html .= '<select size="1" id="'.$item_id.'-year" name="'.$name.'[year][]">';
											for($i=date('Y')-5; $i<=date('Y'); $i++){
												$html .= '<option value="'.$i.'">'.$i.'年</option>';
											}
											$html .= '</select>';

											$html .= '<select size="1" id="'.$item_id.'-month" name="'.$name.'[month][]">';
											for($i=1; $i<=12; $i++){
												$html .= '<option value="'.$i.'">'.$i.'月</option>';
											}
											$html .= '</select>';

											$html .= '<select size="1" id="'.$item_id.'-date" name="'.$name.'[date][]">';
											for($i=1; $i<=31; $i++){
												$html .= '<option value="'.$i.'">'.$i.'日</option>';
											}
											$html .= '</select>';
											break;

										//单选
										case 'radio':
											$name = $item_id;
											foreach($item['options'] as $v=>$lbl){
												$html .= '<label><input type="radio" name="'.$name.'[]" value="'.$k.'" class="radio" /> ';
												$html .= $lbl.'</label> &nbsp;';
											}
											break;

										//列表选择
										case 'select':
											$html .= '<select size="1" name="'.$name.'[]">';
											$html .= '<option value="">请选择</option>';
											foreach($item['options'] as $v=>$lbl){
												$html .= '<option value="'.$v.'">'.$lbl.'</option>';
											}
											$html .= '</select>';
											break;

										//图片
										case 'image':
											$html .= '<div class="resume-avatar">';
											$html .= '<div class="resume-avatar-img">'.img('avatar.jpg').'</div>';
											$html .= '<input type="hidden" name="'.$name.'[]" value=""/>';
											$html .= '<input type="button" value="上传照片" class="btn btn-strong" />';
											$html .= '</div>';
											break;

										//字符串或其他
										case 'string':
										default:
											$html .= '<input type="text" name="'.$name.'[]" class="txt txt-string" id="'.$item_id.'" ';
											$html .= $item['placeholder'] ? 'placeholder="'.$item['placeholder'].'" ' : '';
											$html .= '/>';
											break;
									}
									$html .= '</dd></dl>';
									echo $html;
								}
								?>

								<?php if($mod['multi_instance']):?>
								<div class="resume-mod-instance-op">
									<!-- <a href="" class="resume-mod-clone-instance-btn">复制</a> -->
									<a href="" class="resume-mod-remove-instance-btn">删除</a>
								</div>
								<?php endif;?>
							</div>
							<?php endforeach;?>
						</div>

						<?php if($mod['multi_instance']):?>
						<div class="resume-mod-append-instance">
							<a href="" class="resume-mod-append-instance-btn">添加一项</a>
						</div>
						<?php endif;?>
					</fieldset>
					<?php endforeach;?>

					<fieldset id="blank-catalog" class="resume-mod resume-mod-empty" style="display:none">
						<ul class="resume-mod-op-list">
							<li><a href="javascript:;" class="resume-mod-del-btn" rel="resume-mod-del-btn">删除栏目</a></li>
						</ul>
						<div class="resume-mod-ti">
							<input class="txt" type="text" name="title[blank][]" value="" placeholder="请输入标题"/>
						</div>
						<div class="resume-mod-con">
							<textarea name="data[blank][]" class="txt" id="" cols="50" rows="5" placeholder="请输入内容"></textarea>
						</div>
					</fieldset>
				</div>
			</div>

			<div class="resume-op">
				<input type="submit" value="保存简历" class="btn btn-strong" />
				<input type="button" value="下载打印" class="btn btn-strong" />
				<input type="button" value="+追加一空白项目" class="btn btn-strong resume-add-more-btn" id="resume-add-more-btn" />
			</div>
		</form>
	</div>

	<div class="right-col">
		<form action="" class="side-mod cover-setting">
			<h3>主题设定</h3>
			<ul>
				<?php foreach($themes as $theme_id=>$theme):?>
				<li class="<?php echo $theme_id == $resume['theme'] ? 'current':''?>" data-theme-id="<?php echo $theme_id;?>" rel="resume-change-theme-btn">
					<img src="<?php echo $theme['thumb'];?>">
				</li>
				<?php endforeach;?>
			</ul>
		</form>

		<form action="" class="side-mod column-manager">
			<h3>栏目调整</h3>
			<dl>
				<?php foreach($resume['resume_data'] as $guid=>$mod):?>
				<dd class="<?php echo !$mod['visible'] ? 'mod-invisible' : '';?>">
					<span class="order-drag"></span>
					<span class="ti"><?php echo $mod['item_title']?></span>
					<span class="vi" data-mod-id="<?php echo $item_type;?>"><?php echo !$data['visible'] ? '显示' : '隐藏'?></span>
					<span class="del" data-mod-id="<?php echo $item_type;?>" rel="resume-mod-del-btn">删除</span>
				</dd>
				<?php endforeach;?>
			</dl>
			<p class="op"><input type="button" value="+ 添加栏目" class="btn btn-strong"></p>
		</form>

		<form action="?" class="side-mod career-search">
			<h3>添加工作经历</h3>

			<div class="srch-kw">
				<input type="text" placeholder="关键字搜索" name="" id="" class="txt">
				<input type="submit" value="搜索" class="btn btn-strong"/>
			</div>

			<div class="srch-more">
				<h4>更多搜索条件&#9660;</h4>
				<dl>
					<dt><label for="">按职位</label></dt>
					<dd><select name="" id=""><option value="">人事</option></select></dd>
				</dl>
				<dl>
					<dt>按资历</dt>
					<dd><select name="" id=""><option value="">人事</option></select></dd>
				</dl>
				<dl>
					<dt>按行业</dt>
					<dd><select name="" id=""><option value="">人事</option></select></dd>
				</dl>
			</div>
		</form>
	</div>
</div>

<?php echo js('resume_create.js');?>
<?php include 'inc/footer.inc.php'?>
