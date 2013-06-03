(function(Y){
	Y.widget.Pagination = function(config){
		this.config = Y.object.extend({
			itemTotal: 0,
			pageSize: 10,
			pageIndex: 1,
			numOffset: 5,
			modelStr: '',
			container: null,
			showIfEmpty: true,
			cssClassName: {
				first: 'pagination_first',
				next: 'pagination_next',
				num: 'pagination_num',
				eclipse: 'pagination_eclipse',
				last: 'pagination_last'
			},
			onPageSwitch: function(tagPageIndex, srcPageIndex){}
		}, config);

		if(!this.config.container){
			throw "NO PAGINATION CONTAINER SPECIFYED";
		} else {
			this.config.container = Y.dom.one(this.config.container);
		}

		this.bindEvent();
	};

	Y.widget.Pagination.prototype.bindEvent = function(){
		var _this = this;
		this.config.container.on('click', function(ev){
			var tag = Y.event.getTarget(ev);
			if(tag.tagName == 'A'){
				switch(tag.rel){
					case 'first':
						_this.jumpToPage(1);
						break;

					case 'next':
						_this.jumpToPage(_this.config.pageIndex+1);
						break;

					case 'num':
						_this.jumpToPage(tag.getAttribute('num'));
						break;
				}
				Y.event.preventDefault();
				return false;
			}
		});
	};

	/**
	 * 跳转到指定页面
	 * @param {integer} pageIndex
	 **/
	Y.widget.Pagination.prototype.jumpToPage = function(pageIndex){
		console.log('jumpToPage', pageIndex);
		if(this.config.pageTotal < pageIndex){
			pageIndex = this.config.pageTotal;
		}

		this.config.onPageSwitch(pageIndex, this.config.pageIndex);
		this.config.pageIndex = pageIndex;
		this.show();
	};

	Y.widget.Pagination.prototype.show = function() {
		if(!this.config.itemTotal){
			return;
		}

		this.config.pageTotal = Math.ceil(this.config.itemTotal / this.config.pageSize);
		var html = [];

		if(this.config.pageIndex > this.config.pageTotal){
			this.config.pageIndex = this.config.pageTotal;
		}

		//第一页
		if(this.config.pageIndex > 1){
			html.push('<a href="#page=1" class="'+this.config.cssClassName.first+'" rel="first">第一页</a>');
		} else {
			html.push('<span class="'+this.config.cssClassName.first+'">第一页</span>');
		}

		//前置...
		if(this.config.pageIndex - this.config.numOffset > 0){
			html.push('<span class="'+this.config.cssClassName.eclipse+'">...</span>');
		}

		//翻页数字
		for(var i=this.config.pageIndex; i<=(this.config.pageIndex+this.config.numOffset); i++){
			if(i>0 && i<=this.config.pageTotal){
				html.push((this.config.pageIndex != i) ? '<a href="#page='+i+'" class="'+this.config.cssClassName.num+'" rel="num">'+i+'</a>'
						: '<span class="'+this.config.cssClassName.num+'">'+i+'</span>');
			}
		}

		//后置...
		if(this.config.pageIndex + this.config.numOffset < this.config.pageTotal){
			html.push('<span class="'+this.config.cssClassName.eclipse+'">...</span>');
		}

		//下一页
		if(this.config.pageIndex < this.config.pageTotal){
			html.push('<a href="#page='+(this.config.pageIndex+1)+'" class="'+this.config.cssClassName.next+'" rel="next">下一页</a>');
		} else {
			html.push('<span class="'+this.config.cssClassName.next+'">下一页</span>');
		}

		this.config.container.setHtml(html.join(''));
	};
})(YSL);