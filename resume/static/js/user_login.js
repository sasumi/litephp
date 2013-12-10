var response = function(msg, code){
	if(code == 'succ'){
		R.showTip(msg, code);
		setTimeout(function(){
			location.href =R.url('user/info');
		}, 2000);
	} else {
		R.showTip(msg, 'err');
	}
}