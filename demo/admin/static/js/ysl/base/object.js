(function(Y){
	var PROTOTYPE_FIELDS = [
		'constructor',
		'hasOwnProperty',
		'isPrototypeOf',
		'propertyIsEnumerable',
		'toLocaleString',
		'toString',
		'prototype',
		'valueOf'];

	var _Object = {
		/**
		 * bind function to obj
		 * @param {Object} obj
		 * @param {Function} fn
		 * @return {Function}
		 */
		bind: function(obj, fn){
			var slice = Array.prototype.slice;
			var args = slice.call(arguments, 2);
			return function(){
				var _obj = obj || this, _args = args.concat(slice.call(arguments, 0));
				if (typeof(fn) == 'string') {
					if (_obj[fn]) {
						return _obj[fn].apply(_obj, _args);
					}
				} else {
					return fn.apply(_obj, _args);
				}
			};
		},

		/**
		 * object router
		 * @param  {object} obj
		 * @param  {string} path path description, seperated by / or .
		 * @return {object}
		 */
		route: function(obj, path){
			obj = obj || {};
			path = String(path);
			var r = /([\d\w_]+)/g,
				m;
			r.lastIndex = 0;
			while ((m = r.exec(path)) !== null) {
				obj = obj[m[0]];
				if (obj === undefined || obj === null) {
					break;
				}
			}
			return obj;
		},

		/**
		 * 扩展object
		 * 不支持BOM || DOM || EY.t等浏览器对象，遇到此情况返回前一个
		 * 如果第一个参数为boolean，true用于表示为deepCopy，剩下参数做剩余的extend
		 * undefined,null 不可覆盖其他类型，类型覆盖以后面的数据类型为高优先级
		 * 标量直接覆盖，不做extend
		 * @return {Mix}
		 **/
		extend: function(/*true || obj, obj1[,obj2[,obj3]]**/){
			if(arguments.length < 2){
				throw('params error');
			}

			var args = Y.lang.toArray(arguments),
				result,
				deepCopy = false;

			if(Y.lang.getType(args[0]) == 'boolean'){
				deepCopy = args[0];
				args = args.slice(1);
			}

			result = args.pop();
			for(var i=args.length-1; i>=0; i--){
				var current = args[i];
				var _tagType = Y.lang.getType(result);

				//修正 object, null 情况
				if(_tagType == 'null' || _tagType == 'undefined'){
					result = current;
					continue;
				}

				//标量 || DOM || BOM 不做复制
				if(Y.lang.isScalar(result) || Y.lang.isBomOrDom(result)){
					continue;
				}

				//正常object、array, function复制
				for(var key in result){
					var item = result[key];
					if(deepCopy && typeof(item) == 'object'){
						current[key] = this.extend(false, item);	//这里仅处理当前仅支持两层处理
					} else {
						current[key] = item;
					}
				}

				//原型链复制
				for(var j=0; j<PROTOTYPE_FIELDS.length; j++){
					key = PROTOTYPE_FIELDS[j];
					if(Object.prototype.hasOwnProperty.call(result, key)){
						current[key] = result[key];
					}
				}
				result = current;
			}
			return result;
		}
	};

	Y.object = _Object;
})(YSL);
