YSL.use('com.cookie', function(Y){
	//统计COOKIE KEY
	var ACT_STATE_COOKIE_KEY = 'ACT_STATE_COOKIE';

	/**
	 * Action static function
	 * @param string name 统计名称
	 * @param string path 统计路径
	 * @param string sendMode 发送模式，当前仅支持cookie模式和非cookie模式
	 * @param float rate 统计抽样率，取值0-1
	 */
	var ActState = function(name, path, sendMode, rate){
		if(sendMode == 'cookie'){
			var oriStr = Y.cookie.get(ACT_STATE_COOKIE_KEY) || '';
				oriStr += '||'+name+':'+path+':'+rate;
			Y.cookie.set(ACT_STATE_COOKIE_KEY, oriStr);
		} else {
			ActState.send([[name, path, rate]]);
		}
	};

	/**
	 * 页面cookie自动统计方法
	 **/
	ActState.runtime = function(){
		var stateStr = Y.cookie.get(ACT_STATE_COOKIE_KEY);
		if(stateStr){
			var stateArr = [];
			var arr = stateStr.slice('||');
			for(var i=0; i<arr.length; i++){
				var tmp = arr[i].slice(':');
				stateArr.push(tmp[0],tmp[1],tmp[2]);
			}
			ActState.send(stateArr);
		}
	};

	/**
	 * 发送统计
	 * @param array stateArr 统计数据数组
	 * @param function callback
	 **/
	ActState.send = function(stateArr, callback){
		//这里要处理批量方法，需要根据后台的CGI格式进行定义
		for(var i=0; i<stateArr.length; i++){

		}
	};

	/**
	 * ActState 批量绑定方法
	 * @param {object} tagHash 绑定hash，结构如 {'.domselector':'statepath/hello'}
	 * @param {object} option 选项，具体定义请参考函数内相应注释
	 **/
	ActState.bindBatch = function(tagHash, option){
		option = Y.object.extend({
			'eventType': 'mousedown',		//统计事件类型，推荐使用mousedown
			'path': null,					//统计路径
			'nested': false,				//绑定的hash是否冒泡，默认为否
			'rate': 1,						//统计抽样率
			'prepare': true,				//是否采用预置绑定
			'sendMode': 'cookie',			//统计方式，默认使用cookie模式（针对页面无上一级页面情况）
			'container': document.body,		//事件绑定父级容器
			'domQueryCache': false			//是否针对选择器进行cache
		}, (option || {}));
		if (!option.path) {
			throw "ACTSTATE PATH REQUIRED";
		}
		if (option.prepare) {
			var _cache = {};
			Y.event.on(option.container, option.eventType, function (e) {
				var _eventTag = Y.event.getTarget(e).getDomNode();
				for (var tag in tagHash) {
					if ((!_cache[tag] && _cache[tag] === undefined) || option.domQueryCache) {
						_cache[tag] = Y.dom.one(tag, option.container).getDomNode();
					}
					if (_cache[tag] && _cache[tag].length) {
						for (var i = 0; i < _cache[tag].length; i++) {
							if (_cache[tag][i] == _eventTag || Y.dom.isAncestor(_cache[tag][i], _eventTag)) {
								ActState(tagHash[tag], option.url, option.sendMode, option.rate);
								if (!option.nested) {
									break;
								}
							}
						}
					}
				}
			});
		} else {
			for (var tag in tagHash) {
				var eles = Y.dom.one(tag).getDomNode();
				if (eles && eles.length) {
					for (var i = 0; i < eles.length; i++) {
						Y.event.on(eles[i], option.eventType, function () {
							ActState(tagHash[tag], option.url, option.sendMode, option.domain);
						});
					}
				}
			}
		}
	};

	if(!Y.App){Y.App = {};}
	ActState.runtime();
	Y.widget.ActState = ActState;
});