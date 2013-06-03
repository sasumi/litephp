(function(Y){
	var _String = {}
	
	/**
	 * convert string to ascii code
	 * @param {string} str
	 * @return {string}
	 */
	_String.str2asc = function(str){
		return str.charCodeAt(0).toString(16);
	};
	
	/**
	 * repeat string 
	 * @param {string} str
	 * @param {integer} n
	 * @return {string}
	 */
	_String.repeat = function(str, n){
		if(n>1){
			return new Array(n+1).join(str);
		}
		return str;
	};
	
	/**
	 * string trim
	 * @param {integer} iSide, 0:both, 1:left, 2:right
	 * @return {string}
	 */
	_String.trim = function(str, charlist){
		var whitespace, l = 0,
			i = 0;
		str += '';
	 
		if (!charlist) {
			whitespace = " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
		} else {
			charlist += '';
			whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
		}
	 
		l = str.length;
		for (i = 0; i < l; i++) {
			if (whitespace.indexOf(str.charAt(i)) === -1) {
				str = str.substring(i);
				break;
			}
		}
	 
		l = str.length;
		for (i = l - 1; i >= 0; i--) {
			if (whitespace.indexOf(str.charAt(i)) === -1) {
				str = str.substring(0, i + 1);
				break;
			}
		}
		return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
	};
	

	/**
	 * 解析str到obj
	 * @param {String} str
	 * @return {Object}
	**/
	_String.parseRequestStringToObjParam = function(str){
		if(typeof(str) != 'string' || !str){
			return str;
		}
		var data = {}, tmp, list = str.split('&');
		for(var i=0, len=list.length; i<len; ++i){
			tmp = list[i].split('=');
			data[tmp[0]] = tmp[1];
		}
		return data;
	};

	
	/**
	 * remove ubb code
	 * @param  {string} s source string
	 * @return {string}
	 */
	_String.removeUbb = function (s) {
        s = s.replace(/\[em\]e(\d{1,3})\[\/em\]/g, "");
        s = s.replace(/\[(img)\].*\[\/img\]/ig, "");
        s = s.replace(/\[(flash)\].*\[\/flash\]/ig, "");
        s = s.replace(/\[(video)\].*\[\/video\]/ig, "");
        s = s.replace(/\[(audio)\].*\[\/audio\]/ig, "");
        s = s.replace(/&nbsp;/g, "");
        s = s.replace(/\[\/?(b|url|img|flash|video|audio|ftc|ffg|fts|ft|email|center|u|i|marque|m|r|quote)[^\]]*\]/ig, "");
        return s;
    };
	
	/**
	 * convert ascii code to str
	 * @param {string} str
	 * @param {string}
	 */
	_String.asc2str = function(str){
		return String.fromCharCode(str);
	};

	/**
	 * encode str to ascii code
	 * @param {string} str
	 * @return {string}
	 */
	_String.ansiEncode = function(str){
		var ret = '',
			strSpecial = "!\"#$%&’()*+,/:;<=>?[]^`{|}~%",
			tt = "";
	    for(var i = 0; i < str.length; i++) {
	        var chr = str.charAt(i);
	        var c = _String.str2asc(chr);
	        tt += chr + ":" + c + "n";
	        if (parseInt("0x" + c) > 0x7f) {
	            ret += "%"+c.slice(0,2)+"%"+c.slice(-2);
	        } else {
	            if (chr == " ") {
					ret += "+";
				} else {
					ret += (strSpecial.indexOf(chr) != -1) ? ("%" + c.toString(16)) : chr;
				}
	        }
	    }
	    return ret;
	}

	/**
	 * decode ascii string
	 * @param {string} str
	 * @return {string}
	 */
	_String.ansiDecode = function(str){
		var ret = "";
	    for(var i=0; i<str.length; i++) {
	        var chr = str.charAt(i);
	        if(chr == "+") {
				ret += " ";
	        } else if(chr == "%") {
				var asc = str.substring(i + 1, i + 3);
				if (parseInt("0x" + asc) > 0x7f) {
					ret += _String.asc2str(parseInt("0x" + asc + str.substring(i + 4, i + 6)));
					i += 5;
				} else {
					ret += _String.asc2str(parseInt("0x" + asc));
					i += 2;
	            }
			} else {
				ret += chr;
			}
		}
	    return ret;
	}
	Y.string = _String;
})(YSL);
