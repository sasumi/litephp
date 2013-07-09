YSL.use('widget.Popup,widget.Dragdrop,widget.Animate', function(Y, POP, DD, ANI){
	//主题选择
	Y.dom.one('.resume-create').delegate('*[rel=theme-select]', 'click', function(){
		var fieldset = this.parent('fieldset');
		this.parent().all('li').each(function(li){
			li.removeClass('selected');
			var cls = li.getAttr('data-class');
			fieldset.removeClass(cls);
		});

		fieldset.addClass(this.getAttr('data-class'));
		this.addClass('selected');
	});

	//添加栏目
	Y.dom.one('.column-manager .op input[type=button]').on('click', function(){
		var p = new POP({
			title: '添加栏目',
			content: {src: ADD_COL_URL},
			buttons: [{name:'添加栏目'}, {name:'取消'}],
			width: 400
		});
		p.show();
	});

	//添加空白栏目
	Y.dom.one('#resume-add-more-btn').on('click', function(){
		var blank = Y.dom.one('#blank-catalog').getDomNode();
		var n = blank.cloneNode(true);
		n.id = '';
		blank.parentNode.insertBefore(n, blank);
		Y.dom.one(n).show();
		Y.event.preventDefault();
	});


	//删除栏目
	Y.dom.one('.resume-create').delegate('a[rel=resume-mod-del-btn]', 'click', function(){
		var _this = this;
		var pop = new POP({
			title: '提示',
			content: '是否确定要删除该项数据？',
			buttons: [
				{name:'删除', handler: function(){
					var field = _this.parent('fieldset');
					field.setStyle('overflow', 'hidden');
					field.remove();
					pop.close();
				}},
				{name: '取消'}
			],
			width:300
		});
		pop.show();
		Y.event.preventDefault();
	});

	//搜索
	Y.dom.one('form.career-search').on('submit', function(){
		var p = new POP({
			title: '添加工作经历',
			content: {src: ADD_CAREER_URL},
			buttons: [{name:'关闭'}],
			height: 300,
			width: 600
		});
		p.show();
		Y.event.preventDefault();
	});

	//添加子栏目
	Y.dom.one('.resume-create').delegate('.resume-mod-append-instance-btn', 'click', function(){
		Y.event.preventDefault();
		var col = this.parent('fieldset');
		var con = col.one('.resume-mod-con');
		con.parent().getDomNode().insertBefore(con.getDomNode().cloneNode(true), con.getDomNode());
	});

	//删除子栏目
	Y.dom.one('.resume-create').delegate('.resume-mod-remove-instance-btn', 'click', function(){
		Y.event.preventDefault();
		var mod = this.parent('fieldset');
		if(mod.all('.resume-mod-con-instance').size() == 1){
			alert('不能删除最后一项数据');
		} else {
			var p = this.parent(function(n){
				if(n.existClass('resume-mod-con-instance')){
					return true;
				}
			});
			p.remove();
		}
	});

	//封面
	Y.dom.one('.cover-setting').delegate('li', 'click', function(){
		this.parent().all('li').removeClass('current');
		this.addClass('current');
	});

	Y.dom.all('.order-drag').on('mousedown', function(){
		DD.singleton(this.parent());
	});

	Y.dom.one(document).on('mouseup', function(){
		Y.dom.all('.order-drag').each(function(item){
			item.parent().getDomNode().style.cssText = '';
		});
	});
});
