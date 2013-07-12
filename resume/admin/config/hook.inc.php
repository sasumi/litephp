<?php
add_hook('AFTER_APP_SHUTDOWN', function($time){
	echo '<center style="color:#ccc">耗时 '.$time.'ms</center>';
});

add_hook('AFTER_APP_INIT', function(){
	if(!Access::init()->checkLogin() && PAGE != 'access' && ACTION != 'login'){
		//jump_to('access/login');
	}
});

add_hook('BEFORE_APP_INIT', function(){
	echo('<!-- BEFORE_APP_INIT:'.$_SERVER['REQUEST_URI']." -->\r\n");
});
/**
add_hook('ON_APP_EX', function($exception){
	print_exception($exception);
});

add_hook('ON_APP_ERR', function($code, $message, $file, $line, $context){
	dump($code, $message, $file, $line, $context);
});

add_hook('BEFORE_DB_QUERY', function($sql, $conn){
	echo '<!-- '.$sql.' -->';
});
**/