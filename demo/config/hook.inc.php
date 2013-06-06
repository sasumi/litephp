<?php
add_hook('AFTER_APP_SHUTDOWN', function($time){
	echo "\r\n\r\n".'<br/>AFTER_APP_SHUTDOWN --> '.PAGE.'::'.ACTION;
	echo '<br/>use time:'.$time.'ms';
});

add_hook('AFTER_APP_INIT', function(){
	echo('<!--'.PAGE.'::'.ACTION." -->\r\n\r\n");
});

add_hook('BEFORE_APP_INIT', function(){
	echo('<!-- BEFORE_APP_INIT:'.$_SERVER['REQUEST_URI']." -->\r\n");
});

add_hook('ON_APP_EX', function($exception){
	print_exception($exception);
});

add_hook('ON_APP_ERR', function($code, $message, $file, $line, $context){
	dump($code, $message, $file, $line, $context);
});