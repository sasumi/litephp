<?php
return array();
// add_hook('AFTER_APP_SHUTDOWN', function($time){
// 	// echo '<center style="color:#ccc">耗时 '.$time.'ms</center>';
// });

// add_hook('AFTER_APP_INIT', function(){
// 	if(!Access::init()->checkLogin()){
// 		if(PAGE == 'resume'){
// 			Router::jumpTo();
// 		} else if(PAGE == 'user' && ACTION != 'login' && ACTION != 'register'){
// 			Router::jumpTo();
// 		}
// 	}
// });

// add_hook('BEFORE_APP_INIT', function(){
// 	return;
// 	echo('<!-- BEFORE_APP_INIT:'.$_SERVER['REQUEST_URI']." -->\r\n");
// });

// add_hook('xxxON_APP_EX', function($exception){
// 	print_exception($exception);
// });

// add_hook('ON_APP_ERR', function($code, $message, $file, $line, $context){
// 	dump($code, $message, $file, $line, $context);
// });

// add_hook('BEFORE_DB_QUERY', function($sql, $conn){
// 	echo '<!-- '.$sql.' -->';
// });