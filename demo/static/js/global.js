(function(Y){
	//menu
	Y.dom.all('.main-nav li').each(function(li) {
		if(li.one('ul')){
			//li.addClass('has-children');
			li.addEvent('mouseover', function(){
				li.addClass('hover');
				var _this = Y.dom.one(this);
				_this.one('ul').show();
				_this.addClass('hover');
			});
			li.addEvent('mouseout', function(){
				li.removeClass('hover');
				var _this = Y.dom.one(this);
				_this.one('ul').hide();
				_this.removeClass('hover');
			});
		}
	});

	//dialog popup
	Y.dom.one(Y.D).on('click', function(e){
		var tag = Y.event.getTarget(e);
		if(tag.getAttribute('rel') == 'popup'){
			var tagDom = tag.getAttribute('popuptarget');
			var url = tag.href + (tag.href.indexOf('?') > 0 ? tag.href + '&popup_page=1' : '?popup_page=1');
			var oPop = new Y.widget.Popup({
				title: tag.title || tag.innerHTML,
				content: tagDom ? {id:tagDom} : {src:url},
				width: parseInt(tag.getAttribute('iwidth')) || 500,
				height: parseInt(tag.getAttribute('iheight')) || 0,
				moveEnable: true,
				topCloseBtn: true,
				buttons: []
			});
			oPop.show();

			if(tag.tagName == 'A'){
				Y.event.preventDefault();
				return false;
			}
		}
	});

	//iframe popup
	Y.dom.all('form[rel=iframe-form]').each(function(form){
		var iframeID = 'formsubmitiframe';
		var iframe = Y.dom.one('#'+iframeID);
		if(!iframe){
			var div = Y.dom.one('body').create('div');
				div.setHtml('<iframe src="" name="'+iframeID+'" id="'+iframeID+'" frameborder="0" class="hide"></iframe>')
		}
		form.setAttr('target', iframeID);
	});

	//ajax link
	Y.dom.one(Y.D).addEvent('click', function(){
		var tag = Y.event.getTarget();
		if(tag.rel == 'ajax'){
			var ajax = new Y.net.Ajax({
				url: tag.href,
				format: 'json'
			});
			ajax.onInit = function() {
				new Y.widget.Tip('sending request...');
			}
			ajax.onResult = function(data){
				if(!data || !data.t){
					new Y.widget.Tip('Request fail.', 2);
				}
				else if(data.t == 'success'){
					new Y.widget.Tip(data.m || 'Operation success.', 1);
					setTimeout(function(){
						location.reload();
					}, 1000);
				}
				else {
					new Y.widget.Tip(data.m || 'Operation fail.', 2);
				}
			}
			ajax.send();
			Y.event.preventDefault();
			return false;
		}
	});
})(YSL);
