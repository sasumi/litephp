YSL.use('widget.Animate', function(Y){
	var slide = function(id, config){
		config = config || {};
		this.ele = Y.dom.one('#' + id);
		this.config = Y.object.extend(false, {
			defaultIdx: 0,
			speed: 'normal',
			cssClass: {
				pageWrap: 'Slide-page-wrap',
				prevBtn: 'Slide-prev-btn',
				nextBtn: 'Slide-next-btn',
				pageCtn: 'Slide-page-ctn'
			}
		}, config);

		this._init();
	};

	slide.prototype._init = function(){
		var cfg = this.config, self = this;
		this.pageWrap = this.ele.one('.' + cfg.cssClass.pageWrap);
		this.pageList = this.pageWrap.getChildren();
		if(this.pageList.length == 0 ){return ;}
		this.maxIdx = this.pageList.length -1;

		var wrapWidth = 0;
		this.posArray = [];
		this.pageWidthArray = [];
		Y.lang.each(this.pageList, function(item){
			var pageCtn = item.one('.' + cfg.cssClass.pageCtn);
			var left = pageCtn.getPosition().left;
			var width = pageCtn.getRegion().width;
			self.posArray.push(left);
			self.pageWidthArray.push(width);
			wrapWidth += item.getRegion().width;
		});
		this.pageWrap.setStyle('width', wrapWidth);

		if(this.maxIdx > 0) {
			this.prevBtn = Y.dom.create('a').addClass(cfg.cssClass.prevBtn);
			this.nextBtn = Y.dom.create('a').addClass(cfg.cssClass.nextBtn);
			this.ele.getDomNode().appendChild(this.prevBtn.getDomNode());
			this.ele.getDomNode().appendChild(this.nextBtn.getDomNode());

			this._bindEvent();
		}

		this.curIdx = cfg.defaultIdx;
		this.jumpTo(this.curIdx);

	};

	slide.prototype._bindEvent = function(){
		var self = this;
		this.prevBtn.on('click', function(e){
			Y.event.preventDefault();
			if(self.curIdx > 0){
				var idx = self.curIdx - 1;
				self.jumpTo(idx);
			}
		});

		this.nextBtn.on('click', function(e){
			Y.event.preventDefault();
			if(self.curIdx < self.maxIdx ){
				var idx = self.curIdx + 1;
				self.jumpTo(idx);
			}
		});

		Y.dom.one(Y.W).on('resize', function(){
			self.jumpTo(self.curIdx);
		})
	},

	slide.prototype.onJump = function(){},

	slide.prototype.jumpTo = function(idx, callback){
		if(idx < 0 || idx > this.maxIdx){
			return ;
		}

		var cfg = this.config, self = this, left,
			viewW = this.ele.getRegion().width,
			page = this.pageList[idx];
			pageW = page.getRegion().width,
			pageCtn = page.one('.' + cfg.cssClass.pageCtn),
			ctnLeft = this.posArray[idx],
			ctnW = this.pageWidthArray[idx];


		if(viewW > ctnW){
			left = (viewW - ctnW )/2 - ctnLeft;
		} else{
			left = - ctnLeft;
		}

		left = left > 0 ? 0 : left;

		if(this.ani){
			this.ani.pause();
		}

		this.ani = new YSL.Animate('#' + cfg.cssClass.pageWrap, {
			tween: 'Quart.easeIn',
			speed: cfg.speed,
			to: {left: left}
		});

		this.ani.onFinish = function(){
			self.onJump(idx);
			self.curIdx = idx;
			if(self.maxIdx == 0){return ;}
			if(self.curIdx == 0){
				self.prevBtn.hide();
			} else if (self.curIdx == self.maxIdx ){
				self.nextBtn.hide();
			} else{
				self.prevBtn.show();
				self.nextBtn.show();
			}
		}

		this.ani.start();
	};

	Y.widget.PageSlide = slide;
});