
/**
 * YSL lang module
 * @param {Object} YSL
 */
(function(Y){
	var _LANG = {};

	/**
	 * trans collection to array
	 * @param {Object} coll, dom collection
	 */
	_LANG.toArray = function(col){
		if(col.item){
            var l = col.length, arr = new Array(l);
            while (l--) arr[l] = col[l];
            return arr;
        } else {
			var arr = [];
			for(var i=0; i<col.length; i++){
				arr[i] = col[i];
			}
			return arr;
		}
	};

	/**
	 * check item is in array
	 * @param  {mix} item
	 * @param  {array} arr
	 * @return {boolean}
	 */
	_LANG.inArray = function(item, arr){
		for(var i=arr.length-1; i>=0; i--){
			if(arr[i] == item){
				return true;
			}
		}
		return false;
	};

	/**
	 * check an object is an empty
	 * @param  {mix}  obj
	 * @return {Boolean}
	 */
	_LANG.isEmptyObject = function(obj){
		if(typeof(obj) == 'object'){
			for(var i in obj){
				if(i !== undefined){
					return false;
				}
			}
		}
		return true;
	};

	/**
	 * check object is plain object
	 * @param  {mix}  obj
	 * @return {Boolean}
	 */
	_LANG.isPlainObject = function(obj){
		return obj && toString.call(obj) === "[object Object]" && !obj["nodeType"] && !obj["setInterval"];
	};

	_LANG.isScalar = function(value){
		var type = _LANG.getType(value);
		return type == 'number' || type == 'boolean' || type == 'string' || type == 'null' || type == 'undefined';
	};

	/**
	 * 判断一个对象是否为一个DOM 或者 BOM
	 * @param {Mix} value
	 * @return {Boolean}
	 **/
	_LANG.isBomOrDom = function(value){
		if(this.isScalar(value)){
			return false;
		}
		if(Y.ua.ie){
			//Node, Event, Window
			return value['nodeType'] || value['srcElement'] || (value['top'] && value['top'] == Y.W.top);
		} else {
			return this.getType(value) != 'object' && this.getType(value) != 'function';
		}
	};

	/**
	 * check object is boolean
	 * @param  {mix}  obj
	 * @return {Boolean}
	 */
	_LANG.isBoolean = function(obj){
		return this.getType(obj) == 'boolean';
	};

	/**
	 * check object is a string
	 * @param  {mix}  obj
	 * @return {Boolean}
	 */
	_LANG.isString = function(obj){
		return this.getType(obj) == 'string';
	};

	/**
	 * check object is an array
	 * @param  {mix}  obj
	 * @return {Boolean}
	 */
	_LANG.isArray = function(obj){
		return this.getType(obj) == 'array';
	};

	/**
	 * check object is a function
	 * @param  {mix}  obj
	 * @return {Boolean}
	 */
	_LANG.isFunction = function(obj){
		 return this.getType(obj) == 'function';
	};

	/**
	 * get type
	 * @param  {mix} obj
	 * @return {string}
	 */
	_LANG.getType = function(obj){
		return obj === null ? 'null' : (obj === undefined ? 'undefined' : Object.prototype.toString.call(obj).slice(8, -1).toLowerCase());
	};

	/**
	 * Array each loop call
	 * @param  {[type]}   obj
	 * @param  {Function} callback
	 */
	_LANG.each = function(obj, callback){
		var value, i = 0,
			length = obj.length,
			isObj = (length === undefined) || (typeof (obj) == "function");
		if (isObj) {
			for (var name in obj) {
				if (callback.call(obj[name], obj[name], name, obj) === false) {
					break;
				}
			}
		} else {
			for (value = obj[0]; i < length && false !== callback.call(value, value, i, obj); value = obj[++i]) {}
		}
		return obj;
	};


	/**
	 * Class构造器
	 * @param {String} s 构造规则
	 * @param {Object} p 对象实体
	 **/
	_LANG.createClass = function(s, p) {
		var t = this, sp, ns, cn, scn, c, de = 0;

		//解析规则: <prefix> <class>:<super class>
		s = /^((static) )?([\w.]+)(\s*:\s*([\w.]+))?/.exec(s);
		cn = s[3].match(/(^|\.)(\w+)$/i)[2]; // Class name

		//创建命名空间
		ns = t.createNS(s[3].replace(/\.\w+$/, ''));

		//类存在
		if (ns[cn]){
			return;
		}

		//生成静态类
		if (s[2] == 'static') {
			ns[cn] = p;
			if (this.onCreate){
				this.onCreate(s[2], s[3], ns[cn]);
			}
			return;
		}

		//创建缺省构造原型类
		if (!p[cn]) {
			p[cn] = function(){};
			de = 1;
		}

		// Add constructor and methods
		ns[cn] = p[cn];
		t.extend(ns[cn].prototype, p);

		//扩展
		if (s[5]) {
			if(!t.resolve(s[5])){
				throw('ve.Class namespace parser error');
			}
			sp = t.resolve(s[5]).prototype;
			scn = s[5].match(/\.(\w+)$/i)[1]; // Class name

			// Extend constructor
			c = ns[cn];
			if (de) {
				// Add passthrough constructor
				ns[cn] = function() {
					return sp[scn].apply(this, arguments);
				};
			} else {
				// Add inherit constructor
				ns[cn] = function() {
					this.base = sp[scn];
					return c.apply(this, arguments);
				};
			}
			ns[cn].prototype[cn] = ns[cn];

			// Add super methods
			t.each(sp, function(f, n) {
				ns[cn].prototype[n] = sp[n];
			});

			// Add overridden methods
			t.each(p, function(f, n) {
				// Extend methods if needed
				if (sp[n]) {
					ns[cn].prototype[n] = function() {
						this.base = sp[n];
						return f.apply(this, arguments);
					};
				} else {
					if (n != cn){
						ns[cn].prototype[n] = f;
					}
				}
			});
		}

		// Add static methods
		t.each(p['static'], function(f, n) {
			ns[cn][n] = f;
		});
		if (this.onCreate){
			this.onCreate(s[2], s[3], ns[cn].prototype);
		}
	};

	/**
	 * 创建namespace
	 * @param {String} n name
	 * @param {Object} o scope
	 * @return {Object}
	 **/
	_LANG.createNS = function(n, o) {
		var i, v;
		o = o || Y.W;
		n = n.split('.');
		for (i=0; i<n.length; i++) {
			v = n[i];
			if (!o[v]){
				o[v] = {};
			}
			o = o[v];
		}
		return o;
	};

	/**
	 * 解析字符串对应到对象属性
	 * @param {String} n
	 * @param {Object} o
	 * @return {Mix}
	 **/
	_LANG.resolve = function(n, o) {
		var i, l;
		o = o || Y.W;
		n = n.split('.');

		for (i=0, l = n.length; i<l; i++) {
			o = o[n[i]];
			if (!o){
				break;
			}
		}
		return o;
	}

	Y.lang = _LANG;
})(YSL);