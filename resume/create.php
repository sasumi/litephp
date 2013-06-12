<?php include 'header.inc.php'?>
<div style="float:left; width:700px; margin-right:20px;">
	<form action="?" method="POST">
		<fieldset>
			<button type="submit">保存简历</button>
			<button type="submit">下载打印</button>
		</fieldset>
		<fieldset>
			<input type="text" name="" length="30" placeholder="给你的简历起个标题...例如“北京大学_张三_应聘电信市场部市场专员“"/>
		</fieldset>

		<fieldset>
			<p style="text-align:right"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
			<p><input type="text" name="" value="基本资料"/></p>
			<ul>
				<li><label for="">姓名：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">性别：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">联系电话：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">电子邮箱：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">出生年月：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">学历：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">户籍所在地：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">民族：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">学校专业：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">主要证书：</label><input type="text" name="" id="" value="def"/></li>
				<li><label for="">求职意向：</label><input type="text" name="" id="" value="def"/></li>
			</ul>
		</fieldset>

		<fieldset>
			<p style="text-align:right"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
			<p><input type="text" name="" value="工作技能"/></p>
			<p><textarea name="" id="" cols="50" rows="5"> 法务能力：法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。• 文书处理：法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。</textarea></p>
		</fieldset>

		<fieldset>
			<p style="text-align:right"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
			<p><input type="text" name="" value="教育培训"/></p>
			<p><textarea name="" id="" cols="50" rows="5"> 法务能力：法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。• 文书处理：法律知识体系丰富完整，具有运用法学理论和方法分析问题和运用法律管理事务与解决问题的能力。具有较的法学逻辑思维和独立的学习、分析、处理能力，了解司法程序，能协助企业单位防范法律范围内的风险，处理企业单位日常诉讼、非诉讼法律事务。</textarea></p>
		</fieldset>

		<fieldset id="blank-catalog" style="display:none">
			<p style="text-align:right"><a href="">删除</a> <a href="" class="tpl_lnk">模版&darr;</a></p>
			<p><input type="text" name="" value="" placeholder="请输入标题"/></p>
			<p><textarea name="" id="" cols="50" rows="5" placeholder="请输入内容"></textarea></p>
		</fieldset>

		<p><button id="add-more-btn">追加一空白项</button></p>

		<fieldset>
			封面设定：
			<input type="radio" name="" id="" checked="true"/>无封面
			<input type="radio" name="" id="" />封面1
			<input type="radio" name="" id="" />封面2
			<input type="radio" name="" id="" />封面3
		</fieldset>

		<p>
			<button type="submit">保存简历</button>
			<button type="submit">下载打印</button>
		</p>
	</form>
</div>

<div>
	<form action="">
		<dl>
			<dt>栏目调整</dt>
			<dd>
				<input type="button" class="order-btn" value="&uarr;&darr;"/>
				基本资料
				<input type="button" value="显示"/>
				<input type="button" value="删除"/>
			</dd>
			<dd>
				<input type="button" class="order-btn" value="&uarr;&darr;"/>
				工作技能
				<input type="button" value="隐藏"/>
				<input type="button" value="删除"/>
			</dd>
			<dd>
				<input type="button" class="order-btn" value="&uarr;&darr;"/>
				教育培训
				<input type="button" value="隐藏"/>
				<input type="button" value="删除"/>
			</dd>
			<dd>
				<input type="button" class="order-btn" value="&uarr;&darr;"/>
				基本资料
				<input type="button" value="隐藏"/>
				<input type="button" value="删除"/>
			</dd>
			<dd>
				<input type="button" id="add-catalog-btn" value="添加栏目"/>
			</dd>
		</dl>
	</form>

	<form action="?" id="career-search-form">
		<fieldset>
			<legend>添加工作经历</legend>
			<dl>
				<dt>关键字搜索</dt>
				<dd><input type="text" name="" id="" value="def"/><input type="submit" value="搜索" id="career-search-btn"/></dd>
			</dl>
			<dl>
				<dt>条件检索</dt>
			</dl>
			<dl>
				<dt>按职位</dt>
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
		</fieldset>

		<p>
			操作提示<hr/>
			搜索或筛选项目条件，点击插入，即会从文末添加，并自动套蓝显示。
		</p>
	</form>

	<div id="change-tpl" style="display:none; background-color:white; padding:5px 0;">
		<a href="">模版1</a><br/>
		<a href="">模版2</a><br/>
		<a href="">模版3</a><br/>
		<a href="">模版4</a>
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