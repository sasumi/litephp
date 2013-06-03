/**
 * YSL cookie module
 */
(function(Y){
	var _Cookie = {
		/**
		 * create a cookie item
		 * @param  {string} key cookie key
		 * @param  {string} val cookie value
		 * @param  {integer} h   time
		 */
		create: function(key, val, h){
			if (h) {
				var date = new Date();
		        date.setTime(date.getTime() + (h * 60 * 60 * 1000));
		        var expires = "; expires=" + date.toGMTString();
		    } else {
		    	var expires = '';
		    }
		    Y.D.cookie = key + "=" + val + expires + "; path=/";
		},

		/**
		 * read cookie
		 * @param  {string} key cookie key
		 * @return {mix}
		 */
		read: function(key){
			var n = key + '=';
			var ca = Y.D.cookie.split(';');
		    for (var i = 0; i < ca.length; i++) {
		        var c = ca[i];
		        while (c.charAt(0) == ' '){
		        	c = c.substring(1, c.length);
		        }
		        if (c.indexOf(n) == 0) {
		        	return c.substring(n.length, c.length);
		        }
		    }
		    return null;
		},

		/**
		 * remove cookie
		 * @param  {string} key cookie key
		 */
		remove: function(key){
			this.create(key, '', -1);
		}
	};

	Y.com.cookie = _Cookie;
})(YSL);
