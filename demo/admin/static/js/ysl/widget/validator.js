(function(Y){
	//使用到的正则表达式
	var REGEXP_COLLECTION = {
		REQUIRE: /^.+$/,									//必填
		CHINESE_ID: /^\d{14}(\d{1}|\d{4}|(\d{3}[xX]))$/,	//身份证
		PHONE: /^[0-9]{7,13}$/,								//手机+固话
		EMAIL: /^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/,		//emali
		POSTCODE: /^[0-9]{6}$/,								//邮编
		AREACODE: /^0[1-2][0-9]$|^0[1-9][0-9]{2}$/,			//区号
		CT_PASSPORT: /^[0-9a-zA-Z]{5,40}$/,					//电信账号
		CT_MOBILE: /^(13|15|18)[0-9]{9}$/,					//中国电信号码
		QQ: /^\d{5,13}$/,
		TRIM: /^\s+|\s+$/g
	};

	var Validator = function(config){
		this.config = Y.object.extend(true, {
			form: '#frm',
			rules: {
				/**
				'name': {
					require: '请输入用户名称',
					max20: '最大长度为20个字符',
					min4: '最小长度为4个字符'
				},
				'password': {
					require: '请输入用户密码',
					min6: '最小长度为6个字符',
					max32: '最大长度为32个字符'
				},
				'date': {
					date: '请输入正确的日期格式'
				}
				**/
			},
			checkAllRules: false,
			breakOnError: false,
			onAllCheckPass: function(){return true;},
			onCheckPass: function(item){
				var pn = Y.dom.one(item.parentNode);
				var span = pn.one('span.msg') || pn.create('span').addClass('msg');
				span.addClass('pass').removeClass('error').setHtml('ok');
			},
			onError: function(item, errs){
				var err = errs[0];
				var pn = Y.dom.one(item.parentNode);
				var span = pn.one('span.msg') || pn.create('span').addClass('msg');
				span.addClass('error').removeClass('pass').setHtml(err);
			},
			resetError: function(form){
				Y.dom.one(form).all('span.msg').each(function(span){
					span.removeClass('pass').removeClass('error');
					span.setHtml('')
				});
			}
		}, config);

		this.elements = [];
		this.errors = {};

		if(!this.config.form || !this.config.rules){
			throw('validate need form & rules');
		}
		this.init();
	};

	/**
	 * 初始化
	 */
	Validator.prototype.init = function(){
		var _this = this;
		this.elements = Y.dom.one(this.config.form).getDomNode().elements;
		Y.dom.one(this.config.form).on('submit', function(){
			_this.errors = {};
			_this.config.resetError(_this.config.form);
			var errorList = _this.checkAll();

			if(!errorList || Y.lang.isEmptyObject(errorList)){
				return _this.config.onAllCheckPass();		//check pass
			} else {
				Y.event.preventDefault();					//no pass
				return false;
			}
		});
	};

	/**
	 * check all elements
	 */
	Validator.prototype.checkAll = function(){
		for(var i=0; i<this.elements.length; i++){
			var element = this.elements[i],
				name = this.elements[i].name;

			if(this.checkElementCompitable(element)){
				//跳过已经检查的radio
				if(element.type == 'radio' && this.errors[element.name]){
					continue;
				}

				var errs = this.checkItem(element, this.config.rules[name], this.config.checkAllRules);
				if(errs){
					this.errors[name] = errs;
					this.config.onError(element, errs);
					if(this.config.breakOnError){
						return;
					}
				} else {
					this.config.onCheckPass(element);
				}
			}
		}
		return this.errors;
	};

	/**
	 * check element is compitable for validate
	 * @param {Object} element
	 * @return {Boolean}
	 */
	Validator.prototype.checkElementCompitable = function(element){
		return element.tagName != 'FIELDSET' &&
			element.type != 'hidden' &&
			element.type != 'submit' &&
			element.type != 'button' &&
			element.type != 'reset' &&
			element.type != 'image';
	}

	/**
	 * check single item
	 * @param {Object} element
	 * @param {Object} rules
	 * @param {Boolean} checkAllRules
	 * @return {Array|Null} error
	 */
	Validator.prototype.checkItem = function(element, rules, checkAllRules){
		if(!rules){
			return null;
		}

		var errors = [],
			name = element.name;
		if(element.tagName == 'SELECT' || (element.tagName == 'INPUT' && (element.type == 'text' || element.type == 'password'))){
			var val = element.value.replace(REGEXP_COLLECTION.TRIM, '');
			for(var key in rules){
				var uKey = key.toUpperCase();
				//正则表命中
				if(REGEXP_COLLECTION[uKey]){
					if(!REGEXP_COLLECTION[uKey].test(val)){
						if(!checkAllRules){
							return [rules[key]];
						} else {
							errors.push(rules[key]);
						}
					}
				}

				//最大长度
				else if(uKey.indexOf('MAX') === 0){
					var len = parseInt(uKey.substr(3), 10);
					if(len > 0 && len < val.length){
						if(!checkAllRules){
							return [rules[key]];
						} else {
							errors.push(rules[key]);
						}
					}
				}

				//最小长度
				else if(uKey.indexOf('MIN') === 0){
					var len = parseInt(uKey.substr(3), 10);
					if(len > 0 && len > val.length){
						if(!checkAllRules){
							return [rules[key]];
						} else {
							errors.push(rules[key]);
						}
					}
				}

				//自定义正则表达式
				else if(uKey.indexOf('/') === 0){
					var reg = new RegExp(key);
					if(!reg.test(val)){
						if(!checkAllRules){
							return [rules[key]];
						} else {
							errors.push(rules[key]);
						}
					}
				}

				//函数模式
				else if(typeof(rules[key]) == 'function'){
					var ret = rules[key](val);
					if(ret){
						if(!checkAllRules){
							return [ret];
						} else {
							errors.push(ret);
						}
					}
				}
			}
		}

		//checkbox 模式仅有require模式
		else if(element.type == 'checkbox'){
			for(var key in rules){
				var uKey = key.toUpperCase();
				if(uKey == 'REQUIRE'){
					if(!element.checked){
						return [rules[key]];
					} else {
						return null;
					}
				}
			}
		}

		//radio 模式仅有require模式
		else if(element.type == 'radio'){
			for(var key in rules){
				var uKey = key.toUpperCase();
				if(uKey == 'REQUIRE'){
					if(!this.checkRadioChecked(element.name)){
						return [rules[key]];
					} else {
						return null;
					}
				}
			}
		}
		return errors.length ? errors : null;
	};

	/**
	 * 检查radio是否已经checked
	 * @param {String} name
	 * @return {Boolean}
	 */
	Validator.prototype.checkRadioChecked = function(name){
		for(var i=0; i<this.elements.length; i++){
			if(this.elements[i].name == name && !!this.elements[i].checked){
				return true;
			}
		}
		return false;
	}
	Y.widget.Validator = Validator;
})(YSL);