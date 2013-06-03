/**
 * YSL widget slide module
 */
(function(Y){
	Y.widget.slide = function(ctrl, ctn, cfg){
		var ctn_lst = Y.dom.one(ctn).getChildren();
		if(ctn_lst.length < 2){
			return false;
		}
		var ctrl_lst = Y.dom.one(ctrl).getChildren(),
			from_idx = 1,
			tag_idx = 2,
			timer = null,
			cfg = cfg || {},
			def_cfg = {animate: false,pause_time: 2000,cur_cls:'current'};
		cfg = Y.object.extend(cfg, def_cfg);

		//显示第tag_idx[1~len]个
		var static_handler = function(){
			Y.lang.each(ctn_lst, function(itm){itm.removeClass(cfg.cur_cls);});
			Y.lang.each(ctrl_lst, function(itm){itm.removeClass(cfg.cur_cls);});
			ctn_lst[tag_idx-1].addClass(cfg.cur_cls);
			ctrl_lst[tag_idx-1].addClass(cfg.cur_cls);
			cur_idx = tag_idx;
			tag_idx = (tag_idx==ctn_lst.length) ? 1 : (tag_idx+1);
		};

		//开始动作
		var start = function(){
			timer = cfg.animate ? setInterval(animate_handler, cfg.pause_time,0) :
				setInterval(static_handler, cfg.pause_time, 0);
		};

		//暂停动作
		var pause = function(){clearInterval(timer);};

		//监听mouse动作执行停止或者开始
		void function(){
			Y.lang.each(ctrl_lst,function(item, idx){
				item.on('mouseover', function(){tag_idx = (parseInt(idx, 10)+1); static_handler(); pause();});
			});
			Y.lang.each(ctrl_lst, function(item){item.on('mouseout', function(){start();});});
		}();
		start();
	};
})(YSL);

