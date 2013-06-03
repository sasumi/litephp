(function(Y){
	var FLASH_VERSION;

	var _Media = {
		/**
		 * 获取flash版本号
		 * @return {Object}
		 **/
		getFlashVersion: function(){
			if (!FLASH_VERSION) {
				var resv = 0;
				if (navigator.plugins && navigator.mimeTypes.length) {
					var x = navigator.plugins['Shockwave Flash'];
					if (x && x.description) {
						resv = x.description.replace(/(?:[a-z]|[A-Z]|\s)+/, "").replace(/(?:\s+r|\s+b[0-9]+)/, ".").split(".");
					}
				} else {
					try {
						for (var i = (resv = 6), axo = new Object(); axo != null; ++i) {
							axo = new ActiveXObject("ShockwaveFlash.ShockwaveFlash." + i);
							resv = i;
						}
					} catch (e) {
						if (resv == 6) {
							resv = 0;
						}
						resv = Math.max(resv - 1, 0);
					}
					try {
						resv = new QZFL.media.SWFVersion(axo.GetVariable("$version").split(" ")[1].split(","));
					} catch (ignore) {}
				}
				if (!(resv instanceof QZFL.media.SWFVersion)) {
					resv = new QZFL.media.SWFVersion(resv);
				}
				if (resv.major < 3) {
					resv.major = 0;
				}
				FLASH_VERSION = resv;
			}
			return FLASH_VERSION;
		},

		/**
		 * 获取flashHtml
		 * @param {Object} flashArgs
		 * @param {String} requiredMajorVersion 需要flash的主版本号
		 * @param {String} cid
		 * @return {String}
		 **/
		getFlashHtml: function(flashArgs, requiredMajorVersion, cid){
			var _attrs = [],
                _params = [];
            for (var k in flashArgs) {
                switch (k) {
                case "noSrc":
                case "movie":
                    continue;
                    break;
                case "id":
                case "name":
                case "width":
                case "height":
                case "style":
                    if (typeof (flashArgs[k]) != 'undefined') {
                        _attrs.push(' ', k, '="', flashArgs[k], '"');
                    }
                    break;
                case "src":
                    if (Y.ua.ie) {
                        _params.push('<param name="movie" value="', (flashArgs.noSrc ? "" : flashArgs[k]), '"/>');
                    } else {
                        _attrs.push(' data="', (flashArgs.noSrc ? "" : flashArgs[k]), '"');
                    }
                    break;
                default:
                    _params.push('<param name="', k, '" value="', flashArgs[k], '" />');
                }
            }
            if (Y.ua.ie) {
                _attrs.push(' classid="clsid:', cid || 'D27CDB6E-AE6D-11cf-96B8-444553540000', '"');
            } else {
                _attrs.push(' type="application/x-shockwave-flash"');
            }
            if (requiredMajorVersion) {
                _attrs.push(' codeBase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab#version=', requiredMajorVersion, '"');
            }
            return "<object" + _attrs.join("") + ">" + _params.join("") + "</object>";
		},

		insertFlash: function(container, option){
			container = Y.dom.one(container).getDomNode();
			option = Y.object.extend(true, {
				src: '',
				width: '100%',
				height: '100%',
				noSrc: true
			}, option);

            container.innerHTML = _Media.getFlashHtml(option);
            var f = container.firstChild;
            if (Y.ua.ie) {
                setTimeout(function () {
                    f.LoadMovie(0, option.src);
                }, 0);
            } else {
                f.setAttribute("data", option.src);
            }
		},

		/**
		 * 获取WMM播放器HTML
		 **/
		getWMMHtml: function(wmpArgs, cid){
			var params = [],
                objArgm = [];
            for (var k in wmpArgs) {
                switch (k) {
                case "id":
                case "width":
                case "height":
                case "style":
                case "src":
                    objArgm.push(' ', k, '="', wmpArgs[k], '"');
                    break;
                default:
                    objArgm.push(' ', k, '="', wmpArgs[k], '"');
                    params.push('<param name="', k, '" value="', wmpArgs[k], '" />');
                }
            }
            if (wmpArgs["src"]) {
                params.push('<param name="URL" value="', wmpArgs["src"], '" />');
            }
            if (Y.ua.ie) {
                return '<object classid="' + (cid || "clsid:6BF52A52-394A-11D3-B153-00C04F79FAA6") + '" ' + objArgm.join("") + '>' + params.join("") + '</object>';
            } else {
                return '<embed ' + objArgm.join("") + '></embed>';
            }
		}
	};
	Y.com.media = _Media;
})(YSL);