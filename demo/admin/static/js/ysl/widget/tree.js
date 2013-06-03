YSL.use('', function(Y){
	/**
	 * 目录树
	 * @param {DOM} container	渲染容器
	 * @param {Object} data 	node: {title:'', className: '', value:'', children[node1,node2,...]}
	 * @param {Object} config	配置项
	 */
	var Tree = function(container, data, config){
		var _this = this;
		this.container = Y.dom.one(container);
		this.data = data;
		this.config = Y.object.extend(config || {}, {
			toggleAble: true,
			initToggleState: true,	//true : expand, false: collapse
			useAnimate: true,
			css: {
				treeList: 'tree-list',

				rootNodePrefix: 'tree-root-node',
				rootNodeExpandPrefix: 'tree-root-node-expand-prefix',
				rootNodeCollapsePrefix: 'tree-root-node-collapse-prefix',

				nodePrefix: 'tree-node-prefix',
				nodeCollapsePrefix: 'tree-node-collapse-prefix',
				nodeExpandPrefix: 'tree-node-expand-prefix',

				lastNodePrefix: 'tree-last-node-prefix',
				lastNodeCollapsePrefix: 'tree-last-node-collapse-prefix',
				lastNodeExpandPrefix: 'tree-last-node-expand-prefix',

				nodeTitle: 'tree-node-title',
				nodeLast: 'tree-node-last'
			}
		});

		if(!this.container || !this.data){
			throw("PARAMS ERROR");
		}

		if(!Y.lang.isArray(this.data)){
			this.data = [this.data];
		}

		var len = this.data.length;
		var html = '<ul class="'+this.config.css.treeList+'">';
		Y.lang.each(this.data, function(data,idx){
			html += _this._render(data, idx == (len-1))
		})
		html += '</ul>';
		this.container.setHtml(html);
		this._bindEvent();
	};

	/**
	 * render a node
	 * @param  {Object} data
	 * @param  {Boolean} lastChild
	 * @return {String}
	 */
	Tree.prototype._render = function(data, lastChild){
		var _this = this,
			childHtml = '',
			hasChild = data.children && data.children.length;

		var isRootNode = !this._rootNodeClassRendered;
		this._rootNodeClassRendered = true;

		if(hasChild){
			childHtml = '<ul'+(this.config.toggleAble && !this.config.initToggleState ? ' style="display:none"': '')+'>';
			var len = data.children.length;
			Y.lang.each(data.children, function(child, idx){
				childHtml += _this._render(child, idx==(len-1));
			});
			childHtml += '</ul>';
		}

		var prefixClassList = [(isRootNode ? this.config.css.rootNodePrefix : (lastChild ? this.config.css.lastNodePrefix : this.config.css.nodePrefix))];
		if(this.config.toggleAble && hasChild){
			if(isRootNode){
				prefixClassList.push(this.config.initToggleState ? this.config.css.rootNodeExpandPrefix : this.config.css.rootNodeCollapsePrefix);
			}
			else if(lastChild){
				prefixClassList.push(this.config.initToggleState ? this.config.css.lastNodeExpandPrefix : this.config.css.lastNodeCollapsePrefix);
			} else {
				prefixClassList.push(this.config.initToggleState ? this.config.css.nodeExpandPrefix : this.config.css.nodeCollapsePrefix);
			}
		}

		var nodeClass = lastChild ? this.config.css.nodeLast : '';
		return [
			'<li',(nodeClass ? ' class="'+nodeClass+ '"' : ''),'>',
				(this.config.toggleAble && hasChild ? '<a href="javascript:;" class="'+prefixClassList.join(' ')+'"></a>' : '<span class="'+prefixClassList.join(' ')+'"></span>'),
				'<span class="',this.config.css.nodeTitle, ' ', (data.className || ''),'" -data-value="',data.value,'">',data.title,'</span>',
				childHtml,
			'</li>'].join('');
	};

	/**
	 * 绑定tree事件
	 */
	Tree.prototype._bindEvent = function(){
		var _this = this;
		this.container.on('click', function(e){
			var target = Y.event.getTarget(e);
			if(target.tagName == 'A' && target.parentNode.tagName == 'LI' && target.parentNode.firstChild == target){
				_this.toggleNode(Y.dom.one(target.parentNode));
				Y.event.preventDefault();
			}
			if(target.nodeType == 'SPAN' && target.parentNode.tagName == 'LI' && target.parentNode.childNodes[1] == target){
				_this.onTitleClick(Y.dom.one(target.parentNode));
				Y.event.preventDefault();
			}
		});
	};

	Tree.prototype.onTitleClick = function(node){

	};

	/**
	 * 折叠指定项目
	 * @param  {DOM} node
	 */
	Tree.prototype.toggleNode = function(node){
		var subTree = node.one('ul');
		var prefixLink = node.one('a').getDomNode();
		var toState = subTree.getStyle('display') == 'none';
		if(!toState){
			this._hideNode(subTree, function(){
				prefixLink.className = prefixLink.className.replace(/-expand-/ig, '-collapse-');
			});
		} else {
			this._showNode(subTree, function(){
				prefixLink.className = prefixLink.className.replace(/-collapse-/ig, '-expand-');
			});
		}
	};

	/**
	 * 隐藏节点
	 * @param  {DOM}   node
	 * @param  {Function} callback
	 */
	Tree.prototype._hideNode = function(node, callback){
		if(this.config.useAnimate){
			Y.use('widget.Animate', function(){
				var Ani = new Y.widget.Animate(node, {
					to: {height:0},
					tween: 'Back.easeIn',
					speed: 'slow'
				});
				node.setStyle('overflow','hidden');
				Ani.onFinish = function(){
					node.removeStyle('overflow').removeStyle('height');
					node.setStyle('display', 'none');
					callback();
				};
				Ani.start();
			});
		} else {
			node.hide();
			callback();
		}
	};

	/**
	 * 显示节点
	 * @param  {DOM}   node
	 * @param  {Function} callback
	 */
	Tree.prototype._showNode = function(node, callback){
		if(this.config.useAnimate){
			Y.use('widget.Animate', function(){
				node.setStyle({
					position:'absolute',
					visibility: 'hidden',
					display: 'block'
				});
				var region = node.getRegion();

				node.removeStyle('position').removeStyle('visibility').removeStyle('display');
				node.setStyle({
					height: 0,
					overflow: 'hidden'
				});

				var Ani = new Y.widget.Animate(node, {
					to: {height:region.height},
					tween: 'Back.easeOut',
					speed: 'normal'
				});
				Ani.onFinish = function(){
					node.removeStyle('overflow').removeStyle('height');
					callback();
				};
				Ani.start();
			});
		} else {
			node.show();
			callback();
		}
	};

	Tree.prototype.getDOM = function(){
		return this.container.getChildren(0);
	};

	Y.widget.Tree = Tree;
})