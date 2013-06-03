(function(Y){
	var body = Y.dom.one('body');

	/**
	 * 拖拽
	 * @param mix dragTag 被拖动对象
	 * @param object option 选项
	 * 		option支持参数：
	 * 			container: 限定活动容器
	 * 			lockCenter: 是否锁定移动时，鼠标在目标位置中央
	 **/
	var Dragdrop = function(dragTag, option){
		this._init(dragTag, option);

		this.moveAble = false;
		var _this = this;
		var _moving = false;

		var _posInfo = {leftRate:0.5, topRate:0.5};
		var _proxyRegion = {};
		var _lastRegion = {};
		var _containerRegion = {};

		var updatePos = function(mouseX, mouseY){
			var newLeft = mouseX - Math.floor(_proxyRegion.width*_posInfo.leftRate),
				newTop = mouseY - Math.floor(_proxyRegion.height*_posInfo.topRate);

			//容器限制
			if(_this.container){
				newLeft = Math.max(_containerRegion.left, newLeft);
				newTop = Math.max(_containerRegion.top, newTop);

				if((_proxyRegion.width + newLeft) > (_containerRegion.left + _containerRegion.width)){
					newLeft = _containerRegion.left + _containerRegion.width - _proxyRegion.width;
				}

				if((_proxyRegion.height + newTop) > (_containerRegion.top + _containerRegion.height)){
					newTop = _containerRegion.top + _containerRegion.height - _proxyRegion.height;
				}
			}
			_this.proxy.setStyle({top:newTop,left:newLeft});
		};

		body.on('mousedown', function(e){
			var tag = Y.event.getTarget(e);
			if(tag.getDomNode() == _this.dragTag.getDomNode() || _this.dragTag.contains(tag)){
				if(_this.moveAble){
					_this.onBeforeStart(e);

					_moving = true;
					_lastRegion = _this.dragTag.getRegion();
					_proxyRegion = _this.proxy.getRegion();
					_containerRegion = _this.container ? _this.container.getRegion() : {};

					if(!_this.option.lockCenter){
						_posInfo = {
							leftRate: (e.clientX - _lastRegion.left)/_lastRegion.width,
							topRate: (e.clientY - _lastRegion.top)/_lastRegion.height
						};
					}

					updatePos(e.clientX, e.clientY);
					_this.onStart(e);
					Y.event.preventDefault(e);
				}
			}
		});

		body.on('mousemove', function(e){
			if(_this.moveAble && _moving && Y.event.getButton(e) === 0){
				updatePos(e.clientX, e.clientY);
				_this.onMoving(e);
			}
		});

		body.on('mouseup', function(e){
			if(_moving){
				_this.onStop(e);
				_moving = false;
			}
		});
	};

	Dragdrop.prototype._init = function(dragTag, option){
		this.option = Y.object.extend(true, {
			proxy: this.dragTag,
			container: null,
			lockCenter: false
		}, option);

		this.dragTag = Y.dom.one(dragTag);
		this.proxy = Y.dom.one(this.option.proxy) || this.dragTag;
		this.proxy.setStyle({
			position: 'absolute',
			cursor: 'move'
		});
		this.container = Y.dom.one(this.option.container);
	};

	Dragdrop.prototype.onMoving = function(e){};
	Dragdrop.prototype.onBeforeStart = function(e){};
	Dragdrop.prototype.onStart = function(e){};
	Dragdrop.prototype.onStop = function(e){};
	Dragdrop.prototype.start = function(){this.moveAble = true;};
	Dragdrop.prototype.stop = function(){this.moveAble = false;};

	/**
	 * 单例模式
	 * @param mix tag
	 * @param mix object
	**/
	Dragdrop.singleton = (function(){
		var _DRAGDROP_SINGLETON;
		return function(dragTag, option){
			if(!_DRAGDROP_SINGLETON){
				_DRAGDROP_SINGLETON = new Dragdrop(dragTag, option);
			} else {
				_DRAGDROP_SINGLETON._init(dragTag, option);
			}
			_DRAGDROP_SINGLETON.start();
			return _DRAGDROP_SINGLETON;
		};
	})();

	Y.widget.Dragdrop = Dragdrop;
})(YSL);