(function(Y){
	Y.widget.helper = {
		select: function(tp, tag){
			var tag = tag || Y.dom.one(Y.D);
			Y.dom.one(tag).all('input:checkbox').each(function(_chk){
				var chk = _chk.getDomNode();
				chk.checked = tp=='all'?true:(tp =='none'?false:!chk.checked);
			});
		},

		/**
		 * setup placehover effect for form element
		 * @param {mix} el
		 * @param
		 */
		placeHolder: function(el, normalClass, focusClass, emptyClass){
			normalClass = normalClass || '';
			focusClass = focusClass || '';
			emptyClass = emptyClass || '';

			el = Y.dom.one(el);
			var phTxt = el.getAttr('placeholder');
			if(!phTxt){
				throw 'need placeholder attr';
				return;
			}

			el.on('focus', function(){
				el.removeClass(emptyClass);
				el.removeClass(normalClass);
				el.addClass(focusClass);
				if(el.getValue() == phTxt){
					el.setValue('');
				}
			});
			el.on('blur', function(){
				el.removeClass(emptyClass).removeClass(normalClass).removeClass(focusClass);
				if(el.getValue() == '' || el.getValue() == phTxt){
					el.setValue(phTxt);
					el.addClass(emptyClass);
				} else {
					el.addClass(normalClass);
				}
			});
		}
	};
})(YSL);