(function(Y){
	Y.dom.delegate('a[rel=pop]', 'click', function(){
		Y.event.preventDefault();
		var _title = this.getAttr('title');
		var _href = this.getAttr('href');
		Y.use('widget.Popup', function(Y, Pop){
			var pop = new Pop({
				title: _title,
				content: {src: _href},
				width:400
			});
			pop.show();
		});
	});
})(YSL);