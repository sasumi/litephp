<?php

// declare(ticks = 1);
// register_tick_function(function(){
// 	$debug_list = debug_backtrace();
// 	echo "<hr/><PRE>";
// 	var_dump($debug_list[1]);
// });


//REQUIRE php 5.3 or above
if(version_compare(PHP_VERSION, '5.3.0') < 0){
	throw new Exception("REQUIRE PHP 5.3 OR ABOVE", 1);
}

session_start();
$GLOBALS['__USER_INCLUDE_PATH__'] = array();

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
function tpl($file_name=''){
	$file_name = $file_name ?: (PAGE == 'index' ? PAGE.'.php' : PAGE.'_'.ACTION.'.php');
	return TPL_PATH.strtolower($file_name);
}

/**
 * 测试
 **/
function dump(){
	echo '<pre style="background-color:#ddd; font-size:12px">';
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
	echo $info[0]['file'].' ['.$info[0][line].'] </pre>';
	if($die){
		die;
	}
}

/**
 * tick debug
 * @param {Number} $step_offset
 * @param {Function} $fun
 */
function tick_dump($step_offset=1, $fun='dump'){
	$step_offset = (string) $step_offset;
	if(strstr($step_offset, ',') !== false){
		list($start, $step) = array_map('intval', explode(',', $step_offset));
	} else {
		$start = 0;
		$step = intval($step_offset);
	}
	register_tick_function($fun);
	eval("declare(ticks = $step);");
}

function pdog($fun, $handler){
	declare(ticks = 1);
	register_tick_function(function()use($fun, $handler){
		$debug_list = debug_backtrace();
		foreach($debug_list as $info){
			if($info['function'] == $fun){
				call_user_func($handler, $info['args']);

			}
		}
	});
}

/**
 * add more include path
 * @param string $path
 */
function add_include_path($path){
    foreach (func_get_args() as $path){
        if (!file_exists($path) || (file_exists($path) && filetype($path) !== 'dir')){
			trigger_error("Include path '{$path}' not exists", E_USER_WARNING);
			continue;
        }

        $paths = explode(PATH_SEPARATOR, get_include_path());
        if(array_search($path, $paths) === false){
        	array_push($GLOBALS['__USER_INCLUDE_PATH__'], $path);
        	array_push($paths, $path);
        }
        set_include_path(implode(PATH_SEPARATOR, $paths));
    }
}

/**
 * remove include path from php setting
 * @param  string $path
 */
function remove_include_path($path){
    foreach (func_get_args() as $path){
        $paths = explode(PATH_SEPARATOR, get_include_path());

        if(($k = array_search($path, $GLOBALS['__USER_INCLUDE_PATH__'])) !== false){
        	unset($GLOBALS['__USER_INCLUDE_PATH__'][$k]);
        }

        if(($k = array_search($path, $paths)) !== false){
        	unset($paths[$k]);
        } else {
        	continue;
        }

        if(!count($paths)){
            trigger_error("Include path '{$path}' can not be removed because it is the only", E_USER_NOTICE);
			continue;
        }
        set_include_path(implode(PATH_SEPARATOR, $paths));
    }
}

/**
 * handler page logic
 * @param  array  $logic_config
 */
function handle_page_logic(array $logic_config){
	parser_get_request($current_page, $current_action, $request);
	foreach($logic_config as $logic){
		list($route, $caller) = $logic;
		$page = ROUTE_DEFAULT_PAGE;
		$action = ROUTE_DEFAULT_ACTION;

		if($route == '*'){
			$page = '*';
			$action = '*';
		} else if(strpos('/', $route) > 0){
			list($page, $action) = explode('/', $route);
		} else {
			$page = $route;
			$action = '*';
		}

		if($page == '*' || $page == $current_page){
			if($action == '*' || $action == $current_action){
				call_user_func($caller, $request, $current_page, $current_action);
			}
		}
	}
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

	//bind include path
	if(defined(INCLUDE_PATH)){
		add_include_path(INCLUDE_PATH);
	}

	//bind lib com path
	add_include_path(LIB_PATH.'com'.DS);

	//auto class loader
	spl_autoload_register(function($class){
		$paths = $GLOBALS['__USER_INCLUDE_PATH__'];
		foreach($paths as $path){
			$file = $path.strtolower($class).'.class.php';
			if(file_exists($file)){
				include $file;
			}
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

	//auto logic
	if(file_exists(CONFIG_PATH.'logic.inc.php')){
		$logic = include CONFIG_PATH.'logic.inc.php';
		handle_page_logic($logic);
	}

	//stat app launch time
	$GLOBALS['__init_time__'] = microtime(true);
	register_shutdown_function(function(){
		$fin_time = microtime(true);
		$run_time = round(($fin_time - $GLOBALS['__init_time__'])*1000, 2);
		fire_hook('AFTER_APP_SHUTDOWN', $run_time);
	});
}
lite();

