/**
 * YSL widget tab module
 */
(function(Y){
	Y.widget.tab = function(tb, ctn, e, cls){
		var tbs = Y.dom.one(tb) && Y.dom.one(tb).getChildren(),
			ctns = Y.dom.one(ctn) && Y.dom.one(ctn).getChildren(),
			e = e ? e : 'mouseover',
			cls = cls ? cls : 'current';
		Y.lang.each(tbs,
			function(obj, idx){
				obj.on(e, function(){
					Y.lang.each(tbs, function(obj, j){j == idx ? obj.addClass(cls) : obj.removeClass(cls);});
					Y.lang.each(ctns,function(obj, j){j == idx ? obj.addClass(cls) : obj.removeClass(cls);});
				});
			}
		);
	};
})(YSL);