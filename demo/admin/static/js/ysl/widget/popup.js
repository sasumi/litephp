(function(){
	var _HAS_TOP = (YSL.TOP_FIRST && top.YSL && top !== window);
	var _WIN = _HAS_TOP ? top : window;

	if(_WIN.YSL.widget.Popup){
		YSL.widget.Popup = _WIN.YSL.widget.Popup;
		return;
	}

	YSL.use('widget.masklayer', function(){
		var Y = _WIN.YSL;
		Y.dom.insertStyleSheet([
			'.PopupDialog {position:absolute; top:20px; left:20px; width:350px; border:1px solid #999; background-color:white; box-shadow:0 0 10px #535658}',
			'.PopupDialog-hd {height:28px; background-color:#e4e4e4; cursor:move; position:relative;}',
			'.PopupDialog-hd h3 {font-size:12px; font-weight:bolder; color:gray; padding-left:10px; line-height:28px;}',
			'.PopupDialog-close {display:block; overflow:hidden; width:28px; height:28px; position:absolute; right:0; top:0; text-align:center; cursor:pointer; font-size:17px; font-family:Verdana; text-decoration:none; color:gray;}',
			'.PopupDialog-close:hover {color:black;}',
			'.PopupDialog-ft {background-color:#f3f3f3; white-space:nowrap; border-top:1px solid #e0e0e0; padding:5px 5px 5px 0; text-align:right;}',
			'.PopupDialog-bd {padding:20px;}',
			'.PopupDialog-bd-frm {border:none; width:100%}',
			'.PopupDialog-btn {display:inline-block; cursor:pointer; box-shadow:1px 1px #fff; text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.7); background:-moz-linear-gradient(19% 75% 90deg, #E0E0E0, #FAFAFA); background:-webkit-gradient(linear, left top, left bottom, from(#FAFAFA), to(#E0E0E0)); color:#4A4A4A; background-color:white; text-decoration:none; padding:0 15px; height:20px; line-height:20px; text-align:center; border:1px solid #ccd4dc; white-space:nowrap; border-radius:2px}',
			'.PopupDialog-btn:hover {background-color:#eee}',
			'.PopupDialog-btnDefault {}'].join(''), 'YSL_WIDGET_POPUP');

		var POPUP_COLLECTION = [];
		var ESC_BINDED = false;

		/**
		 * Popup class
		 * @constructor Popup
		 * @description popup dialog class
		 * @example new YSL.widget.Popup(config);
		 * @param {Object} config
		 */
		var Popup = function(cfg){
			this.container = null;
			this.status = 0;
			this._constructReady = Y.emptyFn;
			this._constructed = false;
			this.guid = Y.guid();
			this.onShow = Y.emptyFn;
			this.onClose = Y.emptyFn;

			this.config = Y.object.extend(true, {
				ID_PRE: 'popup-dialog-id-pre',
				title: '对话框',				//标题
				content: '测试',				//content.src content.id
				width: 400,						//宽度
				moveEnable: true,				//框体可移动
				moveTriggerByContainer: false,	//内容可触发移动
				zIndex: 1000,					//高度
				isModal: false,					//模态对话框
				topCloseBtn: true,				//是否显示顶部关闭按钮,如果显示顶部关闭按钮，则支持ESC关闭窗口行为
				showMask: true,
				keepWhileHide: false,			//是否在隐藏的时候保留对象
				cssClass: {
					dialog: 'PopupDialog',
					head: 'PopupDialog-hd',
					body: 'PopupDialog-bd',
					iframe: 'PopupDialog-bd-frm',
					container: 'PopupDialog-dom-ctn',
					foot: 'PopupDialog-ft'
				},
				buttons: [/*
					{name:'确定', handler:null},
					{name:'关闭', handler:null, setDefault:true}*/
				],
				sender: Y.emptyFn,	//data sender interface
				reciver: Y.emptyFn	//data reciver interface
			}, cfg);

			this.constructStruct();

			//ADD TO MONITER COLLECTION
			POPUP_COLLECTION.push(this);
		};

		/**
		 * contruct popup structure
		 */
		Popup.prototype.constructStruct = function(){
			var _this = this;

			//DOM Clone Mode
			if(!this.container){
				this.container = Y.dom.create('div').addClass(this.config.cssClass.dialog);
				this.container.setStyle('left', '-9999px');
			}
			this.container.getDomNode().id = this.config.ID_PRE + Y.guid();

			//构建内容容器
			var content = '';
			if(typeof(this.config.content) == 'string'){
				content = '<div class="'+this.config.cssClass.body+'">'+this.config.content+'</div>';
			} else if(this.config.content.src){
				content = '<iframe allowtransparency="true" guid="'+this.guid+'" src="'+this.config.content.src+'" class="'+this.config.cssClass.iframe+'" frameborder=0></iframe>';
			} else {
				content = '<div class="' + this.config.cssClass.container + '"></div>';
			}

			//构建按钮
			var btn_html = '';
			if(this.config.buttons.length > 0){
				var btn_html = '<div class="'+this.config.cssClass.foot+'">';
				for(var i=0; i<this.config.buttons.length; i++){
					btn_html += '&nbsp;<a href="javascript:;" class="PopupDialog-btn'+(this.config.buttons[i].setDefault?' PopupDialog-btnDefault':'')+'">'+this.config.buttons[i].name+'</a>';
				}
				btn_html += '</div>';
			}

			//构建对话框框架
			var html = ([
					'<div class="PopupDialog-wrap">',
						'<div class="PopupDialog-Modal-Mask" style="position:absolute; height:0px; overflow:hidden; z-index:2; background-color:#ccc; width:100%"></div>',
						'<div class="',this.config.cssClass.head+'">',
							'<h3>',this.config.title,'</h3>',
							(this.config.topCloseBtn ? '<a class="PopupDialog-close" href="javascript:;" title="关闭窗口">x</a>' : ''),
						'</div>',content,btn_html,
					'</div>'
				]).join('');
			this.container.setHtml(html);

			if(this.config.content.src){
				this.container.one('iframe').on('load', function(){
					try {
						var ifr = this.getDomNode();
						var w = ifr.contentWindow;
						var d = w.document;
						var b = w.document.body;
						w.focus();
					} catch(ex){
						console.log(ex);
					}

					//Iframe+无指定固定宽高时 需要重新刷新size
					if(!_this.config.height && b){
						b.style.overflow = 'hidden';

						var info = {};
						if(w.innerWidth){
							//info.visibleWidth = w.innerWidth;
							info.visibleHeight = w.innerHeight;
						} else {
							var tag = (d.documentElement && d.documentElement.clientWidth) ?
								d.documentElement : d.body;
							//info.visibleWidth = tag.clientWidth;
							info.visibleHeight = tag.clientHeight;
						}
						var tag = (d.documentElement && d.documentElement.scrollWidth) ?
								d.documentElement : d.body;
						//info.documentWidth = Math.max(tag.scrollWidth, info.visibleWidth);
						info.documentHeight = Math.max(tag.scrollHeight, info.visibleHeight);

						//this.parentNode.parentNode.style.width = info.documentWidth + 'px';
						//w.frameElement.style.width = info.documentWidth + 'px';
						ifr.style.height = info.documentHeight + 'px';
						_this.container.setStyle('height', 'auto');
					} else {
						var headRegion = Y.dom.one('.'+_this.config.cssClass.head).getRegion();
						this.setStyle('height', (_this.config.height - headRegion.height)+'px');
					}
					_this._constructed = true;
					_this._constructReady();
				});
			} else {
				//移动ID绑定模式的DOM对象【注意：这里移动之后，原来的元素就被删除了，为了做唯一性，这里只能这么干】
				if(this.config.content.id){
					Y.dom.one('#'+this.config.content.id).show();
					this.container.one('div.'+this.config.cssClass.container).getDomNode().appendChild(Y.dom.one('#'+this.config.content.id).getDomNode());
				}
				_this._constructed = true;
				this._constructReady();
			}
		};

		/**
		 * show popup
		 */
		Popup.prototype.show = function(){
			var _this = this;
			if(!this._constructed){
				this._constructReady = function(){
					_this.show();
				};
				return;
			}

			//CREATE MASK
			if(this.config.showMask){
				Y.widget.masklayer.show();
			}

			this.container.show();

			//CACULATE REGION INFO
			var region = Y.object.extend(true, this.container.getRegion(), this.config);
				region.minHeight = region.minHeight || 78;

			var scroll = Y.dom.getScroll(),
				winRegion = Y.dom.getWindowRegion(),
				top = left = 0;

			if(winRegion.visibleHeight > region.height){
				top = scroll.top + (winRegion.visibleHeight - region.height)/4;
			} else if(winRegion.documentHeight > region.height){
				top = scroll.top;
			}

			if(winRegion.visibleWidth > region.width){
				left = winRegion.visibleWidth/2 - region.width/2 - scroll.left;
			} else if(winRegion.documentWidth > region.width){
				left = scroll.left;
			}
			var calStyle = Y.object.extend(true, region,{left:left,top:top,zIndex:this.config.zIndex});
			this.container.setStyle(calStyle);

			//固定高度iframe设置高度
			if(this.config.content.src && this.config.height){
				var iframe = this.container.one('iframe');
				var headRegion = Y.dom.one('.'+this.config.cssClass.head).getRegion();
				iframe.setStyle('height', (this.config.height - headRegion.height)+'px');
			}

			this.onShow();
			this.status = 1;
			this.bindEvent();
			this.bindMoveEvent();
			this.bindEscCloseEvent();

			var hasOtherModalPanel = false;
			var _this = this;

			Y.lang.each(POPUP_COLLECTION, function(dialog){
				//有其他的模态对话框
				//调低当前对话框的z-index
				if(dialog != _this && dialog.status && dialog.config.isModal){
					_this.config.zIndex = dialog.config.zIndex - 1;
					hasOtherModalPanel = true;
					return false;
				} else if(_this != dialog && dialog.status && !dialog.config.isModal){
					if(dialog.config.zIndex > _this.config.zIndex){
						_this.config.zIndex = dialog.config.zIndex + 1;
					} else if(dialog.config.zIndex == _this.config.zIndex){
						_this.config.zIndex += 1;
					}
				}
			});

			this.container.setStyle('zIndex', this.config.zIndex);
			if(hasOtherModalPanel){
				this.setDisable();
			} else if(_this.config.isModal){
				//设置除了当前模态对话框的其他对话框所有都为disable
				Y.lang.each(POPUP_COLLECTION, function(dialog){
					if(dialog != _this && dialog.status){
						dialog.setDisable();
					}
				});
				this.focus();
			} else {
				this.focus();
			}
		};

		/**
		 * 聚焦到当前对话框第一个按钮
		 */
		Popup.prototype.focus = function() {
			var a = this.container.one('A');
			if(a){
				a.getDomNode().focus();
			}
		};

		/**
		 * set dialog operate enable
		 **/
		Popup.prototype.setEnable = function() {
			var mask = this.container.one('.PopupDialog-Modal-Mask');
			if(mask){
				mask.hide();
			}
		};

		/**
		 * set dialog operate disable
		 **/
		Popup.prototype.setDisable = function() {
			var size = this.container.getSize();
			var mask = this.container.one('.PopupDialog-Modal-Mask');
			mask.setStyle({height:size.height, opacity:0.4});
		};

		/**
		 * bind popup event
		 */
		Popup.prototype.bindEvent = function(){
			var _this = this;
			var topCloseBtn = this.container.one('a.PopupDialog-close');
			if(topCloseBtn){
				topCloseBtn.getDomNode().onclick = Y.object.bind(this, function(){
					this.close();
				});
			}

			this.container.all('a.PopupDialog-btn').each(function(btn, i){
				btn.on('click', function(){
					if(_this.config.buttons[i].handler){
						_this.config.buttons[i].handler.apply(this, arguments);
					} else {
						_this.close();
					}
				});
			});

			var defBtn = this.container.one('a.PopupDialog-btnDefault');
			if(defBtn){
				defBtn.getDomNode().focus();
			}

			var _this = this;
			this.container.on('mousedown', function(){_this.updateZindex();});
		}

		/**
		 * update dialog panel z-index property
		 **/
		Popup.prototype.updateZindex = function() {
			var _this = this;
			var hasModalPanel = false;
			Y.lang.each(POPUP_COLLECTION, function(dialog){
				if(dialog != _this && dialog.status && dialog.config.isModal){
					hasModalPanel = true;
					return false;
				} else if(dialog != _this && dialog.status){
					if(dialog.config.zIndex >= _this.config.zIndex){
						_this.config.zIndex = dialog.config.zIndex + 1;
					}
				}
			});
			if(hasModalPanel){
				return;
			}
			this.container.setStyle('zIndex', this.config.zIndex);
		}

		/**
		 * bind ESC close event
		 */
		Popup.prototype.bindEscCloseEvent = function(){
			if(ESC_BINDED){
				return;
			}
			ESC_BINDED = true;

			var _this = this;
			Y.event.add(Y.D, 'keyup', function(e){
				if(e.keyCode == Y.event.KEYS.ESC){
					var lastDialog = null;
					Y.lang.each(POPUP_COLLECTION, function(dialog){
						if(dialog.config.isModal && dialog.status && dialog.config.topCloseBtn){
							lastDialog = dialog;
							return false;
						} else if(dialog.status && dialog.config.topCloseBtn){
							if(!lastDialog || lastDialog.config.zIndex <= dialog.config.zIndex){
								lastDialog = dialog;
							}
						}
					});
					if(lastDialog){
						lastDialog.close();
					}
				}
			});
		}

		/**
		 * bind popup moving event
		 */
		Popup.prototype.bindMoveEvent = function(){
			if(!this.config.moveEnable){
				return;
			}
			var _this = this;
			var _lastPoint = {X:0, Y:0};
			var _lastRegion = {top:0, left:0};
			var _moving;

			Y.event.add(Y.D, 'mousemove', function(e){
				e = e || Y.W.event;
				if(!_this.container || !_moving || Y.event.getButton(e) !== 0){
					return false;
				}
				offsetX = parseInt(e.clientX - _lastPoint.X, 10);
				offsetY = parseInt(e.clientY - _lastPoint.Y, 10);
				var newLeft = Math.max(_lastRegion.left + offsetX,0);
				var newTop = Math.max(_lastRegion.top + offsetY,0);
				_this.container.setStyle({top:newTop,left:newLeft});
			});

			Y.event.add(Y.D, 'mousedown', function(e){
				if(!_this.container){
					return;
				}
				var head = _this.config.moveTriggerByContainer ? _this.container : _this.container.one('.'+_this.config.cssClass.head);
				var tag = Y.dom.one(Y.event.getTarget());
				if(head.contains(tag)){
					_moving = true;
					_lastRegion = _this.container.getRegion();
					_lastPoint = {X: e.clientX, Y: e.clientY};
					Y.event.preventDefault(e);
				}
			});

			Y.event.add(Y.D, 'mouseup', function(){
				_moving = false;
			});
		}

		/**
		 * close current popup
		 */
		Popup.prototype.close = function(){
			if(this.onClose() === false){
				return;
			}
			this.container.hide();
			this.status = 0;

			var _this = this,
				hasDialogLeft = false,
				hasModalPanelLeft = false;

			if(!this.config.keepWhileHide){
				var tmp = [];
				Y.lang.each(POPUP_COLLECTION, function(dialog){
					if(dialog != _this){
						tmp.push(dialog);
					}
				});
				POPUP_COLLECTION = tmp;
				_this.container.remove();
				_this.container = null;
			}

			Y.lang.each(POPUP_COLLECTION, function(dialog){
				if(dialog.status){
					hasDialogLeft = true;
				}
				if(dialog.status && dialog.config.isModal){
					hasModalPanelLeft = true;
					dialog.setEnable();
					dialog.focus();
					return false;
				}
			});

			//没有显示的对话框
			if(!hasDialogLeft){
				Y.widget.masklayer.hide();
			}

			//剩下的都是普通对话框
			if(!hasModalPanelLeft){
				var _lastTopPanel;
				Y.lang.each(POPUP_COLLECTION, function(dialog){
					if(!dialog.status){
						return;
					}
					dialog.setEnable();
					if(!_lastTopPanel){
						_lastTopPanel = dialog;
					} else if(_lastTopPanel.config.zIndex <= dialog.config.zIndex){
						_lastTopPanel = dialog;
					}
				});
				if(_lastTopPanel){
					_lastTopPanel.focus();
				}
			}
		}

		/**
		 * 关闭其他窗口
		 **/
		Popup.prototype.closeOther = function(){
			try {
				var _this = this;
				Y.lang.each(POPUP_COLLECTION, function(pop){
					if(pop != _this){
						pop.close();
					}
				});
			}catch(e){}
		};

		/**
		 * close all popup
		 * @see Popup#close
		 */
		Popup.closeAll = function(){
			Y.lang.each(POPUP_COLLECTION, function(pop){
				pop.close();
			});
		};

		/**
		 * resize current popup
		 * @deprecated only take effect in iframe mode
		 */
		Popup.resizeCurrentPopup = function(){
			if(!Y.W.frameElement){
				return;
			}
			Y.dom.one(Y.W).on('load', function(){
				var wr = Y.dom.getWindowRegion();
				Y.D.body.style.overflow = 'hidden';
				Y.W.frameElement.style.height = wr.documentHeight +'px';
			});
		};

		/**
		 * search popup by guid
		 * @param  {String} guid
		 * @return {Popup}
		 */
		Popup.getPopupByGuid = function(guid){
			var result;
			Y.lang.each(POPUP_COLLECTION, function(pop){
				if(pop.guid == guid){
					result = pop;
					return false;
				}
			});
			return result;
		};

		/**
		 * close current popup
		 * @deprecated only take effect in iframe mode
		 */
		Popup.closeCurrentPopup = function(){
			if(!Y.W.frameElement){
				return;
			}

			var guid = Y.W.frameElement.getAttribute('guid');
			if(guid){
				var pop = parent.YSL.Popup.getPopupByGuid(guid);
				pop.close();
			}
		};

		if(_HAS_TOP){
			YSL.widget.Popup = _WIN.YSL.widget.Popup = Popup;
		} else {
			YSL.widget.Popup = Popup;
		}
	});
})();