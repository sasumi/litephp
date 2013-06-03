/**
 * YSL dom module
 * @include lang.js
 * @include core.js
 * @param {Object} YSL
 */
(function(Y){
	Y.dom = {};

	/**
	 * get scroll top
	 * @return {Object}
	 */
	Y.dom.getScroll= function(){
		var DE = Y.D.documentElement, BD = Y.D.body;
		return {
			top: Math.max(DE.scrollTop, BD.scrollTop),
			left: Math.max(DE.scrollLeft, BD.scrollLeft),
			height: Math.max(DE.scrollHeight, BD.scrollHeight),
			width: Math.max(DE.scrollWidth, BD.scrollWidth)
		};
	}

	/**
	 * insert stylesheet
	 * @param {string} rules
	 * @param {string} styleSheetID
	 * @return {DOM Element}
	 * @deprecate Y.dom.insertStyleSheet('* {margin:0;}');
	 */
	Y.dom.insertStyleSheet = function (rules, styleSheetID) {
		styleSheetID = styleSheetID || Y.guid();
		var node = Y.dom.one('#'+styleSheetID);
		if(!node){
			node = Y.dom.one('head').create('style').setAttr({id:styleSheetID, type:'text/css'});
		}

		node = node.getDomNode();
		if(node.styleSheet){
			node.styleSheet.cssText = rules;
		} else {
			 node.appendChild(Y.D.createTextNode(rules));
		}
		return node;
	};

	/**
	 * get window region info
	 * @return {Object}
	 */
	Y.dom.getWindowRegion = function(){
		var info = {};
		info.screenLeft = Y.W.screenLeft ? Y.W.screenLeft : Y.W.screenX;
		info.screenTop = Y.W.screenTop ? Y.W.screenTop : Y.W.screenY;

		//no ie
		if(Y.W.innerWidth){
			info.visibleWidth = Y.W.innerWidth;
			info.visibleHeight = Y.W.innerHeight;
			info.horizenScroll = Y.W.pageXOffset;
			info.verticalScroll = Y.W.pageYOffset;
		} else {
			//IE + DOCTYPE defined || IE4, IE5, IE6+no DOCTYPE
			var tag = (Y.D.documentElement && Y.D.documentElement.clientWidth) ?
				Y.D.documentElement : Y.D.body;
			info.visibleWidth = tag.clientWidth;
			info.visibleHeight = tag.clientHeight;
			info.horizenScroll = tag.scrollLeft;
			info.verticalScroll = tag.scrollTop;
		}

		var tag = (Y.D.documentElement && Y.D.documentElement.scrollWidth) ?
				Y.D.documentElement : Y.D.body;
		info.documentWidth = Math.max(tag.scrollWidth, info.visibleWidth);
		info.documentHeight = Math.max(tag.scrollHeight, info.visibleHeight);
		return info;
	}

	/**
	 * base dom class
	 */
	function _DOM(node){
		this._node = node;
	}

	/**
	 * get current dom node
	 * @return {Object}
	 */
	_DOM.prototype.getDomNode = function(){
		return this._node;
	}

	/**
	 * set current dom node
	 * @param {Object} node
	 */
	_DOM.prototype.setDomNode = function(node){
		this._node = node;
	}

	/**
	 * 检测两个节点是否相同
	 **/
	_DOM.prototype.equal = function(tag){
		return this.getDomNode() == tag.getDomNode();
	};

	/**
	 * add css class
	 * @param {String} cls
	 * @return {Object}
	 */
	_DOM.prototype.addClass = function(cs){
		var tmp = [], _this = this;
		Y.lang.each(cs.split(' '), function(c){
			if(Y.string.trim(c) && !_this.existClass(c)){
				tmp.push(c);
			}
		});
		if(tmp.length){
			this.getDomNode().className += ' ' + tmp.join(' ');
		}
		return this;
	}

	/**
	 * check exist css classes
	 * @param {String} cs
	 * @return {Boolean}
	 */
	_DOM.prototype.existClass = function(cs){
		var exist = true;
		var cc = this.getDomNode().className;
		Y.lang.each(cs.split(' '), function(c){
			c = Y.string.trim(c);
			if(c && !(new RegExp('(\\s|^)' + c + '(\\s|$)')).test(cc)){
				exist = false;
				return false;
			}
		});
		return exist;
	}

	/**
	 * remove css classes
	 * @param {String} cls
	 * @return {Object}
	 */
	_DOM.prototype.removeClass = function(cs){
		var n = this.getDomNode(),
			cc = n.className,
			_this = this;
		Y.lang.each(cs.split(' '), function(c){
			c = Y.string.trim(c);
			if(_this.existClass(c)){
				var reg = new RegExp('(\\s|^)' + c + '(\\s|$)');
				cc = cc.replace(c, '');
			}
		});
		n.className = cc;
		return this;
	}

	/**
	 * toggle two css class
	 * @param {String} cls1
	 * @param {String} cls2
	 * @return {Boolean} toggle result
	 */
	_DOM.prototype.toggleClass = function(cls1, cls2){
		if(this.existClass(cls1)){
			this.removeClass(cls1).addClass(cls2);
			return true;
		} else {
			this.addClass(cls1).removeClass(cls2);
			return false;
		}
	}

	/**
	 * get html
	 * @return {String}
	 */
	_DOM.prototype.getHtml = function(){
		return this.getDomNode().innerHTML;
	}

	/**
	 * set html
	 * @param {String} s
	 * @return {Object}
	 */
	_DOM.prototype.setHtml = function(s){
		this.getDomNode().innerHTML = s;
		return this;
	}


	/**
	 * get value
	 * @return {String}
	 */
	_DOM.prototype.getValue = function(){
		return this.getDomNode().value || null;
	}

	/**
	 * set value
	 * @return {Object}
	 */
	_DOM.prototype.setValue = function(val){
		this.getDomNode().value = val;
		return this;
	}

	/**
	 * get attribute
	 * @return {String}
	 */
	_DOM.prototype.getAttr = function(a){
		var n = this.getDomNode();
		if(n.hasAttribute(a)){
			return n.getAttribute(a);
		}
		return n[a];
	}

	/**
	 * set or get attribute
	 * @param {String} a
	 * @param {Object} v
	 * @return {Object}
	 */
	_DOM.prototype.setAttr = function(a, v){
		var node = this.getDomNode();
		if(typeof(a) == 'string'){
			node.setAttribute(a, v);
		} else {
			for(var i in a){
				try {
					if(node.hasAttribute(i)){
						node.setAttribute(i, a[i]);
					} else {
						node[i] = a[i];
					}
				} catch(ex){}
			}
		}
		return this;
	}

	/**
	 * get style by name
	 * @param {String} name
	 * @return {String}
	 */
	_DOM.prototype.getStyle = function(name){
		if(!this.isDomNode(this.getDomNode())){
			return null;
		}

		var convertName = {'float': Y.W.getComputedStyle ? 'cssFloat' : 'styleFloat'},
			node = this.getDomNode(),
			name = convertName[name] || name;
		if(Y.W.getComputedStyle){
			var cs = Y.W.getComputedStyle(node, null),
				value = node.style[name] || cs[name];
			if (value === undefined && (name == 'backgroundPositionX' || name == 'backgroundPositionY')) {
				value = node.style['backgroundPosition'] || cs['backgroundPosition'];
				value = value.split(' ')[+(name.slice(-1) == 'Y')];
			}
			return value;
		} else {
			var rOpacity = /opacity=([^)]*)/,
				value = node.style[name] || node.currentStyle[name],
				m;
			if(value === undefined && name == 'opacity') {
				value = (m = rOpacity.exec(node.currentStyle.filter)) ? parseFloat(m[1]) / 100 : 1;
			}
			return value;
		}
	}

	/**
	 * set style
	 * @param {String} name
	 * @param {String} value
	 * @return {Object}
	 */
	_DOM.prototype.setStyle = function(props, value){
		var tmp,
			node = this.getDomNode(),
			bRtn = true,
			w3cMode = (tmp = Y.D.defaultView) && tmp.getComputedStyle,
			rexclude = /z-?index|font-?weight|opacity|zoom|line-?height/i;

		if (typeof(props) == 'string') {
			tmp = props;
			props = {};
			props[tmp] = value;
		}

		for (var prop in props) {
			value = props[prop];
			if (prop == 'float') {
				prop = w3cMode ? "cssFloat" : "styleFloat";
			} else if (prop == 'opacity') {
				value = value >= 1 ? (value / 100) : value;
				if (!w3cMode) { // for ie only
					prop = 'filter';
					value = 'alpha(opacity=' + Math.round(value * 100) + ')';
				}
			} else if (prop == 'backgroundPositionX' || prop == 'backgroundPositionY') {
				tmp = prop.slice(-1) == 'X' ? 'Y' : 'X';
				if (w3cMode) {
					var v = this.getStyle('backgroundPosition' + tmp);
					prop = 'backgroundPosition';
					typeof(value) == 'number' && (value = value + 'px');
					value = tmp == 'Y' ? (value + " " + (v || "top")) : ((v || 'left') + " " + value);
				}
			}
			if (typeof node.style[prop] != "undefined") {
				try {
					node.style[prop] = value + (typeof value === "number" && !rexclude.test(prop) ? 'px' : '');
					bRtn = bRtn && true;
				} catch(ex){
					//console.log('SET STYLE', ex);
				}
			} else {
				bRtn = bRtn && false;
			}
		}
		return bRtn;
	}

	/**
	 * remove style from cssText
	 * @param  {String} key
	 * @return {DOM}
	 */
	_DOM.prototype.removeStyle = function(key){
		var cssText = this.getDomNode().style.cssText;
		if(cssText && new RegExp('(\\s|^)' + key + '(\\s|:)', 'i').test(cssText)){
			var reg = new RegExp('(\\s|^)' + key + '[\\s]*:(.*?)(;|$)', 'ig');
			this.getDomNode().style.cssText = cssText.replace(reg, ' ');
		}
		return this;
	};

	/**
	 * check current item is visibile
	 * @return {boolean}
	 */
	_DOM.prototype.checkIsVisibile = function(){

	}

	/**
	 * show dom object(display block)
	 * @return {Object}
	 */
	_DOM.prototype.show = function(){
		this.getDomNode().style.display = 'block';
		return this;
	}

	/**
	 * hide dom object(display none)
	 * @return {Object}
	 */
	_DOM.prototype.hide = function(){
		this.getDomNode().style.display = 'none';
		return this;
	}

	/**
	 * toggle dom object(display none or block)
	 * @return {Object}
	 */
	_DOM.prototype.toggle = function(){
		this.getDomNode().style.display = this.getDomNode().style.display == 'none' ? '' : 'none';
		return this;
	}

	/**
	 * swap two dom tag
	 * @param {Object} tag
	 */
	_DOM.prototype.swap = function(tag){
		var tag = Y.dom.one(tag).getDomNode();
		if (this.getDomNode().swapNode) {
			this.getDomNode().swapNode(tag);
		} else {
			var prt = tag.parentNode,
				next = tag.nextSibling;
			if (next == this.getDomNode()) {
				prt.insertBefore(this.getDomNode(), tag);
			} else if (tag == this.nextSibling) {
				prt.insertBefore(tag, this.getDomNode());
			} else {
				this.parentNode.replaceChild(tag, this.getDomNode());
				prt.insertBefore(this.getDomNode(), next);
			}
		}
	}

	/**
	 * remove current dom node
	 * @param {Boolean} keepChild
	 */
	_DOM.prototype.remove = function(keepChild){
		var n = this.getDomNode(),
			p = n.parentNode;
		if(keepChild && n.hasChildNodes()){
			while(c = n.firstChild){
				p.insertBefore(c, n)
			}
		}
		p.removeChild(n);
		this.setDomNode();
	}

	/**
	 * remove all children
	 **/
	_DOM.prototype.removeAllChildren = function(){
		var n = this.getDomNode();
		while(n.hasChildNodes()){
			n.removeChild(n.lastChild);
		}
	};

	/**
	 * relocation to parent node
	 * @param {Function} fn
	 */
	_DOM.prototype.parent = function(mix){
		var fn;
		if(typeof(mix) == 'string'){
			fn = function(n){
				return n.tagName.toLowerCase() == mix.toLowerCase();
			};
		} else if(mix){
			fn = mix;
		} else {
			return new _DOM(this.getDomNode().parentNode);
		}
		var result;
		var p = this.getDomNode().parentNode;
		if(fn){
			while(p && p.parentNode){
				if(fn(p)){
					return new _DOM(p);
				}
				p = p.parentNode;
			}
		} else {
			return new _DOM(p);
		}
	};

	/**
	 * get document from node
	 * @return {Document}
	 */
	_DOM.prototype.getDoc = function() {
		var n = this.getDomNode();
		if(!n){
			return Y.D;
		}
		return Y.ua.ie ? n['document'] : n['ownerDocument'];
	};

	/**
	 * get window
	 * @param  {Document} doc
	 * @return {Window}
	 */
	_DOM.prototype.getWin = function(doc) {
		if(!doc){
			doc = this.getDoc();
		}
		return doc.parentWindow || doc.defaultView;
	};

	/**
	 * get dom position information
	 * @return {Object}
	 */
	_DOM.prototype.getPosition = function(){
		var top = 0,
			left = 0,
			node = this.getDomNode();

		if(Y.D.documentElement.getBoundingClientRect && node.getBoundingClientRect) {
			var box = node.getBoundingClientRect(),
				oDoc = node.ownerDocument,
				_fix = Y.ua.ie ? 2 : 0; //ie & ff 2px bug
			top = box.top - _fix + Y.dom.getScroll().top;
			left = box.left - _fix + Y.dom.getScroll().left;
		}

		//only safari
		else {
			while (node.offsetParent) {
				top += node.offsetTop;
				left += node.offsetLeft;
				node = node.offsetParent;
			}
		}
		return {'left':left, 'top':top};
	}

	/**
	 * get size
	 * return {Object}
	 */
	_DOM.prototype.getSize = function(){
		var _fix = [0, 0], _this = this;
		Y.lang.each(['Left', 'Right', 'Top', 'Bottom'], function(v){
			_fix[v == 'Left' || v == 'Right' ? 0 : 1] += (parseInt(_this.getStyle('border'+v+'Width'),10)||0) +
				(parseInt(_this.getStyle('padding'+v),10)||0);
		});

		var _w = this.getDomNode().offsetWidth - _fix[0],
			_h = this.getDomNode().offsetHeight - _fix[1];
		return {width:_w, height:_h};
	}

	/**
	 * get region info
	 * @return {Object}
	 */
	_DOM.prototype.getRegion = function(){
		var pos = this.getPosition(),
			size = this.getSize();
		return {
			left: pos.left,
			top: pos.top,
			width: size.width,
			height: size.height
		}
	}

	/**
	 * set relate position to target
	 * @param {Object} el
	 * @param {String} pos
	 * @return {Boolean}
	 */
	_DOM.prototype.setRelatePosition = function(el, pos){
		var curR = this.getRegion(),
			tagR = el.getRegion();

		var posArr = {
			'center': {top:(tagR.top + tagR.height/2 - curR.height/2), left:(tagR.left + tagR.width/2 - curR.width/2)},
			'inner-left-top': {top:tagR.top, left:tagR.left},
			'inner-left-middle': {top:(tagR.top + tagR.height/2 - curR.height/2), left:tagR.left},
			'inner-left-bottom': {top:(tagR.top + tagR.height - curR.height), left:tagR.left},
			'inner-right-bottom': {top:(tagR.top + tagR.height - curR.height), left:(tagR.left+tagR.width-curR.width)},
			'inner-right-middle': {top:(tagR.top + tagR.height/2 - curR.height/2), left:(tagR.left+tagR.width-curR.width)},
			'inner-right-top': {top:tagR.top, left:(tagR.left+tagR.width-curR.width)},
			'inner-top-middle': {top:tagR.top, left:(tagR.left + tagR.width/2 - curR.width/2)},
			'inner-bottom-middle': {top:(tagR.top + tagR.height - curR.height), left:(tagR.left + tagR.width/2 - curR.width/2)},

			'outer-left-top': {top:tagR.top, left:(tagR.left-curR.width)},
			'outer-left-middle': {top:(tagR.top+tagR.height/2-curR.height/2), left:(tagR.left-curR.width)},
			'outer-left-bottom': {top:(tagR.top + tagR.height - curR.height), left:(tagR.left-curR.width)},
			'outer-right-bottom': {top:(tagR.top + tagR.height - curR.height), left:(tagR.left+tagR.width)},
			'outer-right-middle': {top:(tagR.top + tagR.height/2 - curR.height/2), left:(tagR.left+tagR.width)},
			'outer-right-top': {top:tagR.top, left:(tagR.left+tagR.width)},
			'outer-top-left': {top:(tagR.top-curR.height), left:tagR.left},
			'outer-top-middle': {top:(tagR.top-curR.height), left:(tagR.left + tagR.width/2 - curR.width/2)},
			'outer-top-right': {top:(tagR.top-curR.height), left:(tagR.left + tagR.width - curR.width)},
			'outer-bottom-left': {top:(tagR.top+tagR.height), left:tagR.left},
			'outer-bottom-middle': {top:(tagR.top+tagR.height), left:(tagR.left + tagR.width/2 - curR.width/2)},
			'outer-bottom-right': {top:(tagR.top+tagR.height), left:(tagR.left + tagR.width - curR.width)},

			'outer-coner-left-top': {top:(tagR.top-curR.height), left:(tagR.left-curR.width)},
			'outer-coner-right-top': {top:(tagR.top-curR.height), left:(tagR.left+tagR.width)},
			'outer-coner-left-bottom': {top:(tagR.top+tagR.height), left:(tagR.left-curR.width)},
			'outer-coner-right-bottom': {top:(tagR.top+tagR.height), left:(tagR.left+tagR.width)}
		};
		return this.setStyle({position:'absolute',display:'block', top:posArr[pos].top, left:posArr[pos].left});
	};

	/**
	 * create child node
	 * @param {String} tp node type
	 * @param {Integer} pos position(0,...-1)
	 * @return {Object} node
	 */
	_DOM.prototype.create = function(tp, pos, pp){
		var p = (this.getDomNode() || Y.D.body);
		p = p.nodeType == 9 ? p = p.body : p;

		pos = (pos === undefined) ? -1 : parseInt(pos,10) || 0;
		var n = typeof(tp) == 'string' ? Y.D.createElement(tp) : tp;
		if(pos == -1){
			p.appendChild(n);
		} else {
			p.insertBefore(n, p.childNodes[pos]);
		}
		var d = new _DOM(n);
		if(pp){
			d.setAttr(pp);
		}
		return d;
	}

	/**
	 * get children nodes
	 * @param {Integer} idx
	 * @return {Array}
	 */
	_DOM.prototype.getChildren = function(idx){
		var nodes = [], cns=this.getDomNode().childNodes;
	    for (var i = cns.length-1; i>=0; i--) {
	        if (cns[i].nodeName != '#text' && cns[i].nodeName != '#comment') {
	            nodes.unshift(new _DOM(cns[i]));
	        }
	    }
		return idx !== undefined ? nodes[idx] : nodes;
	}

	/**
	 * search dom chain by specified rule handler
	 * @param  {string} prop
	 * @param  {function} func
	 * @return {DOM}
	 */
	_DOM.prototype.searchChain = function(prop, func){
		prop = prop || 'parentNode';
		var ele = this.getDomNode();
		while (ele) {
			if (func.call(this, this)) {
				return this;
			}
			ele = ele[prop];
		}
		return null;
	}

	/**
	 * check node is dom node
	 * @param {Mix} node
	 * @return {Boolean}
	 */
	_DOM.prototype.isDomNode = function(node){
		return !!node.nodeType;
	}

	/**
	 * check node b is contain by current node
	 * @param {Mix} node
	 * @return {Boolean}
	 */
	_DOM.prototype.contains = function(b){
		var a = this.getDomNode(),
			b = b.getDomNode ? b.getDomNode() : b;
		return a.contains ? a != b && a.contains(b) : !!(a.compareDocumentPosition(b) & 16);
	}

	/**
	 * query on selector result
	 * @param  {String|Mix} selector
	 * @param  {DOM} context
	 * @param  {String} cond
	 * @return {Object|Null}
	 */
	_DOM.prototype.one = function(selector, context, cond){
		if(!selector){
			return null;
		}
		var context = context || this.getDomNode();
		if(typeof(selector) !== 'string'){
			return selector.getOneDomNode ? selector.getOneDomNode() : (selector.getDomNode ? selector : new _DOM(selector));
		}
		var selector = Y.querySelector(selector, context, cond);
		return selector ? new _DOM(selector) : null;
	};

	/**
	 * query all selector result
	 * @param  {String|Mix} selector
	 * @param  {DOM} context
	 * @param  {String} cond
	 * @return {Object}
	 */
	_DOM.prototype.all = function(selector, context, cond) {
		if(!selector){
			return new _DOMCollection([]);
		}
		if(typeof(selector) !== 'string'){
			return selector.getOneDomNode ? selector : (selector.getDomNode ? new _DOMCollection([selector.getDomNode()]) : new _DOM(selector));
		}
		var context = context || this.getDomNode();
		var result = Y.querySelectorAll(selector, context, cond);
		return new _DOMCollection(result);
	};


	/**
	 * extend event method
	 */
	var eventMapHash = {
		'addEvent': 'add',
		'removeEvent': 'remove',
		'on': 'add',
		'delegate': 'delegate'
	};
	for(var i in eventMapHash){
		(function(){
			var evMethod = eventMapHash[i];
			_DOM.prototype[i] = function(){
				var _tmpNode = this.getDomNode();
				var args = Y.lang.toArray(arguments);
				args.splice(0, 0, _tmpNode);
				return Y.event[evMethod].apply(Y.event, args);
			}
		})();
	};

	/**
	 * DOM Collection
	 */
	function _DOMCollection(domArray){
		this._nodes = domArray;
	}

	/**
	 * each extend
	 * @param  {Function} fn
	 */
	_DOMCollection.prototype.each = function(fn) {
		Y.lang.each(this._nodes, function(item, idx, col){
			return fn.call(null, new _DOM(item), idx, col);
		});
	};

	/**
	 * change current collection to array
	 * @return {Array}
	 */
	_DOMCollection.prototype.toArray = function() {
		var ret = [];
		Y.lang.each(this._nodes, function(item){
			ret.push(new _DOM(item));
		})
		return ret;
	};


	/**
	 * get one dom
	 * @return {_DOM}
	 */
	_DOMCollection.prototype.getOneDomNode = function(idx) {
		return new _DOM(this._nodes[idx||0]);
	};

	/**
	 * change current collection to dom array
	 * @return {Array}
	 */
	_DOMCollection.prototype.getAllDomNodes = function() {
		var ret = [];
		Y.lang.each(this._nodes, function(item){
			ret.push(item);
		})
		return ret;
	};

	/**
	 * extend _DOM method to _DOMCollection method
	 */
	Y.lang.each(_DOM.prototype, function(fn, method){
		_DOMCollection.prototype[method] = function(){
			var args = Y.lang.toArray(arguments);
			var ret = [];
			Y.lang.each(this._nodes, function(item){
				var _tmpNode = new _DOM(item);
				var r = _tmpNode[method].apply(_tmpNode, args);
				ret.push(r);
			});
			return ret;
		};
	});

	/**
	 * extend some from _DOM to Y.dom
	 * document || node method
	 */
	var _tmpDom = new _DOM(Y.D);
	var mapHash = ['one', 'all', 'create', 'getWin', 'delegate'];
	for(var i=0; i<mapHash.length; i++){
		(function(){
			var method = mapHash[i];
			Y.dom[method] = function(){
				var args = Y.lang.toArray(arguments);
				return _tmpDom[method].apply(_tmpDom, args);
			}
		})();
	};
})(YSL);