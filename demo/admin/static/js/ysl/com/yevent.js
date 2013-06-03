(function(Y){
	var YEvent = function(){
		this._prevList = [];
		this._midList = [];
		this._lastList = [];
	};

	YEvent.prototype = {
		/**
		 * 添加事件
		 * @param {Function} fn
		 * @param {Boolean} usePipe 是否使用管道（变量引用传递）
		 * @param {Number} pos 添加位置 -1, 1, *
		 **/
		add: function(fn, usePipe, pos){
			var item = {
				fn: fn,
				rec: usePipe
			};

			switch(pos){
				case -1:
					this._prevList.unshift(item);
					break;
				case 1:
					this._lastList.push(item);
					break;
				default:
					this._midList.push(item);
			}
		},

		/**
		 * 添加在开始
		 * @param {Function} fn
		 * @param {Boolean} usePipe 是否使用管道（变量引用传递）
		 **/
		addFirst: function(fn, usePipe){
			return this.add(fn, usePipe, -1);
		},

		/**
		 * 添加在尾部
		 * @param {Function} fn
		 * @param {Boolean} usePipe 是否使用管道（变量引用传递）
		**/
		addLast: function(fn, usePipe){
			return this.add(fn, usePipe, 1);
		},

		/**
		 * 移除指定事件
		 * @return {Boolean}
		 **/
		remove: function(fn){
			var _this = this;
			var found;

			Y.lang.each(this._prevList, function(item, i) {
				if(item.fn == fn){
					_this._prevList.splice(i, 1);
					found = true;
					return false;
				}
			});

			Y.lang.each(this._midList, function(item, i) {
				if(item.fn == fn){
					_this._midList.splice(i, 1);
					found = true;
					return false;
				}
			});

			Y.lang.each(this._lastList, function(item, i) {
				if(item.fn == fn){
					_this._lastList.splice(i, 1);
					found = true;
					return false;
				}
			});
			return found;
		},

		/**
		 * 获取实际顺序的事件数组
		 * @return {Array}
		 **/
		_getList: function(){
			return this._prevList.concat(this._midList).concat(this._lastList);
		},

		/**
		 * 触发事件
		 * 传入参数如果 > 2个，返回值为array，否则返回的为 1个，
		 * 如果事件列表里面函数返回值为false，将中断后续事件触发！！
		 * 如果事件列表里面有usePipe方法，函数必须根据fire的个数来返回相应的格式，如如果fire > 2个，返回array
		 * 否则返回1个，不依照这个规则返回，将可能扰乱整个Event后续方法的正确执行
		 * 整体fire返回最后一个函数处理结果
		 * @param {Arg0} scope
		 * @param {Arg1+} params
		 * @return {Mix}
		 **/
		fire: function(){
			var scope = arguments[0] || this,
				evList = this._getList(),
				arg,
				ret,
				fnRet,
				retIsArray;

			if(arguments.length > 2){
				retIsArray = true;
				arg = Y.lang.toArray(arguments, 1);
			} else {
				arg = arguments[1];
			}

			ret = arg;
			Y.lang.each(evList, function (item) {
				if(retIsArray){
					fnRet = item.fn.apply(scope, Y.lang.extend(true, ret));	//去除参数引用，这里不需要检测ret格式，因为下面throw里面检测了
				} else {
					var _p = !Y.lang.isBomOrDom(ret) && typeof(ret) == 'object' ? Y.lang.extend(true, ret) : ret;
					fnRet = item.fn.call(scope, _p);
				}

				if(item.rec){
					if((retIsArray && Y.lang.isArray(fnRet)) || (!retIsArray && fnRet !== undefined)){
						ret = fnRet;
					} else {
						throw('FUNCTION RETURN FORMAT NOT AVERIBLE');
					}
				}
				if(fnRet === false){
					console.log('当前操作取消后续事件触发', item);
				}
				return fnRet;
			});
			return fnRet;
		}
	}
	Y.com.YEvent = YEvent;
})(YSL);