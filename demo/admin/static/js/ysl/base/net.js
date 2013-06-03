/**
 * YSL net module
 */
(function(Y){
	Y.net = {};
	var CACHE_DATA = {};

	/**
	 * 设置cache
	 * @param {String} key
	 * @param {Mix} data
	 * @param {Number} expired 过期时间（秒）
	 **/
	var setCache = function(key, data, expired){
		var expiredTime = (new Date()).getTime() + expired*1000;
		CACHE_DATA[key] = {data:data, expiredTime:expiredTime};
	};

	/**
	 * 获取cache
	 * @param {String} key
	 * @return {Mix}
	 **/
	var getCache = function(key){
		var time = new Date().getTime();
		if(CACHE_DATA[key] && CACHE_DATA[key].expired > time){
			return CACHE_DATA[key].data;
		} else {
			delete CACHE_DATA[key];
			return null;
		}
	};

	/**
	 * ajax请求组件
	 * @todo  组件暂时不处理跨域问题
	 **/
	Y.net.Ajax = (function(){
		/**
		 * 新建ajax组件对象
		 * @param {Object} config
		 **/
		var ajax = function(config){
			this.config = Y.object.extend(true, {
				url: null,			//请求url
				syn: false,			//是否为同步方法
				method: 'get',		//请求方法
				data: null,			//发送数据
				format: 'json',		//返回格式
				charset: 'utf-8',	//编码字符集
				cache: false		//是否cache
			}, config);

			if(!this.config.url){
				throw('NO REQUEST URL FOUND');
			}

			this.config.data = Y.net.buildParam(this.config.data);
			this.config.method = this.config.method.toLowerCase();
			this.config.format = this.config.format.toLowerCase();

			if (Y.W.XMLHttpRequest) {
				this.xmlObj = new XMLHttpRequest();
				if(this.xmlObj.overrideMimeType){
					this.xmlObj.overrideMimeType('text/xml');
				}
			} else if(Y.W.ActiveXObject){
				this.xmlObj = new ActiveXObject('Msxml2.XMLHTTP') || new ActiveXObject('Microsoft.XMLHTTP');
			} else {
				throw('browser no support ajax');
			}
		};

		/**
		 * 响应处理函数
		 * @param {Object} response
		 **/
		ajax.prototype.onResponse = function(response){
			if(!response || response.length == 0){
				this.onError();
				return;
			}

			var data = null;
			try {
				switch(this.config.format){
					case 'json' || 'javascript':
						eval('data = ' + response.responseText + ';');
						break;
					case 'xml':
						data = response.responseXML;
						break;
					case 'bool' || 'boolean':
						data = /yes|true|y/ig.test(ret.responseText)? true : false;
						break;
					default:
						data = response.responseText;
				}
			} catch(ex){}
			this.onResult(data);
		};

		/**
		 * 发送动作
		 **/
		ajax.prototype.send = function(){
			var _this = this;
			var cache_data = getCache(this.config.url);
			if(cache_data){
				this.onResponse(cache_data);
				return;
			}

			this.xmlObj.onreadystatechange = function(){
				if(_this.xmlObj.readyState == 4) {
					if(_this.xmlObj.status == 200){
						_this.onResponse(_this.xmlObj);
						setCache(_this.config.url, _this.xmlObj);
					} else {
						_this.onError(_this.xmlObj.status);
					}
				} else {
					if(_this.xmlObj.readyState == 0){
						_this.onReady();
					} else {
						_this.onLoading();
					}
				}
			};

			this.xmlObj.open(this.config.method, this.config.url, !this.config.syn);
			if(this.config.format == 'xml'){
				this.xmlObj.setRequestHeader('Content-Type','text/xml');
			} else {
				this.xmlObj.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset='+this.config.charset);
			}

			if(this.config.method == 'GET'){
				param = null;
			}

			this.xmlObj.send(this.config.data);

			if(this.config.syn && this.xmlObj && this.xmlObj.status == 200){
				return this.callback(this.xmlObj);
			}
		};

		//附属额外处理事件
		ajax.prototype.onLoading = function(){};
		ajax.prototype.onResult = function(){};
		ajax.prototype.onError = function(){};
		ajax.prototype.onReady = function(){};
		ajax.prototype.onTimeout = function(){};
		return ajax;
	})();

	/**
	 * get 数据
	 * @param string url
	 * @param object data
	 * @param Function callback
	 * @param object option
	**/
	Y.net.get = function(url, data, callback, option){
		callback = callback || Y.emptyFn;
		option = Y.object.extend(true, {url:url}, option || {});
		option.method = 'get';
		var ajax = new Y.net.Ajax(option);
		ajax.onResponse = callback;
		ajax.send();
	};

	/**
	 * post 数据
	 * @param string url
	 * @param object data
	 * @param Function callback
	 * @param object option
	 **/
	Y.net.post = function(url, data, callback, option){
		callback = callback || Y.emptyFn;
		option = Y.object.extend(true, {url:url}, option || {});
		option.method = 'post';
		var ajax = new Y.net.Ajax(option);
		ajax.onResponse = callback;
		ajax.send();
	};

	/**
	 * 加载css样式表
	 * @param {string} url
	 * @param {function} callback 这里可能不准确
	 * @param {dom} doc
	 */
	Y.net.loadCss = function(url, callback, doc){
		setTimeout(function(){
			doc = doc || Y.D;
			callback = callback || YSL.emptyFn;
			var css = doc.createElement('link');
			css.rel = 'stylesheet';
			css.type = 'text/css';
			css.href = url;
			doc.getElementsByTagName('head')[0].appendChild(css);
			css.onreadystatechange = css.onload = callback();
		}, 0);
	};

	/**
	 * 加载脚本
	 **/
	(function(net){
		var LOADING = false;
		var FILES_QUEUE = [];
		var FILES_LOAD_MAP = {};

		/**
		 * 检测批量文件是否全部加载完成
		 * @param  {Array} fileInfoList
		 * @return {Boolean}
		 */
		var checkLoaded = function(fileInfoList){
			var loaded = true;
			Y.lang.each(fileInfoList, function(fileInfo){
				if(!FILES_LOAD_MAP[fileInfo.src] ||  FILES_LOAD_MAP[fileInfo.src].status != 3){
					loaded = false;
					return false;
				}
			});
			return loaded;
		};

		/**
		 * 批量加载脚本
		 * @param  {Array} fileInfoList 文件列表信息
		 * @param  {Function} allDoneCb  全部文件加载完成回调
		 */
		var batchLoadScript = function(fileInfoList, allDoneCb){
			if(checkLoaded(fileInfoList)){
				allDoneCb();
				return;
			}

			updateListToQueue(fileInfoList, function(){
				if(checkLoaded(fileInfoList)){
					allDoneCb();
				}
			});

			if(!LOADING){
				loadQueue();
			}
		};

		/**
		 * 更新当前要加载的文件到加载队列中
		 * @param  {Array} fileInfoList
		 * @param {Function} 断续回调
		 */
		var updateListToQueue = function(fileInfoList, tickerCb){
			Y.lang.each(fileInfoList, function(fileInfo){
				if(FILES_LOAD_MAP[fileInfo.src]){
					if(FILES_LOAD_MAP[fileInfo.src].status == 1 || FILES_LOAD_MAP[fileInfo.src].status == 2){
						FILES_LOAD_MAP[fileInfo.src].callbacks.push(tickerCb);
					} else if(FILES_LOAD_MAP[fileInfo.src].status == 3){
						tickerCb();
					} else if(FILES_LOAD_MAP[fileInfo.src].status == 4){
						tickerCb(-1);
					}
				} else {
					FILES_QUEUE.push(fileInfo);
					FILES_LOAD_MAP[fileInfo.src] = {
						status: 1,
						callbacks: [tickerCb]
					};
				}
			});
		};

		/**
		 * 加载队列中的脚本
		 */
		var loadQueue = function(){
			if(FILES_QUEUE.length){
				LOADING = true;
				var fileInfo = FILES_QUEUE.shift();
				FILES_LOAD_MAP[fileInfo.src].status = 2;
				forceLoadScript(fileInfo, function(){
					FILES_LOAD_MAP[fileInfo.src].status = 3;
					Y.lang.each(FILES_LOAD_MAP[fileInfo.src].callbacks, function(cb){
						cb();
					});

					//[fix] 防止ie下面的readyState多执行一次导致这里的callback多次执行
					FILES_LOAD_MAP[fileInfo.src].callbacks = [];
					loadQueue();
				});
			} else {
				LOADING = false;
			}
		};

		/**
		 * 强制加载脚本
		 * @param  {Object|String} fileInfo 文件信息，详细配置参考函数内实现
		 * @param  {Function} sucCb
		 * @return {Boolean}
		 */
		var forceLoadScript = function(fileInfo, sucCb){
			var option = Y.object.extend(true, {
				src: null,			//文件src
				charset: 'utf-8',	//文件编码
				'window': window
			}, fileInfo);

			if(!option.src){
				return false;
			}

			var doc = option.window.document;
			var docMode = doc.documentMode;
			var s = doc.createElement('script');
			s.setAttribute('charset', option.charset);

			Y.event.add(s, Y.ua.ie && Y.ua.ie < 10 ? 'readystatechange': 'load', function(){
				if(Y.ua.ie && s.readyState != 'loaded' && s.readyState != 'complete'){
					return;
				}
				setTimeout(function(){
					sucCb();
				}, 0);

				/**
				if(!s || Y.ua.ie && Y.ua.ie < 10 && ((typeof docMode == 'undefined' || docMode < 10) ? (s.readyState != 'loaded') : (s.readyState != 'complete'))){
					return;
				}
				sucCb();
				**/
			});
			s.src = option.src;
			(doc.getElementsByTagName('head')[0] || doc.body).appendChild(s);
		};

		/**
		 * 加载脚本
		 * @param  {Mix}   arg1     文件信息，支持格式：str || {src:str} || [str1,str2] || [{src: str1}, {src: str2}]
		 * @param  {Function} callback
		 */
		var loadScript = function(arg1, callback){
			var list = [];
			if(typeof(arg1) == 'string'){
				list.push({src:arg1});
			} else if(arg1.length){
				Y.lang.each(arg1, function(item){
					if(typeof(item) == 'string'){
						list.push({src: item});
					} else {
						list.push(item);
					}
				});
			} else {
				list.push(arg1);
			}
			batchLoadScript(list, callback);
		};
		Y.net.loadScript = loadScript;
	})(Y.net);


	/**
	 * 合并后台cgi请求url
	 * @deprecated 该方法不支持前台文件hash链接生成，如果要
	 * @param {string} url
	 * @param {mix} get1
	 * @param {mix} get2...
	 * @return {String}
	 */
	Y.net.buildParam = function(/**params1, params2...*/){
		var fixType = function(val){
			return typeof(val) == 'string' || typeof(val) == 'number';
		};
		var args = Y.lang.toArray(arguments), data = [];

		Y.lang.each(args, function(params){
			if(Y.lang.isArray(params)){
				data.push(params.join('&'));
			} else if(typeof(params) == 'object'){
				for(var i in params){
					if(fixType(params[i])){
						data.push(i+'='+params[i]);
					}
				}
			} else if(typeof(params) == 'string') {
				data.push(params);
			}
		});
		return data.join('&').replace(/^[?|#|&]{0,1}(.*?)[?|#|&]{0,1}$/g, '$1');	//移除头尾的#&?
	};

	/**
	 * 合并参数
	 * @param {String} url
	 * @param {Mix..} params
	 * @return {String}
	 **/
	Y.net.mergeCgiUri = function(/**url, get1, get2...**/){
		var args = Y.lang.toArray(arguments);
		var url = args[0];
		url = url.replace(/(.*?)[?|#|&]{0,1}$/g, '$1');	//移除尾部的#&?
		args = args.slice(1);
		Y.lang.each(args, function(get){
			var str = Y.net.buildParam(get);
			if(str){
				url += (url.indexOf('?') >= 0 ? '&' : '?') + str;
			}
		});
		return url;
	};

	/**
	 * 合并cgi请求url
	 * @deprecated 该方法所生成的前台链接默认使用#hash传参，但如果提供的url里面包含？的话，则会使用queryString传参
	 * 所以如果需要使用?方式的话，可以在url最后补上?, 如：a.html?
	 * @param {string} url
	 * @param {mix} get1
	 * @param {mix} get2...
	 * @return {String}
	 */
	Y.net.mergeStaticUri = function(/**url, get1, get2...**/){
		var args = Y.lang.toArray(arguments);
		var url = args[0];
		args = args.slice(1);
		Y.lang.each(args, function(get){
			var str = Y.net.buildParam(get);
			if(str){
				url += /(\?|#|&)$/.test(url) ? '' : (/\?|#|&/.test(url) ? '&' : '#');
				url += str;
			}
		});
		return url;
	};

	/**
	 * get param
	 * @param  {string} param
	 * @return {string}
	 */
	Y.net.getParameter = function(param, url){
		var r = new RegExp("(\\?|#|&)"+param+"=([^&#]*)(&|#|$)");
	    var m = (url || location.href).match(r);
	    return (!m?"":m[2]);
	}
})(YSL);
