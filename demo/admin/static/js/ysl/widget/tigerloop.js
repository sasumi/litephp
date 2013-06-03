YSL.use('widget.Tween', function(Y){
	var Tiger = function(tagNodeList, config){
		this.tagNodeList = tagNodeList;
		this._timer = null;
		this.status = 0;
		this._t = 0;

		this.config = Y.object.extend({
			tween: 'Linear', //current version only support
			speed: 'veryfast',
			interval: 50,
			alphaAni: false,
			startIndex: 0,
			endIndex:199,
			loopCount:1
		}, config || {});

		if(!this.tagNodeList || !this.tagNodeList.length){
			throw('tagNodeList Empty');
		}

		if(!Y.lang.isFunction(this.config.tween)){
			this.config.tween = Y.object.route(Y.widget.Tween, this.config.tween);
		}
		if(!Y.lang.isFunction(this.config.tween)){
			throw('tween param error');
		}

		this._stepCount = (this.config.endIndex-this.config.startIndex) +	this.tagNodeList.length*this.config.loopCount;
		if(!this._stepCount){
			throw('step config error');
		}

		this.onStart = this.onPause = this.onResume = Y.emptyFn;
	};

	Tiger.prototype._run = function() {
		var _this = this;
		var b, c = this._stepCount, d=c;
		var _run = function(){
			if(_this.status == 1){
				var len = _this.tagNodeList.length;
				var stepIndex = parseInt(_this.config.tween(_this._t, 0, c, d), 10);	//TODO stepIndex <0
				var curNodeIndex = (stepIndex + _this.config.startIndex)%len;
				var curNode = _this.tagNodeList[curNodeIndex];

				_this.onTrigger(curNode, _this._lastNode, curNodeIndex, _this._lastNodeIndex, stepIndex);

				_this._lastNode = curNode;
				_this._lastNodeIndex = curNodeIndex;

				if(_this._t++ < d){
					_this.onRuning(_this._t);
					_this._timer = setTimeout(_run, _this.config.interval);
				} else {
					_this.onFinish(d);
					_this.status = 3;
				}
			}
		};
		_run();
	};

	/**
	 * start tiger
	 */
	Tiger.prototype.start = function(){
		this.onStart();
		this.reset();
		this.status = 1;
		this._run();
	};

	/**
	 * pause runing
	 */
	Tiger.prototype.pause = function(){
		if(this.status == 1){
			this.status = 2;
			this.onPause();
		}
	};

	/**
	 * resume from pause state
	 */
	Tiger.prototype.resume = function() {
		if(this.status == 2){
			this.status = 1;
			this._run();
			this.onResume();
		}
	};

	/**
	 * reset current loop
	 */
	Tiger.prototype.reset = function(){
		this.status = 0;
		this._t = 0;
		clearTimeout(this._timer);
	};

	/**
	 * stop current loop
	 */
	Tiger.prototype.stop = function() {
		this.reset();
	};

	/**
	 * on runing event
	 * @param  {Number} stepIndex
	 */
	Tiger.prototype.onRuning = function(stepIndex){
		//console.log('onRuning', stepIndex);
	};

	/**
	 * on finish event
	 * @param  {Number} stepIndex
	 */
	Tiger.prototype.onFinish = function(stepIndex){
		//console.log('onFinish', stepIndex);
	};

	/**
	 * on loop trigger
	 * @param  {DOM} curNode
	 * @param  {DOM} lastNode
	 * @param  {Number} curNodeIndex  current node index
	 * @param  {Number} lastNodeIndex last node index
	 * @param  {Number} stepIndex     current step index
	 */
	Tiger.prototype.onTrigger = function(curNode, lastNode, curNodeIndex, lastNodeIndex, stepIndex){
		if(lastNode){
			lastNode.style.cssText = '';
		}
		curNode.style.cssText = 'background-color:red; color:white; border-color:orange';
	};

	Y.widget.Tigerloop = Tiger;
});