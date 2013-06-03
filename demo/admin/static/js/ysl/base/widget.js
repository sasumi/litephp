(function(Y) {
	var TOP_WIN = Y.TOP_WIN;
	var TOP_DOC = Y.TOP_DOC;
	var TOP_YSL = Y.TOP.YSL;

	Y.widget = {
		register: function(name, obj, opt){
			var opt = Y.object.extend(true, {
				ver: 1,
				win, TOP_WIN
			}, opt);
		},
		unRegister: function(name, obj, opt){

		},
		get: function(name, opt){

		}
	};
})(YSL);