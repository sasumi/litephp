(function(Y){
	/**
	 * ua info
	 * ua.ie || ua.opera || ua.safari || ua.firefox || ua.chrome
	 * ua.ver
	 * @return {mix}
	 */
	var uas = Y.W.navigator.userAgent.toLowerCase();	//useragent full string
	var b = {
		ie: !!Y.W.ActiveXObject,
		opera: !!Y.W.opera && Y.W.opera.version,
		webkit: uas.indexOf(' applewebkit/')> -1,
		air: uas.indexOf(' adobeair/')>-1,
		quirks: Y.D.compatMode == 'BackCompat',
		safari: /webkit/.test(uas) && !/chrome/.test(uas),
		firefox: /firefox/.test(uas),
		chrome: /chrome/.test(uas),
		userAgent: uas
	};

	var k = '';
	for (var i in b) {
		if(b[i]){ k = 'safari' == i ? 'version' : i; break; }
	}
	b.ver = k && RegExp("(?:" + k + ")[\\/: ]([\\d.]+)").test(uas) ? RegExp.$1 : "0";
	b.ver = b.ie ? parseInt(b.ver,10) : b.ver;

	//IE兼容模式检测
	if(b.ie){
		b.ie8Compat = Y.D.documentMode == 8;
		b.ie7Compat = (b.ie == 7 && !Y.D.documentMode) || Y.D.documentMode == 7;
		b.ie6Compat = b.ie < 7 && b.quirks;
	}

	if(b.ie && b.ver == 9){
		b.ver = Y.D.addEventListener ? 9 : 8;
	}
	if(b.ie){
		b['ie'+b.ver] = true;
	}
	b.isIE = function(v){
		return b.ie == v;
	};

	Y.ua = b;
})(YSL);