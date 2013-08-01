YSL.use('widget.Tip', function(Y, Tip){
	var TICK_CLASS = 'tick-on';
	Y.dom.one('.quick-index-frm').delegate('a', 'click', function(e){
		Y.event.preventDefault(e);
		var tk = this.existClass(TICK_CLASS);
		if(this.getAttr('rel') == 'reset-col'){
			this.parent().all('a').removeClass(TICK_CLASS);
			this[tk?'removeClass' : 'addClass'](TICK_CLASS);
		} else {
			this.parent().one('a[rel=reset-col]').removeClass(TICK_CLASS);
			this.toggleClass(TICK_CLASS);
		}
	});
});