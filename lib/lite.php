<?php
//REQUIRE php 5.3 or above
if(version_compare(PHP_VERSION, '5.3.0') < 0){
	throw new Exception("REQUIRE PHP 5.3 OR ABOVE", 1);
}

session_start();
include 'config.inc.php';
include 'function.php';
include 'string.php';
include 'filte.php';
include 'html.php';
include 'hook.php';
include 'route.php';
include 'file.php';

/**
 * 获取模版文件路径
 * @param string $template_path
 * @return string
**/
function tpl($file_name=null){
	if(!$file_name){
		$file_name = ACTION == 'index' ? PAGE.'.php' : PAGE.'_'.ACTION.'.php';
		return TPL_PATH.$file_name;
	}
	return TPL_PATH.$file_name;
}

/**
 * 检测当前请求是否为POST
 * @return boolean
**/
function is_post(){
	return $_SERVER['REQUEST_METHOD'] == 'POST';
}

/**
 * 检测当前请求是否为GET
 * @return boolean
**/
function is_get(){
	return $_SERVER['REQUEST_METHOD'] == 'GET';
}

/**
 * 测试
 **/
function dump(){
	echo '<pre>';
	$args = func_get_args();
	$last = array_slice($args, -1, 1);
	$die = $last[0] === 1;
	if($die){
		$args = array_slice($args, 0, -1);
	}
	if($args){
		call_user_func_array('var_dump', $args);
	}
	$info = debug_backtrace();
	echo $info[0]['file'].' ['.$info[0][line].'] ';
	if($die){
		die;
	}
}

/**
 * tick debug
 * @param {Number} $step_offset
 * @param {Function} $fun
 */
function tick_dump($step_offset=1, $fun=dump){
	$step_offset = (string) $step_offset;
	if(strstr($step_offset, ',') !== false){
		list($start, $step) = array_map('intval', explode(',', $step_offset));
	} else {
		$start = 0;
		$step = intval($step_offset);
	}
	$GLOBALS['TICK_DEBUG_START_INDEX'] = $start;
	register_tick_function($fun);
	eval("declare(ticks = $step);");
}

/**
 * [dump_as_html_comment description]
 */
function dump_as_html_comment(){

}

/**
 * lite初始化
**/
function lite(){
	//import hook
	if(file_exists(CONFIG_PATH.'hook.inc.php')){
		include CONFIG_PATH.'hook.inc.php';
	}

	//load db library
	if(file_exists(CONFIG_PATH.'db.inc.php')){
		include LIB_PATH.'db/db.php';
	}

	//copy htaccess file
	if(ROUTE_MODE == ROUTE_MODE_REWRITE && !file_exists(APP_PATH.'.htaccess')){
		$result = copy(LIB_PATH.'htaccess', APP_PATH.'.htaccess');
		if(!$result){
			throw new Exception('route file copy fail');
		}
		reload();
		return;
	}

	fire_hook('BEFORE_APP_INIT');

	//APP EXCEPTION
	if(has_hook('ON_APP_EX')){
		set_exception_handler(function($exception){
			fire_hook('ON_APP_EX', $exception);
		});
	}

	//APP ERROR
	if(has_hook('ON_APP_ERR')){
		set_error_handler(function($code, $message, $file, $line, $context){
			fire_hook('ON_APP_ERR', $code, $message, $file, $line, $context);
		}, E_USER_ERROR | E_USER_WARNING);
	}

	//auto class loader
	spl_autoload_register(function($class){
		$file = INCLUDE_PATH.strtolower($class).'.class.php';
		$file2 = LIB_PATH.strtolower($class).'.class.php';
		if(file_exists($file)){
			include_once $file;
		} else if(file_exists($file2)){
			include_once $file2;
		}
	});

	parser_get_request($M, $A, $gets);

	define('PAGE', $M);
	define('ACTION', $A);

	//hack $_GET
	if(ROUTE_MODE != ROUTE_MODE_NORMAL){
		$_GET = $gets;
	}

	fire_hook('AFTER_APP_INIT');

	//stat app launch time
	$GLOBALS['__init_time__'] = microtime(true);
	register_shutdown_function(function(){
		$fin_time = microtime(true);
		$run_time = ($fin_time - $GLOBALS['__init_time__'])*1000;
		fire_hook('AFTER_APP_SHUTDOWN', $run_time);
	});
}
lite();