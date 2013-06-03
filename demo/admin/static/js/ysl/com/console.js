(function(Y){
	var CONSOLE_ALL = 0;
	var CONSOLE_LOG = 1;
	var CONSOLE_WARN = 2;
	var CONSOLE_ERROR = 3;
	var CONSOLE_EXCEPTION = 4;
	var CONSOLE_PANEL = null;
	var COLOR_SET = {
		'string': 'orange',
		'number': 'blue',
		'object': 'red',
		'function': 'green',
		'undefined': 'gray',
		'log': 'gray',
		'warn': 'orange',
		'error': 'red',
		'exception': 'red'
	};

	var _Console = {level: CONSOLE_ALL};

	_Console.setLevel = function(level){
		this.level = level;
	};

	/**
	 * print args
	 * @param  {array} arguments arguments
	 * @param  {string} type
	 */
	_Console.print = function(arr, type){
		type = type || CONSOLE_LOG;
		var args = Y.lang.toArray(arr);
		if(!args){
			return;
		}
		if(false && Y.W.console){
			Y.W.console[type].apply(Y.W.console, args);
		} else {
			if(!CONSOLE_PANEL){
				CONSOLE_PANEL = Y.D.createElement('div');
				CONSOLE_PANEL.style.cssText = 'position:absolute; width:250px; height:300px; box-shadow:1px 1px 5px #666; overflow-y:auto; border:4px solid #ddd; padding:10px; font-size:12px; background-color:#eee; color:green; bottom:10px; right:10px; position:fixed !important;';
				Y.D.body.appendChild(CONSOLE_PANEL);
			}

			Y.lang.each(args, function(msg){
				var type = Y.lang.getType(msg);
				var color = COLOR_SET[type];
				CONSOLE_PANEL.innerHTML += '<div style="border-bottom:1px dashed #ddd; padding:3px 0; color:'+color+'">' + (msg || '['+type+']')+ '</div>';
			});
		}
	}

	_Console.log = function(){
		this.print(arguments, 'log');
	}

	_Console.warn = function(){
		this.print(arguments, 'warn');
	}

	_Console.error = function(){
		this.print(arguments, 'error');
	}

	_Console.exception = function(){
		this.print(arguments, 'error');
	};

	Y.com.console = _Console;
})(YSL);