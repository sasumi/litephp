/**
 * YSL date module
 */
(function(Y){
	var _Date = {
		/**
		 * parse date string to YS.date object
		 * @param object tag
		 */
		parse: function(tag){
			var res = data = {}, def = {Y:0,m:1,d:1,H:0,i:0,s:0};
			
			if(typeof(tag) != 'string'){
				return Y.extend(tag, def);
			}
			
			tag = tag.replace(/(^\s*)|(\s*$)/g, '');
			
			//2010*8*20 23*59*59 233
			//2010*8*20 23*59*59
			if(res = tag.match(/(\d{4})\D*(\d{1,2})\D*(\d{1,2})\s*(\d{1,2})\D*(\d{1,2})\D*(\d{1,2})/)){
				data = {Y:res[1],m:res[2],d:res[3],H:res[4],i:res[5],s:res[6]};
			}
			
			//2010*8*20
			//2008/01/12
			else if(res = tag.match(/(\d{4})\D*(\d{1,2})\D*(\d{1,2})/)){
				data = {Y:res[1],m:res[2],d:res[3]};
			}
			
			//20*02*2003* 23*59*59
			else if(res = tag.match(/(\d{1,2})\D*(\d{1,2})\D*(\d{4})\s*(\d{1,2})\D*(\d{1,2})\D*(\d{1,2})/)){
				data = {Y:res[3], m:res[1], d:res[2], H:res[4], i:res[5], s:res[6]};
			}
			
			//parse error
			else {
				return null;
			}
			
			data = Y.extend(data, def);
			Y.lang.each(data, function(item, i){data[i] =  parseInt(item, 10);});
			return data;
		},
		
		/**
		 * get today date object
		 */
		today: function(){
			var D = new Date();
			return {
				Y:D.getFullYear(),
				m:D.getMonth()+1,
				d:D.getDate(),
				H:D.getHours(),
				i:D.getMinutes(),
				s:D.getSeconds()
			};
		},
		
		format: function(objDate, fmt){
			return '';
		},
		
		/**
		 * get offset between end_date and start_date(or today)
		 * @param {Object} end_date
		 * @param {Object} start_date or today
		 * @return {Object}
		 */
		offset: function(end_date, start_date){
			start_date = start_date || this.today();
			d2 = this.parse(end_date);
			d1 = this.parse(start_date);
			
			var s_d = new Date(d1.Y, d1.m-1, d1.d, d1.H, d1.i, d1.s),
				e_d = new Date(d2.Y, d2.m-1, d2.d, d2.H, d2.i, d2.s),
				RT = {};
			var offset = (e_d.getTime() - s_d.getTime());
			if(offset < 0){
				return {Y:0,m:0,d:0,H:0,i:0,s:0};
			}
			RT.Y = Math.floor(offset/(86400*365000));
			RT.m = Math.floor((offset - 86400*365000*RT.Y)/(86400*30000));
			RT.d = Math.floor((offset - 86400*365000*RT.Y - 86400*30000*RT.m)/(86400000));
			RT.H = Math.floor((offset - 86400*365000*RT.Y - 86400*30000*RT.m - 86400000*RT.d)/(3600000));
			RT.i = Math.floor((offset - 86400*365000*RT.Y - 86400*30000*RT.m - 86400000*RT.d - 3600000*RT.H)/(60000));
			RT.s = Math.floor((offset - 86400*365000*RT.Y - 86400*30000*RT.m - 86400000*RT.d - 3600000*RT.H - RT.i*60000)/1000);
			return RT;
		}
	};
	
	Y.com.date = _Date;
})(YSL);