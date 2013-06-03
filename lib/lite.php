<?php
include_once 'function.php';
include_once 'pager.php';
include_once 'html.php';
include_once 'hook.php';
include_once 'db.php';

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
	call_user_func_array('var_dump', $args);
	if($die){
		die;
	}
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
 * 过滤数据
 * @param array &$data
 * @param array $rules
**/
function filte_array(array &$data, array $rules, $throwException=true){
	$err_msgs = array();
	$filted_data = array();
	foreach($data as $key=>$val){
		if($rules[$key]){
			$pass = true;
			try {
				filte_one($val, $rules[$key]);
				} catch(Exception $e){
				$pass = false;
				if(!$err_msgs[$key]){
					$err_msgs[$key] = array();
				}
				$err_msgs[$key] = array_merge($err_msgs[$key], $e->getMsgList());
			}
			
			if($pass){
				$filted_data[$key] = $val;
			}
		}
	}
	$data = $filted_data;
	if(!empty($err_msgs) && $throwException){
		throw new FilteException($err_msgs,  $data, $rules);
	}
}

/**
 * 过滤一个数据项
 * @param mix &$data
 * @param string||array $rules
**/
function filte_one(&$data, $rules, $throwException=true){
	$__DEF_REG_RULES__ = array(
		'REQUIRE' => "/^.+$/",										//必填
		'CHINESE_ID' => "/^\d{14}(\d{1}|\d{4}|(\d{3}[xX]))$/",		//身份证
		'PHONE' => "/^[0-9]{7,13}$/",								//手机+固话
		'EMAIL' => "/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/",		//emali
		'POSTCODE' => "/^[0-9]{6}$/",								//邮编
		'AREACODE' => "/^0[1-2][0-9]$|^0[1-9][0-9]{2}$/",			//区号
		'CT_PASSPORT' => "/^[0-9a-zA-Z]{5,40}$/",					//电信账号
		'CT_MOBILE' => "/^(13|15|18)[0-9]{9}$/",					//中国电信号码
		'QQ' => "/^\d{5,13}$/",
		'NUMBER' => '/^\d+$/',
		'KEY' => '/^\w+$/',
		'TRIM' => "/^\s+|\s+$/g",
		'DATE' => ''
	);
	$check = function($data, $key, $msg){
		$err_msgs = array();
		$def_reg = $__DEF_REG_RULES__[strtoupper($key)];	//内置正则规则命中
		
		if($def_reg){
			if(!preg_match($def_reg, $data)){
				return $msg;
			}
		} else if(stripos($key, 'min') === 0){
			$min = (int)substr($key, 3);
			if($min && strlen($data) < $min){
				return $msg;
			}
		} else if(stripos($key, 'max') === 0){
			$max = (int)substr($key, 3);
			if($max && strlen($data) > $max){
				return $msg;
			}
		} else if(strpos('/', $key) === 0){
			if(!preg_match($key, $data)){
				return $msg;
			}
		} else {
			if(!call_user_func($key, $data)){
				return $msg;
			}
		}
		return null;
	};
	
	$err_msgs = array();
	if(is_string($rules) && $__DEF_REG_RULES__[$rules]){
		$def_reg = $__DEF_REG_RULES__[$rules];
		if(!preg_match($def_reg, $data)){
			$err_msgs = array($rules);
		}
	}
	else if(is_array($rules)){
		foreach($rules as $rule=>$msg){
			$err = $check($data, $rule, $msg);
			if($err){
				$err_msgs[] = $err;
			}
		}
	}
	
	if(!empty($err_msgs) && $throwException){
		$data = null;
		throw new FilteException($err_msgs, $data, $rules);
	}
}

/**
 * 过滤器异常类
**/
class FilteException extends Exception {
	private $msg_arr;
	private $data;
	protected $rules;
	private $trace_info;
	
	public function __construct($msg_arr=array(), $data=array(), $rules=array()){
		$this->msg_arr = $msg_arr;
		$this->data = $data;
		$this->rules = $rules;
		$this->trace_info = debug_backtrace();
	}
	
	public function getMsgList(){
		return $this->msg_arr;
	}
	
	public function getOneMsg(){
		$tmp = array_pop($this->msg_arr);
		return is_array($tmp) ? array_shift($tmp) : $tmp;
	}
	
	public function dump(){
		$html .= '<b>Errors:</b><br/>';
		$html .= '<b>'.var_dump($this->msg_arr).'</b>';
		$html .= '<ul>';
		foreach($this->trace_info as $t){
			$html .= '<li>'.$t['file'].' -- &lt;'.$t['line'].'&gt;</li>';
		}
		$html .= '</ul>';
		echo $html;
	}
}

/**
 * lite初始化
**/
function lite(){
	//系统路径初始化
	if(!defined('APP_PATH')){
		define('APP_PATH', str_replace('\\','/', dirname(dirname(__FILE__)).'/'));
	}
	if(!defined('PAGE_PATH')){
		define('PAGE_PATH', APP_PATH);
	}
	if(!defined('TPL_PATH')){
		define('TPL_PATH', APP_PATH.'template/');
	}
	if(!defined('LIB_PATH')){
		define('LIB_PATH', APP_PATH.'lib/');
	}
	if(!defined('CONFIG_PATH')){
		define('CONFIG_PATH', APP_PATH.'config/');
	}
	if(!defined('INCLUDE_PATH')){
		define('INCLUDE_PATH', APP_PATH.'include/');
	}

	//资源URL初始化
	if(!defined('APP_URL')){
		define('APP_URL', '/');
	}
	if(!defined('STATIC_URL')){
		define('STATIC_URL', APP_URL.'static/');
	}
	if(!defined('JS_URL')){
		define('JS_URL', STATIC_URL.'js/');
	}
	if(!defined('IMG_URL')){
		define('IMG_URL', STATIC_URL.'img/');
	}
	if(!defined('CSS_URL')){
		define('CSS_URL', STATIC_URL.'css/');
	}

	//路由配置初始化
	if(!defined('ROUTE_MODE')){
		define('ROUTE_MODE', 'PATH');
	}
	if(!defined('ROUTE_ACTION_KEY')){
		define('ROUTE_ACTION_KEY', 'act');
	}
	if(!defined('ROUTE_DEFAULT_PAGE')){
		define('ROUTE_DEFAULT_PAGE', 'index');
	}
	if(!defined('ROUTE_DEFAUL_ACTION')){
		define('ROUTE_DEFAUL_ACTION', 'index');
	}
	
	INCLUDE_PATH;

	//import hook
	if(file_exists(CONFIG_PATH.'hook.php')){
		include_once CONFIG_PATH.'hook.php';
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

	parser_get_request($M, $A, $gets);
	define('PAGE', $M);
	define('ACTION', $A);

	//hack $_GET
	if(ROUTE_MODE != 'NORMAL'){
		$_GET = $gets;
	}

	fire_hook('AFTER_APP_INIT');
	register_shutdown_function(function(){
		fire_hook('AFTER_APP_SHUTDOWN');
	});
}
lite();