<?php
include 'function.php';
include 'string.php';
include 'filte.php';
include 'html.php';
include 'hook.php';
include 'pager.class.php';
include 'config.inc.php';

/**
 * 获取模版文件路径
 * @param string $template_path
 * @return string
**/
function tpl($file_name){
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
 * 获取path信息
 * @return string
**/
function get_path_info(){
	$uri = $_SERVER['REQUEST_URI'];
	$script_name = $_SERVER['SCRIPT_NAME'];
	$path_info = '';

	if(stripos($uri, $script_name) === 0){
		$path_info = substr($uri, strlen($script_name));
	} else {
		$script_path = preg_replace('/(.*\/)(.*?)$/ies', "'\\1'", $script_name);
		if(stripos($uri, $script_path) === 0){
			$path_info = substr($uri, strlen($script_path));
		}
	}

	$path_info = ltrim(rtrim($path_info, '/'), '/');
	if(strstr($path_info, '?') !== false){
		$path_info = substr($path_info, 0, strpos($path_info, '?'));
	}
	return $path_info;
}

/**
 * 解析GET请求
 * @param string &$page
 * @param string &$action
 * @param string &$params
**/
function parser_get_request(&$page='', &$action='', &$params=array()){
	preg_match('/(\w+)\.php$/i', $_SERVER['SCRIPT_FILENAME'], $match);
	$page = $match[1];

	switch(ROUTE_MODE){
		case 'REWRITE':
		case 'PATH':
			$path_info = get_path_info();
			preg_match('/(\w+)(\/|$)/', $path_info, $match);
			$action = $match ? $match[1] : null;

			$ps = explode('/', $path_info);
			$tmp = count($ps) > 1 ? array_slice($ps, 1) : array();

			$params = array();
			for($i=0; $i<=count($tmp); $i+=2){
				if($tmp[$i] !== NULL){
					$params[$tmp[$i]] = $tmp[$i+1];
				}
			}
			$params = array_merge($_GET, $params);
			break;

		case 'NORMAL':
		default:
			$action = $_GET[ROUTE_ACTION_KEY];
			unset($_GET[ROUTE_ACTION_KEY]);
			$params = $_GET;

	}

	$page = $page ?: ROUTE_DEFAULT_PAGE;
	$action = $action ?: ROUTE_DEFAUL_ACTION;
}

/**
 * 从$_GET中获取一个参数
 * @param string $key
 * @param string||array $rules
 * @param boolean $throwException
 * @return mix
 **/
function one_get_request($key, $rules, $throwException=true){
	parser_get_request($p, $a, $data);

	$data = $data ? $data[$key] : null;
	filte_one($data, $rules, $throwException);
	return $data;
}

/**
 * 从$_POST中获取一个参数
 * @param string $key
 * @param string||array $rules
 * @param boolean $throwException
 * @return mix
 **/
function one_post_request($key, $rules, $throwException=true){
	$data = $_POST ? $_POST[$key] : null;
	filte_one($data, $rules, $throwException);
	return $data;
}

/**
 * 路由
 * @param string $target
 * @param array $params
 * @return string
**/
function url($target='', $params=array()){
	list($page, $action) = explode('/', $target);
	$page = $page ?: ROUTE_DEFAULT_PAGE;
	$action = $action ?: ROUTE_DEFAUL_ACTION;
	$url = '';

	if(empty($params) && $page == ROUTE_DEFAULT_PAGE && $action == ROUTE_DEFAUL_ACTION){
		return APP_URL;
	}

	switch(ROUTE_MODE){
		case 'PATH':
			$url = APP_URL.$page.'.php'.(empty($params) && $action == ROUTE_DEFAUL_ACTION ? '' : '/'.$action);
			foreach($params as $k=>$p){
				$url .= "/".urlencode($k)."/".urlencode($p);
			}
			break;

		case 'REWRITE':
			$url = APP_URL.'/'.$page.(empty($params) && $action == ROUTE_DEFAUL_ACTION ? '' : '/'.$action);
			foreach($params as $k=>$p){
				$url .= "/".urlencode($k)."/".urlencode($p);
			}
			break;

		case 'NORMAL':
			default:
			$url = APP_URL.$page.'.php?'.ROUTE_ACTION_KEY.'='.$action;
			if($params){
				$url .= http_build_query($params);
			}
	}
	return $url;
}

/**
 * 页面302跳转
 **/
function jump_to(){
	$args = func_get_args();
	$url = call_user_func_array('url', $args);
	header('Location:'.$url);
	die;
}

/**
 * 获取模块文件夹列表
 * @param string $dir
 * @return array
**/
function get_file_list($dir) {
    $file_list = array();
    if(false != ($handle = opendir($dir))) {
        $i=0;
        while(false !== ($file = readdir($handle))) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".."&&!strpos($file,".")) {
                $file_list[$i]=$file;
                $i++;
            }
        }
        closedir ($handle);
    }
    return $file_list;
}

/**
 * 获取当前访问url
 * @return string
 **/
function this_url(){
	$host = $_SERVER['HTTP_HOST'];
	$protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') ? 'https://' : 'http://';
	$port = $_SERVER['SERVER_PORT'] == 80 ? null : $_SERVER['SERVER_PORT'];
	$uri = $_SERVER['REQUEST_URI'];
	return $protocol.$host.($port ? ':'.$port : '').$uri;
}

/**
 * lite初始化
**/
function lite(){
	//import hook
	if(file_exists(CONFIG_PATH.'hook.inc.php')){
		include CONFIG_PATH.'hook.inc.php';
	}

	if(file_exists(CONFIG_PATH.'database.inc.php')){
		include LIB_PATH.'db/db.php';
	}

	fire_hook('BEFORE_APP_INIT');

	//APP EXCEPTION
	if(has_hook('ON_APP_EX')){
		set_exception_handler(function($exception){
			fire_hook('ON_APP_EX', exception);
		});
	}

	//APP ERROR
	if(has_hook('ON_APP_ERR')){
		set_error_handler(function($code, $message, $file, $line, $context){
			fire_hook('ON_APP_ERR', $code, $message, $file, $line, $context);
		}, E_USER_ERROR | E_USER_WARNING);
	}

	//auto class loader
	if(file_exists(INCLUDE_PATH)){
		spl_autoload_register(function($class){
			$file = INCLUDE_PATH.strtolower($class).'.class.php';
			if(file_exists($file)){
				include_once $file;
			}
		});
	}

	parser_get_request($M, $A, $gets);
	define('PAGE', $M);
	define('ACTION', $A);

	//hack $_GET
	if(ROUTE_MODE != 'NORMAL'){
		$_GET = $gets;
	}

	fire_hook('AFTER_APP_INIT');
	$GLOBALS['__init_time__'] = microtime(true);
	register_shutdown_function(function(){
		$fin_time = microtime(true);
		$run_time = ($fin_time - $GLOBALS['__init_time__'])*1000;
		fire_hook('AFTER_APP_SHUTDOWN', $run_time);
	});
}
lite();