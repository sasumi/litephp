(function(Y){
	var TIP_CONTAINER = null;

	/**
	 * Show Tips
	 * @param {Mix} arg1
	 * @param {Integer} wtype
	 * @param {Integer} time
	 * @param {Function} closeCallback
	 */
	var Tip = function(arg1, wtype, time, closeCalllback){
		this.container = TIP_CONTAINER;
		var cfg = arg1;
		if(typeof(arg1) == 'string'){
			cfg = {
				'msg': arg1,
				'type': wtype || 0,
				'time': (time > 0 ? time*1000 : 2000)
			};
		}
		//extend default message config
		this.config = Y.object.extend({
			'msg': '',
			'type': 0,
			'time': 2000,
			'auto': true,
			'callback': closeCalllback
		}, cfg);

		//auto
		if(this.config.auto){
			this.show();
			if(this.config.time){
				setTimeout(Y.object.bind(this,function(){
					this.hide();
				}), this.config.time);
			}
		}
	};

	/**
	 * show tip
	 */
	Tip.prototype.show = function(){
		if(!this.container){
			this.container = TIP_CONTAINER = Y.dom.create('div').addClass('ysl-tip-container-wrap');
		}
		var html = ([
			'<span class="ysl-tip-container">',
				'<span class="ysl-tip-icon-',this.config.type,'"></span>',
				'<span class="ysl-tip-content">',this.config.msg,'</span>',
			'</div>'
		]).join('');

		//ie6 位置修正
		if(Y.ua.ie6){
			var viewP = Y.dom.getWindowRegion();
			this.container.setStyle('top',viewP.visibleHeight /2 + viewP.verticalScroll);
		}
		this.container.setHtml(html).show();
	};

	/**
	 * hide tip
	 */
	Tip.prototype.hide = function(){
		if(this.container){
			this.container.hide();
			this.config.callback && this.config.callback(this);
		}
	};

	/**
	 * hide all tip
	 */
	Tip.closeAll = function(){
		if(TIP_CONTAINER){
			TIP_CONTAINER.hide();
		}
	}

	/**
	 * destory tip container
	 */
	Tip.prototype.destory = function(){
		this.container.remove();
	};

	Y.widget.Tip = Tip;
})(YSL);