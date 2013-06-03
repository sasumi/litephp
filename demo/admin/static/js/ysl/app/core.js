(function(Y){
	var APP_COL = {};

	var App = function(id, config){
		this.id = id || Y.guid();
		this.setConfig(config);
		APP_COL.push({id: this.id, instance: this});
	};

	App.prototype = {
		getId: function(){
			return this.id;
		},

		setConfig: function(){
			this.config = Y.object.extend({
				root: '',
				
			}, config);
		},

		getConfig: function(key){
			return key ? this.config[key] : this.config;
		},

		launch: function(){

		},

		listen: function(ev, handler){

		},

		fire: function(ev, param1, param2){

		},

		watch: function(){

		},

		resolve = function(id){
			
		}
	};

	App.create = function(id, config){
		return new App(id, config);
	};

	App.get = function(id){
		if(APP_COL[id]){
			return APP_COL[id].instance;
		}
	}

	App.remove = function(id){
		if(APP_COL[id]){
			delete APP_COL[id];
		}
	};

	Y.App = App;
})(YSL);