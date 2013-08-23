<?php
add_hook('AFTER_APP_SHUTDOWN', function($time){
	if(ACTION !== ROUTE_DEFAUL_ACTION){
		$file_name = strtolower(PAGE).'_'.strtolower(ACTION).'.php';
	} else {
		$file_name = strtolower(PAGE).'.php';
	}
	$tpl_file = tpl($file_name);
	if(file_exists($tpl_file)){
		include($tpl_file);
	}
});