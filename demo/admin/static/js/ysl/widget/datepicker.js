(function(Y){
	var DatePicker = function(config){
		this.config = {
			showLunaCalendar: true,
			showTimePicker: false,
			showWeekColumn: false,
			yearRange: '1980-2050',
			// dayLang: '日,一,二,三,四,五,六',
			// monthLang: '一月,二月,三月,四月,五月,六月,七月,八月,九月,十月,十一月,十二月',
			// weekLang: '周',

			dayLang: 'Sun,Mon,Thr,Wen,Thu,Fri,Sat',
			monthLang: 'January,February,March,April,May,June,July,August,September,October,November,December',
			weekLang: 'Wk',

			sid: '_date_picker_sid_'+Math.random(),
			cssClass: {
				today: 'today',
				lastMonthDate: 'last_month_date',
				nextMonthDate: 'next_month_date',
				sunday: 'sunday',
				saturday: 'saturday',
				dateSelected: 'selected',
				initDate: 'init_date'
			},
			cssStyle: [''].join(''),
			template: [''].join(''),
			formInput: null
		};

		this.panel = null;		//面板
		this.initDateObj = {};	//初始日期
		this.dateInfo = {
			y: 1970,
			m: 1,		//取值1-12
			d: 1,
			h: 00,
			i: 00,
			s: 00
		};

		/**
		 * parse date string to date object
		 * @param {string} str
		 * @return {object}
		 **/
		this.parseDateVal = function(str){
			var dobj = str ? new Date(str) : new Date();
			var dateObj = {
				y: dobj.getFullYear(),
				m: dobj.getMonth()+1,
				d: dobj.getDate(),
				h: dobj.getHours(),
				i: dobj.getMinutes(),
				s: dobj.getSeconds()
			};
			return dateObj;
		};

		this.appendStyleSheet = function(){};

		/**
		 * get date hash
		 * @param {object} dateObj
		 * @return {array}
		 **/
		this.getDateHash = function(dateObj){
			var dateStar = (new Date(dateObj.y, dateObj.m-1, 1)).getDay();
			var dateNum = (new Date(dateObj.y, dateObj.m, 0)).getDate();
			var lastMonthDateNum = (new Date(dateObj.y, dateObj.m-1, 0)).getDate();
			var maxCellNum = 42;
			var dateHash = [];

			for(var i=0; i<maxCellNum; i++){
				var hashInfo = {};

				if(i<dateStar){
					hashInfo = {
						y: (dateObj.m == 1 ? dateObj.y-1 : dateObj.y),
						m: (dateObj.m == 1 ? 12:(dateObj.m-1)),
						d: lastMonthDateNum-(dateStar-i)+1
					};
				} else if(i<dateNum+dateStar){
					hashInfo = {
						y: dateObj.y,
						m: dateObj.m,
						d: i-dateStar+1
					};
				} else {
					hashInfo = {
						y: dateObj.m == 12 ? dateObj.y+1 : dateObj.y,
						m: (dateObj.m == 12 ? 1 : (dateObj.m+1)),
						d: i-(dateNum+dateStar)+1
					}
				}
				dateHash.push(hashInfo);
			}
			return dateHash;
		};

		/**
		 * create panel
		 **/
		this.createPanel = function(){
			this.panel = Y.dom.one('#datepicker_01');
			this.panel.setStyle({
				width: this.config.showWeekColumn ? 200 : 176
			});

			var dayStr = this.config.dayLang.split(',').join('</span></li><li><span>');
			var html = '';
			if(this.config.showWeekColumn){
				html = '<li><span>'+this.config.weekLang+'</span></li>';
			}
			html += '<li><span>'+dayStr+'</span></li>';
			this.getPanelEl('.datepicker_daylist').setHtml(html);

			if(this.config.showTimePicker){
				var timepicker_html = '<select size="1">';
				for(var i=1; i<=23; i++){
					timepicker_html+= '<option value="'+i+'">'+i+'</option>';
				}
				timepicker_html += '</select>时';

				timepicker_html += '<select size="1">';
				for(var i=1; i<=60; i++){
					timepicker_html+= '<option value="'+i+'">'+i+'</option>';
				}
				timepicker_html += '</select>分';

				timepicker_html += '<select size="1">';
				for(var i=1; i<=60; i++){
					timepicker_html+= '<option value="'+i+'">'+i+'</option>';
				}
				timepicker_html += '</select>秒';
				timepicker_html += '<input type="button" value="确定"/>';

				this.getPanelEl('.datepicker_timepicker').setHtml(timepicker_html);
			}
			this.bindPanelEvent();
		};

		this.onDateSelect = function(){};

		/**
		 * get calendar panel element
		 * @return {object}
		 **/
		this.getPanel = function(){
			return this.panel;
		};

		/**
		 * adjust panel post relative to specified element
		 * @param {object} ele
		 **/
		this.adjustPanelPos = function(ele){
			var region = ele.getRegion();
			this.panel.setStyle({
				left: region.left,
				top: region.top + region.height + 4
			});
		};

		this.bindFormInput = function(input){
			var SID_KEY = '_date_picker_sid_';
			var _this = this;

			DatePicker.INSTANCES[this.config.sid] = this;
			input.setAttr(SID_KEY, this.config.sid);

			input.on('focus', function(){
				_this.initDateObj = _this.parseDateVal(this.value);
				_this.show(null, this);
			});

			this.onDateSelect = function(dateStr){
				input.setValue(dateStr);
				_this.hide();
			};

			Y.dom.one(document).on('click', function(){
				if(!_this.panel){
					return;
				}
				var tag = Y.event.getTarget();
				var sid = tag.getAttribute(SID_KEY);
				if(sid != _this.config.sid && !_this.panel.contains(tag)){
					_this.hide();
				}
			});
		};

		/**
		 * show calendar panel
		 * @param {string} dateStr
		 * @param {dom} ele
		 **/
		this.show = function(dateStr, ele){
			var ele = ele || this.config.formInput;
				ele = Y.dom.one(ele || document.body);
			if(!this.panel){
				this.createPanel();
			}
			this.adjustPanelPos(ele);

			var dateInfo = {};
			if(ele.getDomNode().tagName == 'INPUT' && ele.getDomNode().value){
				dateInfo = this.parseDateVal(ele.getDomNode().value);
			} else {
				dateInfo = this.parseDateVal(dateStr);
			}
			this.jumpToDate(dateInfo);
			this.panel.show();
		};

		/**
		 * hide calendar panel
		 * @param {boolean} bDestroyInstance
		 **/
		this.hide = function(){
			this.panel.hide();
		};

		/**
		 * get element in panel
		 * @param {string} selector
		 * @return {object}
		 **/
		this.getPanelEl = function(selector){
			var result = Y.dom.getEl(selector, this.panel.getDomNode());
			if(result.getNode){
				return result;
			} else {
				return result[0];
			}
		};

		/**
		 * jump to next month
		 **/
		this.jumpToNextMonth = function(){
			if(this.dateInfo.m == 12){
				this.dateInfo.y++;
				this.dateInfo.m = 1;
			} else {
				this.dateInfo.m ++;
			}
			this.jumpToDate(this.dateInfo);
		};

		/**
		 * jump to prev month
		 **/
		this.jumpToPrevMonth = function(){
			if(this.dateInfo.m == 1){
				this.dateInfo.y--;
				this.dateInfo.m = 12;
			} else {
				this.dateInfo.m --;
			}
			this.jumpToDate(this.dateInfo);
		};

		this.formatDate = function(dateObj, format){
			format = format || 'yyyy-mm-dd';
			var Y = dateObj.y+'',
				M = dateObj.m < 10 ? '0'+dateObj.m : dateObj.m,
				D = dateObj.d < 10 ? '0'+dateObj.d : dateObj.d;

			return format.replace('yyyy', Y).replace('yy', Y.slice(-2))
				.replace('mm', M).replace('m', dateObj.m)
				.replace('dd', D).replace('d', dateObj.d);
		};

		/**
		 * 跳转到指定日期
		 * @param {object} dateInfo
		 **/
		this.jumpToDate = function(dateInfo){
			this.dateInfo = dateInfo;
			var dateHash = this.getDateHash(dateInfo);

			var html = '';
			var dobj = new Date();
			for(var i=0; i<dateHash.length; i++){
				var _classList = [];
				var dateStr = this.formatDate(dateHash[i]);

				if(i<7 && dateHash[i].d>20){
					_classList.push(this.config.cssClass.lastMonthDate);
				} else if(i > 20 && dateHash[i].d < 15){
					_classList.push(this.config.cssClass.nextMonthDate);
				}

				//init date
				if(this.initDateObj.y == dateHash[i].y && this.initDateObj.m == dateHash[i].m && this.initDateObj.d == dateHash[i].d){
					_classList.push(this.config.cssClass.initDate);
				}

				//today
				if(dateHash[i].d == dobj.getDate() && dateHash[i].m == (dobj.getMonth()+1) && dateHash[i].y == dobj.getFullYear()){
					_classList.push(this.config.cssClass.today);
				}
				html += '<li class="'+_classList.join(' ')+'"><a href="javacript:;" rel="'+dateStr+'" title="'+dateStr+'">'+dateHash[i].d+'</a></li>';
			}
			this.getPanelEl('ul.datepicker_datelist').setHtml(html);

			if(this.config.showWeekColumn){
				var weekHash = this.getWeekHash(dateInfo);
				var html = '<li><span>'+weekHash.join('</span></li><li><span>')+'</span></li>';
				this.getPanelEl('.datepicker_weeklist').setHtml(html).show();
			}

			//year selector
			var yidx = '';
			var yr = this.config.yearRange.split('-');
			this.getPanelEl('*[rel=year-selector]').removeAllChildren();
			for(var i=yr[0]; i<=yr[1]; i++){
				this.getPanelEl('*[rel=year-selector]').getDomNode().appendChild(new Option(i,i));
				if(i == dateInfo.y){
					yidx = i-yr[0];
				}
			}
			this.getPanelEl('*[rel=year-selector]').getDomNode().selectedIndex = yidx;

			//month selector
			var mt = this.config.monthLang.split(',');
			this.getPanelEl('*[rel=month-selector]').removeAllChildren();
			for(var i=1; i<=12; i++){
				this.getPanelEl('*[rel=month-selector]').getDomNode().appendChild(new Option(mt[i-1],i));
			}
			this.getPanelEl('*[rel=month-selector]').getDomNode().selectedIndex = (dateInfo.m-1);
		};

		/**
		 * get week hash info
		 * 获取本月份的周数列表
		 **/
		this.getWeekHash = function(dateInfo){
			var start = new Date(dateInfo.y, 0, 1);
			var now = new Date(dateInfo.y, dateInfo.m, 1);
			var weekStart = Math.ceil((now.getTime() - start.getTime())/(86400*7*1000)) + 1;
			var weekHash = [];
			for(var i=0; i<6; i++){
				weekHash.push(weekStart+i-5);
			}
			return weekHash;
		};

		/**
		 * bind calendar panel element event
		 **/
		this.bindPanelEvent = function(){
			var _this = this;
			this.getPanelEl('*[rel=year-selector]').on('change', function(){
				_this.jumpToDate({y:this.value, m:_this.dateInfo.m, d:_this.dateInfo.d});
			});
			this.getPanelEl('*[rel=month-selector]').on('change', function(){
				_this.jumpToDate({y:_this.dateInfo.y, m:this.value, d:_this.dateInfo.d});
			});
			this.getPanelEl('*[rel=prev-month]').on('click', function(){
				_this.jumpToPrevMonth();
			});
			this.getPanelEl('*[rel=next-month]').on('click', function(){
				_this.jumpToNextMonth();
			});

			this._lastClickDateNode = null;
			this.getPanelEl('ul.datepicker_datelist').on('click', function(ev){
				var tag = Y.event.getTarget();
				if(tag.tagName == 'A'){
					if(_this._lastClickDateNode){
						_this._lastClickDateNode.removeClass(_this.config.cssClass.dateSelected);
					}
					_this._lastClickDateNode = Y.dom.getEl(tag);
					_this._lastClickDateNode.addClass(_this.config.cssClass.dateSelected);
					_this.onDateSelect(tag.getAttribute('rel'));
					Y.event.preventDefault();
					return;
				}
			});

			var scroll = function(e){
				e = e || Y.W.event;
				var dir;
				if(e.wheelDelta){
					dir = e.wheelDelta > 0 ? 1 : -1;
				} else if(e.detail){
					dir = e.detail < 0 ? 1 : -1;
				}
				_this[dir<0 ? 'jumpToNextMonth' : 'jumpToPrevMonth']();
			};
			this.getPanel('ul.datepicker_datelist').on('mousewheel', scroll);
			this.getPanel('ul.datepicker_datelist').on('DOMMouseScroll', scroll);
		};

		if(!DatePicker.INSTANCES){
			DatePicker.INSTANCES = [this];
			DatePicker.INSTANCES_LEN = 1;
		} else {
			DatePicker.INSTANCES.push(this);
			DatePicker.INSTANCES_LEN ++;
		}

		/**
		 * DatePicker construct function
		 **/
		this.config = YSL.object.extend(this.config, config);
		if(this.config.formInput){
			this.bindFormInput(this.config.formInput);
		}
	}

	DatePicker.INSTANCES = [];
	DatePicker.INSTANCES_LEN = 0;

	/**
	 * hide all calendar panel
	 **/
	DatePicker.hideAll = function(){
		for(var i in DatePicker.INSTANCES){
			DatePicker.INSTANCES[i].hide();
		}
	};

	/**
	 * auto bind page element
	 **/
	DatePicker.autoBindFormInputDatePicker = function(){
		Y.event.add(Y.W, 'load', function(){
			var inps = Y.dom.all('input[type=text]');
			inps.each(function(input){
				new DatePicker({formInput: input});
			});
		});
	};

	DatePicker.autoBindFormInputDatePicker();
	Y.W.DatePicker = DatePicker;
})(YSL);
