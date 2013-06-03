(function(Y){
	/**
	 * 特殊对象直接命中
	 * @param  {string} selector
	 * @return {[type]}          [description]
	 */
	var _fix = function(selector){
		selector = selector.toLowerCase();
		if(/^(body|head|html)$/.test(selector)){
			return Y.D.getElementsByTagName(selector)[0];
		} else if(selector == 'window'){
			return Y.W;
		} else if(selector == 'document'){
			return Y.D;
		}
		return '';
	}

	//使用浏览器原生selector
	if(Y.D.querySelector && Y.D.querySelectorAll){
		Y.querySelector = function(selector, context){
			var sp = _fix(selector);
			if(sp){
				return sp;
			}
			var dom = context || Y.D;
			return dom.querySelector(selector);
		};
		Y.querySelectorAll = function(selector, context){
			var sp = _fix(selector);
			if(sp){
				return sp;
			}
			var dom = context || Y.D;
			return dom.querySelectorAll(selector);
		};
		return;
	}

	/**
	 * DOM选择器
	 * @description 当前版本仅支持一下几种查询格式
	 * <code>
		tag = [--  rule $1    --][-- rule $2 --][-- relation simbol --][-- rule $1 --]...
		tag = object + property ||   limitor           >             		non-ID
		rule $1:		#div || span.class || .class <==> *.class || *
		rule $2:		[attribute_name = value] || :first || :last
		rule$2.1: 	:input || :text || :password || :radio || :checkbox || :submit || :reset
		</code>
	 * @param {Object} select	选择器
	 * @param {Object} context	上下文环境
	 * @param {Strong} cond	层级关系 > 或者所有
	 */
	var getElement = function(selector, context, cond){
		var result_lst = [],
			matches = [],
			context = context || Y.D,
			cond = cond || ' ';

		if(typeof(selector) !== 'string'){
			return selector;
		}

		//特殊对象直接命中
		if(selector == 'body' || selector == 'head' || selector == 'html'){
			return Y.D.getElementsByTagName(selector);
		} else if(selector == 'window'){
			return [Y.W];
		}

		//选择器清理
		selector = selector.replace(/(^\s*)|(\s*$)/g, '').replace(/\s+/ig, ' ').replace(/\s*\>\s*/ig, '>');

		//子模式数组分解
		matches = selector.match(/([^\s|^\>]+)|([\s|\>]+)/ig);

		//ID命中
		if(matches.length == 1 && matches[0].charAt(0) == '#'){
			return [Y.D.getElementById(matches[0].substring(1))];
		}

		//单模式命中
		else if(matches.length == 1){
			var context_lst = context.nodeType ? [context] : context;
			var tmp = [],
				tmp2 = [],
				att = null,
				pseudo = null,
				node_tags = matches[0].match(/^\w+/ig),
				node_tag = node_tags ? node_tags[0] : null;


			for (var i = 0; i < context_lst.length; i++) {
				var sub_result_lst = [];

				//父子关系
				if(cond == '>'){
					Y.lang.each(context_lst[i].childNodes, function(node){
						node_tag? (node.nodeName.toLowerCase() == node_tag && sub_result_lst.push(node)): sub_result_lst.push(node);
					});
				}

				//所有等级关系
				else {
					//selector specified || get all children
					sub_result_lst = node_tag ? Y.lang.toArray(context_lst[i].getElementsByTagName(node_tag))
						:sub_result_lst = sub_result_lst.concat(Y.lang.toArray(context_lst[i].getElementsByTagName('*')));
				}


				//类名过滤
				if (tmp = matches[0].match(/\.([A-Za-z0-9_-]+)/i)) {
					tmp2 = [];
					Y.lang.each(sub_result_lst, function(itm, idx){
						('  '+itm.className+' ').indexOf(' '+tmp[1]+' ') > 0 && tmp2.push(itm);
					});
					sub_result_lst = tmp2;
				}

				//属性过滤 [attribute_name=attribute_value]
				if (att = matches[0].match(/\[(.*?)\=(.*?)\]/i)) {
					tmp2 = [];
					Y.lang.each(sub_result_lst, function(itm){(itm.getAttribute(att[1])==(att[2].replace(/\'/ig,''))) && tmp2.push(itm);});
					sub_result_lst = tmp2;
				}

				//选择器过滤text,textarea,radio,checkbox,password,submit,reset
				//匹配  E[type = *]
				if(pseudo = matches[0].match(/\:(text|textarea|radio|checkbox|password|submit|reset)/i)){
					tmp2 = [];
					Y.lang.each(sub_result_lst, function(itm){(itm.type == pseudo[1]) && tmp2.push(itm);});
					sub_result_lst = tmp2;
					sub_result_lst = tmp2;
				}
				result_lst = result_lst.concat(sub_result_lst);
			}

			//伪类匹配 :first, :last, :even, :odd
			if(pseudo = matches[0].match(/\:(first|last|even|odd)/i)){
				switch(pseudo[1]){
					case 'first':
						result_lst = [result_lst[0]];
						break;
					case 'last':
						result_lst = [result_lst.pop()];
						break;
					case 'even':
						tmp2 = [];
						Y.lang.each(result_lst, function(itm, idx){idx%2 && tmp2.push(itm);});
						result_lst = tmp2;
						break;
					case 'odd':
						tmp2 = [];
						Y.lang.each(result_lst, function(itm, idx){idx%2 || tmp2.push(itm);});
						result_lst = tmp2;
						break;
				}
			}
		}

		//多模式命中（递归）
		else {
			for(var i=0; i<matches.length; i++){
				if(matches[i] != ' ' && matches[i] != '>'){
					if(i == 0){
						var _m = matches;
						result_lst = getElement(matches[i]);
						matches = _m;
					} else {
						if(!result_lst){return null;}
						var _m = matches;
						result_lst = getElement(matches[i], result_lst, matches[i-1]);
						matches = _m;
					}
				}
			}
		}
		return result_lst;
	};

	Y.querySelector = function(selector){
		var sp = _fix(selector);
		if(sp){
			return sp;
		}
		var arr = getElement.apply(null, arguments)[0];
		return arr;
	};

	Y.querySelectorAll = function(selector){
		var sp = _fix(selector);
		if(sp){
			return [sp];
		}
		return getElement.apply(null, arguments);
	}
})(YSL);