YSL.use('app', function(Y){
	Y.App.prototype._MODULES = [];

	Y.object.extend(Y.App.prototype, {
		addModuler: function(id, config, scope){
			var modInfo = {
				id: id, 
				state: 1
			};
			this._MODULES.push({});
		},
		
		getModuler = function(id) {
			var rst;
			Y.lang.each(this._MODULES, function(mod){
				if(mod.id == id){
					rst = mod;
					return false;
				}
			});
			return rst;
		},

		removeModuler = function(id) {
			
		},

		loadModulerFile: function(id, callback){
			
		}
	});
})