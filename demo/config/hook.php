<?php
add_hook('AFTER_APP_SHUTDOWN', function($time){
	echo '<br/>AFTER_APP_SHUTDOWN --> '.PAGE.'::'.ACTION;
	echo '<br/>use time:'.$time.'ms';
});

add_hook('AFTER_APP_INIT', function(){
	echo('<div style="font-size:12px; color:gray;">AFTER_APP_INIT --> '.PAGE.'::'.ACTION.'</div>');
});

add_hook('BEFORE_APP_INIT', function(){
	echo('<div style="font-size:12px; color:gray;">BEFORE_APP_INIT:'.$_SERVER['REQUEST_URI'].'</div>');
});

add_hook('ON_APP_EX', function($exception){
	debug($exception, 1);
});

add_hook('ON_APP_ERR', function($code, $message, $file, $line, $context){
	debug($code, $message, $file, $line, $context);
});