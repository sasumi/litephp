YSL.use('widget.Popup,widget.Tip,widget.Dragdrop,widget.Animate', function(Y, POP, Tip, DD, ANI){
	/**
	 * 删除模块
	 * @param  {String} mod_id
	 * @param  {Function} succCb 确认成功回调
	 */
	var delMod = function(mod_id, succCb){
		succCb = succCb || Y.emptyFn;
		var pop = new POP({
			title: '提示',
			content: '是否确定要删除该栏目和栏目下的是所有数据？',
			buttons: [
				{name:'删除', handler: function(){
					var n = findModDom(mod_id);
					n.remove();
					var mnu_item = Y.dom.one('.column-manager span[data-mod-id='+mod_id+']');
					mnu_item.parent('dd').remove();
					succCb();
					new Y.widget.Tip('删除成功',1,1);
					pop.close();
				}},
				{name: '取消'}
			],
			width:300
		});
		pop.show();
	};

	/**
	 * 隐藏模块
	 * @param  {String} mod_id
	 */
	var hideMod = function(mod_id){
		findModDom(mod_id).hide();
	};

	/**
	 * 显示模块
	 * @param  {String} mod_id
	 */
	var showMod = function(mod_id){
		findModDom(mod_id).show();
	};

	/**
	 * 查找模块
	 * @param  {String} mod_id
	 * @return {DOM}
	 */
	var findModDom = function(mod_id){
		if(mod_id == 'cover'){
			return Y.dom.one('.resume-cover');
		}
		return Y.dom.one('.resume-mod-'+mod_id);
	}

	/**
	 * 设置主题
	 * @param  {String} theme_id
	 */
	var setTheme = function(theme_id){
		var resume = Y.dom.one('.resume');
		var last_theme_id = resume.getAttr('data-theme-id');
		resume.removeClass('resume-theme-'+last_theme_id);
		resume.addClass('resume-theme-'+theme_id);
		resume.setAttr('data-theme-id',theme_id);
		Y.dom.one('input[name=theme]').setValue(theme_id);
	}

	//修改照片
	Y.dom.one('.resume-avatar img').on('load', function(){
		R.scaleAvaImg(this.getDomNode());
	});
	Y.dom.one('.resume').delegate('.resume-avatar .btn', 'click', function(){
		R.changeAvatar(function(src){
			Y.dom.one('.resume-avatar img').setAttr('src', src);
		});
	});

	//模版选择
	Y.dom.one('.resume').delegate('*[rel=template-select]', 'click', function(){
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
		var cur_mods = [];
		Y.dom.all('.column-manager span.vi').each(function(n){
			cur_mods.push(n.getAttr('data-mod-id'));
		});

		var url = Y.net.mergeCgiUri(ADD_COL_URL, {cur_mods: cur_mods.join(',')});
		var p = new POP({
			title: '添加栏目',
			content: {src: url},
			buttons: [{name:'添加栏目'}, {name:'取消'}],
			width: 400
		});
		p.show();
	});

	//添加空白栏目
	Y.dom.one('#resume-add-more-btn').on('click', function(){
		var guid = 'empty'+Y.guid();
		var blank = Y.dom.one('#blank-catalog').getDomNode();
		var n = blank.cloneNode(true);
		n.id = '';
		Y.dom.one(n).addClass('resume-mod-'+guid);
		Y.dom.one(n).one('.resume-mod-del-btn').setAttr('data-mod-id', guid);
		blank.parentNode.insertBefore(n, blank);
		Y.dom.one(n).show();
		Y.event.preventDefault();
	});

	//删除栏目
	Y.dom.one('.resume').delegate('*[rel=resume-mod-del-btn]', 'click', function(){
		Y.event.preventDefault();
		var mod_id = this.getAttr('data-mod-id');
		delMod(mod_id);
	});

	//搜索
	Y.dom.one('form.career-search').on('submit', function(){
		Y.event.preventDefault();
		var p = new POP({
			title: '添加工作经历',
			content: {src: ADD_CAREER_URL},
			buttons: [{name:'关闭'}],
			height: 300,
			width: 600
		});
		p.show();
	});

	//添加子栏目
	Y.dom.one('.resume').delegate('.resume-mod-append-instance-btn', 'click', function(){
		Y.event.preventDefault();
		var col = this.parent('fieldset');
		var last = col.one('.resume-mod-con').last();
		last.clone(true).insertAfter(last);
	});

	//删除子栏目
	Y.dom.one('.resume').delegate('.resume-mod-remove-instance-btn', 'click', function(){
		Y.event.preventDefault();
		var mod = this.parent('fieldset');

		if(mod.all('.resume-mod-con-instance').size() == 1){
			new Y.widget.Tip('不能删除最后一项数据',0,1);
		} else {
			var p = this.parent(function(n){
				if(n.existClass('resume-mod-con-instance')){
					return true;
				}
			});
			p.remove();
		}
	});

	//自动增高textarea

	//column drag
	Y.dom.all('.column-manager .order-drag').on('mousedown', function(){
		var dobj = DD.singleton(this, {proxy: this.parent()});
		dobj.start();
	});

	Y.dom.all('.column-manager .ti').on('mousedown', function(){
		Y.event.preventDefault();
		var dobj = DD.singleton(this, {proxy: this.parent(), container:this.parent('form')});
		dobj.onMoving = function(e){
			var x = e.clientX, y = e.clientY;
			console.log('moving',x, y);
		};
		dobj.onStart = function(e){
			var tag = Y.event.getTarget(e);
			var parent = tag.parent('dd');
			var next = parent.next();
			var prev = parent.previous();
			if(next){
				next.setStyle('marginTop', 30);
			}
			else if(prev){
				prev.setStyle('marginBottom', 30);
			}
		}
		dobj.start();
	});

	Y.dom.one(document).on('mouseup', function(){
		Y.dom.all('.order-drag').each(function(item){
			item.parent().getDomNode().style.cssText = '';
		});
	});

	//栏目
	Y.dom.all('.column-manager').delegate('span.vi', 'click', function(){
		var mod_id = this.getAttr('data-mod-id');
		var toHide = this.getHtml() == '隐藏';
		toHide ? hideMod(mod_id) : showMod(mod_id);
		this.setHtml(toHide ? '显示':'隐藏');
		this.parent('dd')[toHide ? 'addClass' : 'removeClass']('mod-invisible')
	});

	Y.dom.all('.column-manager').delegate('span.del', 'click', function(){
		var _this = this;
		var mod_id = this.getAttr('data-mod-id');
		delMod(mod_id);
	});

	//主题
	Y.dom.one('.cover-setting').delegate('li[rel=resume-change-theme-btn]', 'click', function(){
		Y.event.preventDefault();
		var theme_id = this.getAttr('data-theme-id');
		setTheme(theme_id);
		this.parent().all('li').removeClass('current');
		this.addClass('current');
	});

	//滚动
	Y.dom.one(window).on('scroll', function(){
		var scrollTop = document.body.scrollTop;
		var offTop = 90;
		//Y.dom.one('.right-col').setStyle('marginTop', Math.max(scrollTop-offTop, 0));
	})
});
