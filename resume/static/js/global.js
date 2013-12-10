(function(Y) {
	var R = new function() {
			this.showTip = function(msg, code) {
				top.YSL.use('widget.Tip', function(Y, Tip) {
					return new Tip(msg, code, 1.5);
				});
			};

			this.url = function(uri, param) {
				var tmp = uri.split('/');
				var file = tmp[0] + '.php',
					act = tmp[1] || '';
				return Y.net.mergeCgiUri(SITE_URL + file+'/'+act, param);
			};

			this.hideTip = function() {
				if (top.YSL && top.YSL.widget.Tip) {
					top.YSL.widget.Tip.closeAll();
				}
			};

			this.scaleAvaImg = function(obj) {
				var img = new Image();
				img.onload = function() {
					var newW, newH, ml, mt,
						w = this.width,
						h = this.height;

					if (w > h) {
						newH = 128;
						newW = parseInt(w / h * 100, 10);
						ml = parseInt((newW - 128) / 2, 10);
					} else {
						newW = 128;
						newH = parseInt(h / w * 128, 10);
						mt = parseInt((newH - 128) / 2, 10);
					}
					var s = 'width:' + newW + 'px;height:' + newH + 'px;' + (ml ? 'margin-left:-' + ml + 'px;' : '') + (mt ? 'margin-top:-' + mt + 'px;' : '');
					obj.style.cssText = s;
				};
				img.src = obj.src;
			};

			this.changeAvatar = function(succCb) {
				succCb = succCb || Y.emptyFn;
				var url = this.url('user/changeAvatar');
				Y.use('widget/Popup', function(Y, Pop) {
					var p = new Pop({
						title: '设置头像',
						content: {
							src: url
						},
						buttons: [{
							name: '保存设置',
							handler: 'saveSetting',
							setDefault: true
						}],
						width: 400,
						height: 160
					});
					p.addIO('onSucc', succCb);
					p.show();
				});
			};

			var _login_cb_list = [];
			var _login_showing = false;

			var _callLoginSucc = function(param) {
				Y.lang.each(_login_cb_list, function(fn) {
					fn(param);
				});
				_login_cb_list = [];
			};

			this.showLogin = function(succCb) {
				succCb = succCb || Y.emptyFn;
				_login_cb_list.push(succCb);
				if (!_login_showing) {
					_login_showing = true;
					Y.use('widget.Popup', function(Y, Pop) {
						var pop = new Pop({
							title: '用户登录',
							content: {
								src: R.url('user/login')
							},
							width: 420,
							height: 180
						});
						pop.addIO('loginSucc', function(param) {
							_callLoginSucc(param);
							_login_showing = false;
						});
						pop.onClose = function() {
							_login_showing = false;
						};
						pop.show();
					});
				}
			};
		};

	Y.ready(function() {
		//自动表单
		Y.dom.all('form[data-trans=async]').each(function(form) {
			var iframeID = 'formsubmitiframe' + Y.guid();
			var iframe = Y.dom.one('#' + iframeID);
			if (!iframe) {
				iframe = document.createElement('iframe');
				iframe.id = iframeID;
				iframe.name = iframeID;
				iframe.style.display = 'none';
				iframe.callback = function() {
					var onresponse = form.getAttr('onresponse');
					if (onresponse) {
						var args = Y.lang.toArray(arguments);
						eval('var fn = window.' + onresponse + ';');
						fn.apply(null, args);
					}
				};
				document.body.appendChild(iframe);
			}
			form.setAttr('target', iframeID);
		});

		Y.use('widget.textAutoResize', function(Y, ar) {
			Y.dom.all('textarea').each(function(txt) {
				ar(txt);
			});
		});

		//auto resize textarea
		Y.dom.one(Y.D).on('keydown', function(e) {
			var tag = Y.event.getTarget(e);
			var node = tag.getDomNode();
			if (node.tagName == 'TEXTAREA' && tag.getAttr('data-auto-resize')) {
				var h = tag.getAttr('data-min-height');
				if (!h) {
					h = parseInt(tag.getStyle('height'), 10);
					tag.setAttr('data-min-height', h);
				}
				tag.setStyle('height', h);
				setTimeout(function() {
					tag.setStyle('height', node.scrollHeight);
				}, 0);
			}
		});
	});

	Y.dom.delegate('a[rel=login-btn]', 'click', function() {
		R.showLogin(function() {
			setTimeout(function() {
				location.reload();
			}, 1000);
		});
		Y.event.preventDefault();
	});

	window.R = R;
})(YSL);