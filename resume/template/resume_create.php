<?php include 'header.inc.php'?>
<?php echo css('resume.css')?>
<div class="page-resume-create clearfix">
	<div class="left-col">
		<form action="?" method="POST" class="create-frm">
			<input type="submit" value="保存简历" class="btn" title="保存简历(ctrl+s)"/>
			<fieldset class="field-title">
				<label for="title">标题</label>
				<input class="txt" type="text" name="title" id="title" length="" placeholder="给你的简历起个标题...例如“北京大学_张三_应聘电信市场部市场专员“"/>
			</fieldset>

			<fieldset class="common-mod mod-userinfo">
				<p class="mod-op"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
				<p class="mod-ti"><input class="txt" type="text" name="" value="基本资料"/></p>

				<dl><dt><label for="">姓名</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">性别</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">联系电话</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">电子邮箱</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">出生年月</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">学历</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">户籍所在地</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">民族</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">学校专业</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">主要证书</label></dt><dd><input class="txt" type="text"/></dd></dl>
				<dl><dt><label for="">求职意向</label></dt><dd><input class="txt" type="text"/></dd></dl>
			</fieldset>

			<fieldset class="common-mod">
				<p class="mod-op"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
				<p class="mod-ti"><input class="txt" type="text" name="" value="工作技能"/></p>
				<p class="mod-con"><textarea class="txt" name="" id="" cols="50" rows="5"> 法务能力法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。• 文书处理法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。</textarea></p>
			</fieldset>

			<fieldset class="common-mod">
				<p class="mod-op"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
				<p class="mod-ti"><input class="txt" type="text" name="" value="教育培训"/></p>
				<p class="mod-con"><textarea name="" class="txt" id="" cols="50" rows="5"> 法务能力法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。• 文书处理法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。</textarea></p>
			</fieldset>

			<fieldset id="blank-catalog" style="display:none">
				<p class="mod-op"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
				<p class="mod-ti"><input class="txt" type="text" name="" value="" placeholder="请输入标题"/></p>
				<p class="mod-con"><textarea name="" class="txt" id="" cols="50" rows="5" placeholder="请输入内容"></textarea></p>
			</fieldset>

			<p class="add-more">
				<input type="button" value="+追加一空白项目" class="btn" id="add-more-btn" />
			</p>
			<p>
				<input type="submit" value="保存简历" class="btn" />
				<input type="button" value="下载打印" class="btn" />
			</p>
		</form>
	</div>

	<div class="right-col">
		<form action="" class="side-mod cover-setting">
			<h3>封面设定</h3>
			<ul>
				<li class="current"><?php echo img('cover1.png')?></li>
				<li><?php echo img('cover1.png')?></li>
				<li><?php echo img('cover1.png')?></li>
				<li><?php echo img('cover1.png')?></li>
			</ul>
		</form>


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
					<span class="ti">基本资料</span>
					<a href="<?php echo url('resume/column')?>">显示</a>
					<a href="<?php echo url('resume/del')?>">删除</a>
				</dd>
			</dl>
			<p class="op"><input type="button" value="+ 添加栏目" class="btn"></p>
		</form>

		<form action="?" id="career-search-form" class="side-mod career-search">
			<h3>添加工作经历</h3>

			<p class="srch-kw">
				<input type="text" placeholder="关键字搜索" name="" id="" class="txt">
				<input type="submit" value="搜索" class="btn"/>
			</p>

			<h4>更多搜索条件</h4>
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
<script type="text/javascript">
YSL.use('widget.Popup,widget.Dragdrop', function(Y, POP, DD){
	Y.dom.one('#add-catalog-btn').on('click', function(){
		var p = new POP({
			title: '添加栏目',
			content: {src: 'dialog/append_catalog.php'},
			buttons: [{name:'保存'}, {name:'取消'}],
			width: 400
		});
		p.show();
	});

	Y.dom.one('#add-more-btn').on('click', function(){
		var blank = Y.dom.one('#blank-catalog').getDomNode();
		var n = blank.cloneNode(true);
		n.id = '';
		blank.parentNode.insertBefore(n, Y.dom.one('#add-more-btn').parent().getDomNode());
		Y.dom.one(n).show();
		Y.event.preventDefault();
	});

	Y.dom.one('#career-search-form').on('submit', function(){
		var p = new POP({
			title: '添加工作经历',
			content: {src: 'dialog/career_search.php'},
			buttons: [{name:'保存'}, {name:'取消'}],
			height: 300,
			width: 600
		});
		p.show();
		Y.event.preventDefault();
	});

	Y.dom.all('.order-btn').on('mousedown', function(){
		DD.singleton(this.parent());
	});

	Y.dom.one(document).on('mouseup', function(){
		Y.dom.all('.order-btn').each(function(item){
			item.parent().getDomNode().style.cssText = '';
		});
	});

	Y.dom.delegate('.tpl_lnk', 'mouseover', function(){
		var region = this.getRegion();
		var dm = Y.dom.one('#change-tpl');
		dm.show();

		dm.setStyle({
			'position': 'absolute',
			'left': region.left,
			'top': region.top+region.height
		});
		Y.event.preventDefault();
	});
});
</script>
<?php include 'footer.inc.php'?>