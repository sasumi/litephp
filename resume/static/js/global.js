(function(Y){
	/**
	 * auto patch form which has attribute 'data-trans=asycn' submit event
	 * with async method.
	 * adding parameter: transtype=async to the backend
	 * @deprecated all forms use one iframe submit. so, only one request in one time
	 */
	var ifr;
	if(!(ifr = Y.dom.one('#data-trans-id'))){
		var div = Y.dom.create('div',-1).hide();
		div.setHtml('<iframe frameborder="0" id="data-trans-id" name="data-trans-id" data-ready="1"></iframe>');
		ifr = Y.dom.one('iframe', div);
		ifr.on('load', function(){
			this.setAttr('data-ready', '1');
		});
	};

	Y.dom.all('form[data-trans=async]').each(function(form){
		form.create('input', -1, {
			type: 'hidden',
			name: 'transtype',
			value: 'async'
		});

		form.setAttr('target', 'data-trans-id');
		form.on('submit', function(){
			var ready = ifr.getAttr('data-ready') == '1';
			if(ready){
				ifr.setAttr('data-ready', '2');
				return true;
			}
			Y.event.preventDefault();
		});
	});
})(YSL);