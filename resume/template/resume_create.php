<?php
$PAGE_CLASS .= 'page-resume-create';
$PAGE_HEAD .= css('resume_create.css', 'theme.css');
$PAGE_HEAD .= '<style>'.$theme_css.'</style>';

include 'header.inc.php';
?>
<script>
	var ADD_COL_URL = '<?php echo url("resume/addCol");?>';
	var ADD_CAREER_URL = '<?php echo url("resume/addCar");?>';
</script>
<div class="clearfix">
	<div class="left-col">
		<form action="<?php echo url('user/myresume')?>" method="POST" class="resume-create resume-theme resume-theme-c1">
			<div class="resume-create-wrap resume-theme-wrap">
				<input type="submit" value="保存简历" class="btn none" title="保存简历(ctrl+s)"/>
				<?php foreach($mods as $mod_id=>$mod):?>
				<fieldset class="resume-mod resume-mod-<?php echo $mod_id;?> resume-mod-<?php echo $mod_id;?>-base">
					<ul class="resume-mod-op-list">
						<?php if(count($mod['themes'])>1):?>
						<li>
							<span class="ti resume-mod-change-temp-btn">模版&#9660;</span>
							<ul rel="theme-select">
								<?php foreach($mod['themes'] as $k=>$theme):?>
								<li <?php if($k=='base'):?>class="selected"<?php endif;?> rel="theme-select" data-class="resume-mod-<?php echo $mod_id;?>-<?php echo $k;?>">
									<span class="tick">√</span>
									<span class="item"><?php echo $theme['name']?></span>
								</li>
								<?php endforeach;?>
							</ul>
						</li>
						<?php endif;?>
						<li><a href="javascript:;" class="resume-mod-del-btn" rel="resume-mod-del-btn">删除栏目</a></li>
					</ul>

					<?php if($mod['title']):?>
					<?php $placeholder = $mod['placeholder'] ?: '请输入标题';?>
					<div class="resume-mod-ti">
						<input class="txt" type="text" name="" value="<?php echo $mod['title'];?>" placeholder="<?php echo $placeholder;?>"/>
					</div>
					<?php endif;?>

					<div class="resume-mod-con">
						<div class="resume-mod-con-instance">
							<?php
							foreach($mod['data'] as $key=>$item){
								$item_id = 'resume-mod-'.$mod_id.'-'.$key;
								$html = '<dl class="'.$item_id.'-item resume-mod-item">';
								$html .= $item['label'] ? '<dt><label for="'.$item_id.'">'.$item['label'].'</label></dt>' : '';
								$html .= '<dd>';
								switch($item['type']){
									case 'yearmonth':
										$html .= '<select size="1" id="'.$item_id.'">';
										for($i=date('Y')-5; $i<=date('Y'); $i++){
											$html .= '<option value="">'.$i.'年</option>';
										}
										$html .= '</select>';

										$html .= '<select size="1" id="'.$item_id.'-month">';
										for($i=1; $i<=12; $i++){
											$html .= '<option value="">'.$i.'月</option>';
										}
										$html .= '</select>';
										break;

									case 'description':
										$html .= '<textarea name="" id="'.$item_id.'" cols="30" rows="10" class="txt txt-description" ';
										$html .= $item['placeholder'] ? 'placeholder="'.$item['placeholder'].'" ' : '';
										$html .= '></textarea>';
										break;

									case 'html':
										$html .= '<textarea name="" id="'.$item_id.'" cols="30" rows="10" class="txt" ';
										$html .= $item['placeholder'] ? 'placeholder="'.$item['placeholder'].'" ' : '';
										$html .= '></textarea>';
										break;

									case 'date':
										$html .= '<select size="1" id="'.$item_id.'">';
										for($i=date('Y')-5; $i<=date('Y'); $i++){
											$html .= '<option value="">'.$i.'年</option>';
										}
										$html .= '</select>';

										$html .= '<select size="1" id="'.$item_id.'-month">';
										for($i=1; $i<=12; $i++){
											$html .= '<option value="">'.$i.'月</option>';
										}
										$html .= '</select>';

										$html .= '<select size="1" id="'.$item_id.'-date">';
										for($i=1; $i<=31; $i++){
											$html .= '<option value="">'.$i.'日</option>';
										}
										$html .= '</select>';
										break;

									case 'radio':
										$name = $item_id;
										foreach($item['options'] as $v=>$lbl){
											$html .= '<label><input type="radio" name="'.$name.'" value="'.$k.'" class="radio" /> ';
											$html .= $lbl.'</label> &nbsp;';
										}
										break;

									case 'select':
										$html .= '<select size="1" name="'.$name.'">';
										foreach($item['options'] as $v=>$lbl){
											$html .= '<option value="'.$v.'">'.$lbl.'</option>';
										}
										$html .= '</select>';
										break;

									case 'string':
									default:
										$html .= '<input type="text" name="'.$key.'" class="txt txt-string" id="'.$item_id.'" ';
										$html .= $item['placeholder'] ? 'placeholder="'.$item['placeholder'].'" ' : '';
										$html .= '/>';
										break;
								}
								$html .= '</dd></dl>';
								echo $html;
							}
							?>

							<?php if($mod['multiInstance']):?>
							<div class="resume-mod-instance-op">
								<a href="" class="resume-mod-clone-instance-btn">复制</a>
								<a href="" class="resume-mod-remove-instance-btn">删除</a>
							</div>
							<?php endif;?>
						</div>
					</div>

					<?php if($mod['multiInstance']):?>
					<div class="resume-mod-append-instance">
						<a href="" class="resume-mod-append-instance-btn">添加一项</a>
					</div>
					<?php endif;?>
				</fieldset>
				<?php endforeach;?>

				<fieldset id="blank-catalog" class="resume-mod resume-mod-empty" style="display:none">
					<ul class="resume-mod-op-list">
						<li><a href="javascript:;" rel="resume-mod-del-btn">删除栏目</a></li>
					</ul>
					<div class="resume-mod-ti">
						<input class="txt" type="text" name="" value="" placeholder="请输入标题"/>
					</div>
					<div class="resume-mod-con">
						<textarea name="" class="txt" id="" cols="50" rows="5" placeholder="请输入内容"></textarea>
					</div>
				</fieldset>
			</div>

			<div class="resume-op">
				<input type="submit" value="保存简历" class="btn b-btn" />
				<input type="button" value="下载打印" class="btn b-btn" />
				<input type="button" value="+追加一空白项目" class="btn b-btn resume-add-more-btn" id="resume-add-more-btn" />
			</div>
		</form>
	</div>

	<div class="right-col">
		<form action="" class="side-mod column-manager">
			<h3>栏目调整</h3>
			<dl>
				<dd>
					<span class="order-drag"></span>
					<span class="ti">基本资料</span>
					<a href="<?php echo url('resume/column')?>">显示</a>
					<a href="<?php echo url('resume/del')?>">删除</a>
				</dd>
				<dd>
					<span class="order-drag"></span>
					<span class="ti">工作技能</span>
					<a href="<?php echo url('resume/column')?>">显示</a>
					<a href="<?php echo url('resume/del')?>">删除</a>
				</dd>
				<dd>
					<span class="order-drag"></span>
					<span class="ti">教育培训</span>
					<a href="<?php echo url('resume/column')?>">显示</a>
					<a href="<?php echo url('resume/del')?>">删除</a>
				</dd>
				<dd>
					<span class="order-drag"></span>
					<span class="ti">语言能力</span>
					<a href="<?php echo url('resume/column')?>">显示</a>
					<a href="<?php echo url('resume/del')?>">删除</a>
				</dd>
				<dd>
					<span class="order-drag"></span>
					<span class="ti">其他</span>
					<a href="<?php echo url('resume/column')?>">显示</a>
					<a href="<?php echo url('resume/del')?>">删除</a>
				</dd>
			</dl>
			<p class="op"><input type="button" value="+ 添加栏目" class="btn"></p>
		</form>

		<form action="" class="side-mod cover-setting">
			<h3>封面设定</h3>
			<ul>
				<li class="current"><?php echo img('cover1.png')?></li>
				<li><?php echo img('cover1.png')?></li>
				<li><?php echo img('cover1.png')?></li>
				<li><?php echo img('cover1.png')?></li>
			</ul>
		</form>

		<form action="?" class="side-mod career-search">
			<h3>添加工作经历</h3>

			<div class="srch-kw">
				<input type="text" placeholder="关键字搜索" name="" id="" class="txt">
				<input type="submit" value="搜索" class="btn"/>
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

		<div class="page-tip">
			<strong>操作提示</strong>
			搜索或筛选项目条件，点击插入，即会从文末添加，并自动套蓝显示。
		</div>

		<div id="change-tpl" style="display:none; background-color:white; padding:5px 0;">
			<a href="">模版1</a><br/>
			<a href="">模版2</a><br/>
			<a href="">模版3</a><br/>
			<a href="">模版4</a>
		</div>
	</div>
</div>

<?php echo js('resume_create.js');?>
<?php include 'footer.inc.php'?>
