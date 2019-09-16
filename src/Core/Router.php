<?php
namespace Lite\Core;

use Lite\Component\Net\Http;
use Lite\Component\String\Html;
use Lite\Exception\Exception;
use Lite\Exception\RouterException;
use function Lite\func\array_merge_recursive_distinct;
use function Lite\func\decodeURIComponent;
use function Lite\func\str_start_with;

/**
 * 路由基础类。当前路由路由基础类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class Router{
	const EVENT_BEFORE_ROUTER_INIT = __CLASS__ . 'EVENT_BEFORE_ROUTER_INIT';
	const EVENT_AFTER_ROUTER_INIT = __CLASS__ . 'EVENT_AFTER_ROUTER_INIT';
	const EVENT_GET_STATIC_URL = __CLASS__ . 'EVENT_ROUTER_GET_STATIC_URL';
	const EVENT_BEFORE_PARSE_CURRENT_REQUEST = __CLASS__ . 'EVENT_BEFORE_PARSE_CURRENT_REQUEST';
	
	const DEFAULT_ROUTER_KEY = 'r';
	
	const MODE_NORMAL = 0x01;
	const MODE_PATH = 0x02;
	const MODE_REWRITE = 0x03;

	public static $ROUTER_KEY = self::DEFAULT_ROUTER_KEY;
	public static $RETURN_URL_KEY = '_ret_';
	public static $DEFAULT_CONTROLLER = '';
	public static $DEFAULT_ACTION = '';
	
	private static $CONTROLLER = '';
	private static $ACTION = '';

	public static $GET = array();
	public static $POST = array();
	public static $PUT = array();
	public static $DELETE = array();
	
	/**
	 * 读取PHP输入源
	 * @return string
	 */
	public static function readInputData(){
		$data = file_get_contents('php://input');
		return $data;
	}
	
	/**
	 * 分块读取PHP输入源
	 * @param $handler
	 * @param int $chunk_size
	 */
	public static function readInputDataChunk($handler, $chunk_size = 1024){
		$fp = fopen('php://input', 'r');
		while($data = fread($fp, $chunk_size)){
			$handler($data);
		}
		fclose($fp);
	}
	
	/**
	 * listen path rule
	 * @todo to be tested & considering conflict on normal url format
	 * @param $path
	 * @param $handler
	 * @return bool
	 */
	public static function listen($path, $handler){
		return true;
	}
	
	/**
	 * 获取当前路由控制key
	 * 仅在路由模式为normal时有效
	 * @return string|null
	 */
	public static function getRouterKey(){
		return self::$ROUTER_KEY;
	}

	/**
	 * 获取系统配置默认controller
	 * @return string
	 */
	public static function getDefaultController(){
		return self::$DEFAULT_CONTROLLER;
	}
	
	/**
	 * 获取系统配置默认action
	 * @return string
	 */
	public static function getDefaultAction(){
		return self::$DEFAULT_ACTION;
	}
	
	/**
	 * 获取当前调用controller
	 * @return string
	 */
	public static function getController(){
		return self::$CONTROLLER;
	}

	/**
	 * 获取controller常用缩写（去除app命名空间，翻转斜杠）
	 * @return string
	 */
	public static function getControllerAbbr(){
		$controller = self::getController();
		$ctrl = self::resolveNameFromController($controller);
		return $ctrl;
	}

	/**
	 * 获取当前路径URI
	 * @return string
	 */
	public static function getCurrentUri(){
		return self::getControllerAbbr().'/'.self::getAction();
	}
	
	/**
	 * 获取附带返回当前页面地址的URL
	 * @param string $uri
	 * @param array $param
	 * @param bool $force_current_page 是否锁定返回当前页面，如设置为true，则忽略当前页面传入的返回URL参数
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getUrlWithReturnUrl($uri = '', $param = [], $force_current_page = false){
		if($force_current_page){
			$param[static::$RETURN_URL_KEY] = Router::getCurrentPageUrl();
		} else {
			$param[static::$RETURN_URL_KEY] = Router::get(static::$RETURN_URL_KEY) ?: Router::getCurrentPageUrl();
		}
		return static::getUrl($uri, $param);
	}
	
	/**
	 * 获取返回URL
	 * 获取当前页面路径中的返回URL地址，若为空（或非法传入），则返回指定uri、param页面
	 * @return string
	 */
	public static function getReturnUrl(){
		$return_url = Router::get(static::$RETURN_URL_KEY);
		if($return_url){
			$tmp = parse_url($return_url);
			//support empty host format
			if(!$tmp['host'] || $tmp['host'] === $_SERVER['HTTP_HOST']) {
				return $return_url;
			}
		}
		return '';
	}
	
	/**
	 * 获取当前调用action
	 * @return string
	 */
	public static function getAction(){
		return self::$ACTION;
	}
	
	/**
	 * 获取$_GET变量
	 * @param null $key
	 * @return mixed
	 */
	public static function get($key = null){
		return !$key ? self::$GET : (isset(self::$GET[$key]) ? self::$GET[$key] : null);
	}
	
	/**
	 * 获取$_POST变量
	 * @param null $key
	 * @return null
	 */
	public static function post($key = null){
		return !$key ? self::$POST : self::$POST[$key];
	}
	
	/**
	 * get request
	 * @param null $key
	 * @return mixed
	 */
	public static function request($key = null){
		$req = array_merge($_REQUEST, self::$GET);
		return $key ? $req[$key] : $req;
	}
	
	/**
	 * 获取PUT变量
	 * @param null $key
	 * @return array
	 */
	public static function put($key = null){
		return !$key ? self::$PUT : self::$PUT[$key];
	}
	
	/**
	 * 获取DELETE变量
	 * @param null $key
	 * @return array
	 */
	public static function delete($key = null){
		return !$key ? self::$DELETE : self::$DELETE[$key];
	}
	
	/**
	 * 解析路由请求到指定的C/A
	 * @throws \Lite\Exception\RouterException
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	private static function parseCurrentRequest(){
		$get = $_GET;
		$router_mode = Config::get('router/mode');
		$path_info = self::getPathInfo();

		//检查重写规则是否命中
		if($router_mode == self::MODE_REWRITE &&
			list($uri, $param) = Rewrite::onParseRequest($path_info, $get)){
			list($controller, $action) = self::resolveUri($uri, true);
			return [$controller, $action, $param];
		}

		//优先query参数
		if((isset($get[self::$ROUTER_KEY]) && $get[self::$ROUTER_KEY]) || $router_mode == self::MODE_NORMAL){
			list($controller, $action) = self::resolveUri($get[self::$ROUTER_KEY], true);
			unset($get[self::$ROUTER_KEY]);
		} else {
			list($controller, $action, $param) = self::resolveCurrentRequestPath($path_info);
			$get = array_merge($param, $get);
		}

		//安全保护
		if(!preg_match('/^[\w|\\\]+$/', $controller) || !preg_match('/^\w+$/', $action)){
			throw new RouterException('PARAMETER ILLEGAL', null, array('controller'=>$controller, 'action' => $action));
		}

		//自动decode
		if(!empty($get)){
			array_walk_recursive($get, function(&$item){
				if(is_string($item)){
					$item = urldecode($item);
				}
			});
		}
		return array($controller, $action, $get);
	}
	
	/**
	 * 解析请求参数到当前环境的Controller、Action、GET、POST
	 * @throws \Lite\Exception\Exception
	 */
	public static function init(){
		Hooker::fire(self::EVENT_BEFORE_ROUTER_INIT);
		self::$ROUTER_KEY = Config::get('router/router_key');
		self::$DEFAULT_CONTROLLER = Application::getNamespace().'\\controller\\'.Config::get('router/default_controller').'Controller';
		self::$DEFAULT_ACTION = Config::get('router/default_action');
		self::$POST = $_POST;

		list(self::$CONTROLLER, self::$ACTION, self::$GET) = self::parseCurrentRequest();
		$_GET = self::$GET; //reset $_GET
		Hooker::fire(self::EVENT_AFTER_ROUTER_INIT, self::$CONTROLLER, self::$ACTION, self::$GET, self::$POST);
	}
	
	/**
	 * 获取path信息
	 * @return string
	 **/
	public static function getPathInfo(){
		if($_SERVER['PATH_INFO']){
			if(stripos($_SERVER['PATH_INFO'], '/index.php/') == 0){
				$_SERVER['PATH_INFO'] = str_replace('/index.php/', '', $_SERVER['PATH_INFO']);
			}
			return trim($_SERVER['PATH_INFO'], '/');
		}

		$uri = $_SERVER['REQUEST_URI'];
		$script_name = $_SERVER['SCRIPT_NAME'];
		$path_info = '';
		
		if(stripos($uri, $script_name) === 0){
			$path_info = substr($uri, strlen($script_name));
		} else{
			$script_path = preg_replace('/(.*\/)(.*?)$/i', "'\\1'", $script_name);
			if(stripos($uri, $script_path) === 0){
				$path_info = substr($uri, strlen($script_path));
			}
		}
		
		$path_info = trim($path_info, '/');
		if(strstr($path_info, '?') !== false){
			$path_info = substr($path_info, 0, strpos($path_info, '?'));
		}
		return $path_info;
	}
	
	/**
	 * 检测当前请求是否为POST
	 * @return boolean
	 **/
	public static function isPost(){
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
	
	/**
	 * 检测当前请求是否为GET
	 * @return boolean
	 **/
	public static function isGet(){
		return $_SERVER['REQUEST_METHOD'] == 'GET';
	}
	
	/**
	 * 检测当前请求是否为PUT
	 * @return boolean
	 **/
	public static function isPut(){
		return $_SERVER['REQUEST_METHOD'] == 'PUT';
	}
	
	/**
	 * 检测当前请求是否为DELETE
	 * @return boolean
	 **/
	public static function isDelete(){
		return $_SERVER['REQUEST_METHOD'] == 'DELETE';
	}
	
	/**
	 * 产生表单action
	 * @param string $uri
	 * @param array $params
	 * @param array $fields_exclude_from_current_query 保留当前URL中的查询数据
	 * @return string
	 */
	public static function getFormAction($uri = '', $params = array(), $fields_exclude_from_current_query = array()){
		$params = $params ?: array();
		if($fields_exclude_from_current_query){
			$tmp = static::get();
			foreach($fields_exclude_from_current_query as $field){
				unset($tmp[$field]);
			}
			$params = array_merge($tmp, $params);
		}
		$html = Html::htmlHidden(self::$ROUTER_KEY, $uri);
		foreach($params as $name => $p){
			$html .= Html::htmlHidden($name, $p);
		}
		return $html;
	}

	/**
	 * 解析URI
	 * @param string $uri
	 * @param bool $force_class_exists
	 * @return array [string,string] Controller类名（包含命名空间），action方法名称
	 */
	private static function resolveUri($uri = '', $force_class_exists = false){
		$c = null;
		$a = self::$DEFAULT_ACTION;
		$uri = trim($uri, '/ ');
		if($uri){
			$tmp = explode('/', $uri);
			switch(count($tmp)){
				case 1:
					list($c) = $tmp;
					break;

				case 2:
					list($c, $a) = $tmp;
					break;

				default:
					$a = array_pop($tmp);
					$c = join('/', $tmp);
					break;
			}
		}
		$c = $c ? self::patchControllerFullName($c, $force_class_exists) : self::$DEFAULT_CONTROLLER;
		return [$c, $a];
	}

	/**
	 * 从controller类名中解析对应名称（包含子级目录）
	 * 同时转换namespace里面的反斜杠\ 成斜杠 /
	 * @param $controller
	 * @return string
	 */
	public static function resolveNameFromController($controller){
		$ns = Application::getNamespace();
		$preg = "/^".preg_quote($ns)."\\\\controller\\\\(.*?)Controller/";
		$ctrl = preg_replace($preg, '$1', $controller);
		return str_replace('\\','/',$ctrl);
	}

	/**
	 * 从pathinfo中解析参数（pathinfo已去除URI信息）
	 * @param $path_arr
	 * @param bool $resolve_array
	 * @return array
	 */
	private static function resolveParamFromPath($path_arr, $resolve_array = true){
		$param = array();
		for($i=0; $i<count($path_arr); $i+=2){
			$k = $path_arr[$i];
			$v = decodeURIComponent($path_arr[$i+1]);
			if($resolve_array && preg_match('/\[.*?\]/', $k)){
				parse_str($k.'='.$v, $tmp);
				$param = array_merge_recursive_distinct($param, $tmp);
			} else {
				$param[$k] = $v;
			}
		}
		return $param;
	}

	/**
	 * 从pathinfo中解析出控制器、动作以及参数
	 * @param $path_info
	 * @return array[string,string,array]
	 * @throws \Lite\Exception\RouterException
	 */
	private static function resolveCurrentRequestPath($path_info){
		$tmp = $path_info ? explode('/', $path_info) : [];
		if(empty($tmp)){
			return array(self::$DEFAULT_CONTROLLER, self::$DEFAULT_ACTION, array());
		}
		$p = array();
		while($p[] = array_shift($tmp)){
			if($c = self::patchControllerFullName(join('/', $p), true)){
				$act = array_shift($tmp);
				return array(
					$c,
					$act ?: self::$DEFAULT_ACTION,
					self::resolveParamFromPath($tmp)
				);
			}
		}
		throw new RouterException('Page Not Found', null, "Path: $path_info");
	}

	/**
	 * 获取控制器类名全称
	 * @param string $ctrl_abs 控制器短名称
	 * @param bool $force_class_exists
	 * @return null|string class name
	 */
	public static function patchControllerFullName($ctrl_abs, $force_class_exists = false){
		if(!$ctrl_abs){
			return Router::getDefaultController();
		}
		$ns = Application::getNamespace();
		$controller = str_replace('/','\\',$ns.'\\controller\\'.$ctrl_abs).'Controller';
		if($force_class_exists && !class_exists($controller)){
			return null;
		}
		return $controller;
	}

	/**
	 * 合成参数
	 * @param $param
	 * @param $mode
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	private static function buildParam($param, $mode){
		$param = $param ?: [];
		switch($mode){
			case self::MODE_NORMAL:
				return http_build_query($param);
			
			case self::MODE_REWRITE:
			case self::MODE_PATH:
				$str = array();
				$ext_param = array();
				foreach($param as $k => $v){
					if(is_array($v)){
						foreach($v as $sub_k=>$_v){
							if(strlen($_v) && strpos($_v, '/') === false && strpos($_v, ' ') === false){
								$str[] = urlencode($k).'['.urlencode($sub_k).']'.'/'.rawurlencode($_v);
							} else {
								$ext_param[$k][$sub_k] = $_v;
							}
						}
					} else if(strlen($v) && strpos($v, '/') === false && strpos($v, ' ') === false){
						$str[] = urlencode($k)."/".rawurlencode($v);
					} else {
						$ext_param[$k] = $v;
					}
				}
				return join('/',$str).($ext_param ? '?'.http_build_query($ext_param) : '');
			default:
				throw new Exception('No router mode support');
		}
	}
	
	/**
	 * 获取URL链接
	 * @param string $uri
	 * @param array $params
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getUrl($uri = '', $params = array()){
		if(str_start_with($uri, array('http://', 'https://', '//'))){
			return $uri.(stripos($uri, '?') === false ? '?' : '&').http_build_query($params);
		}

		$app_url = Config::get('app/url');
		$router_mode = Config::get('router/mode');
		$lower_case_uri = Config::get('router/lower_case_uri') ?: false;
		list($controller, $action) = self::resolveUri($uri, false);
		if(!$controller){
			return '#NO_ROUTER_FOUND:'.$uri;
		}

		//首页
		if(empty($params) &&
			strcasecmp($controller, self::$DEFAULT_CONTROLLER) == 0 &&
			strcasecmp($action, self::$DEFAULT_ACTION) == 0){
			return $app_url;
		}

		$ctrl_name = self::resolveNameFromController($controller);

		$ns_ctrl_mode = strpos($ctrl_name, '/'); //controller 里面包含命名空间模式
		$url = $app_url;
		if($router_mode == self::MODE_NORMAL){
			if(!$params){
				if($action == self::$DEFAULT_ACTION && !$ns_ctrl_mode){
					$url = $app_url."?".self::$ROUTER_KEY."=".($lower_case_uri ? strtolower($ctrl_name) : $ctrl_name);
				} else {
					$url = $app_url."?".self::$ROUTER_KEY."=".($lower_case_uri ? strtolower($ctrl_name) : $ctrl_name)."/".($lower_case_uri ? strtolower($action) : $action);
				}
			} else{
				$params[self::$ROUTER_KEY] = $lower_case_uri ? strtolower("$ctrl_name/$action") : "$ctrl_name/$action";
				$url .= '?'.http_build_query($params);
			}
			return $url;
		}
		if($router_mode == self::MODE_REWRITE && $rewrite_url = Rewrite::onGetUrl("$ctrl_name/$action", $params)){
			return $rewrite_url;
		}
		//path模式，检测url中是否包含？，如果不包含，则后缀添加斜杠
		if($router_mode == self::MODE_PATH){
			$url .= stripos($url, '?') !== false ? '' : "/";
		}
		$p = "$ctrl_name";
		if($params || strcasecmp($action, self::$DEFAULT_ACTION) != 0 || $ns_ctrl_mode){
			$p .= "/$action";
		}
		if($lower_case_uri){
			$p = strtolower($p);
		}
		$str = self::buildParam($params, $router_mode);
		return $url.($str ? "$p/$str" : $p);
	}
	
	/**
	 * 产生表单action
	 * @param string $uri
	 * @param array $params
	 * @param array $fields_exclude_from_current_query 保留当前URL中的查询数据
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getUrlByFilter($uri = '', $params = array(), $fields_exclude_from_current_query = array()){
		$params = $params ?: array();
		if($fields_exclude_from_current_query){
			$tmp = static::get();
			foreach($fields_exclude_from_current_query as $field){
				unset($tmp[$field]);
			}
			$params = array_merge($tmp, $params);
		}
		return static::getUrl($uri, $params);
	}
	
	/**
	 * 静态资源url规则
	 * 规则：/ 为开始的url，直接返回应用根目录
	 * http开始的url，返回自身
	 * 其他规则返回资源目录，如无资源目录，则相对于静态资源目录
	 * @see 依赖 Config::get('app/url'), Config::get('app/css')等常量参数
	 * @param  string $file_name
	 * @param  string $type
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getStaticUrl($file_name, $type = 'static'){
		if(str_start_with($file_name, ['http://', 'https://', '//'])){
			$url = $file_name;
		} else if(strpos($file_name, '/') === 0){
			$url = Config::get('app/url') . substr($file_name, 1);
		} else{
			$map = array(
				'css'    => Config::get('app/css'),
				'js'     => Config::get('app/js'),
				'img'    => Config::get('app/img'),
				'flash'  => Config::get('app/flash'),
				'static' => Config::get('app/static')
			);
			if($map[strtolower($type)]){
				$url = $map[strtolower($type)] . $file_name;
			} else{
				$url = Config::get('app/static') . $file_name;
			}
		}
		//event
		$ref = new RefParam(array('url' => $url, 'type' => $type));
		Hooker::fire(self::EVENT_GET_STATIC_URL, $ref);
		return $ref->get('url') ?: $url;
	}
	
	/**
	 * 调用js路径
	 * @param string $file_name
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getJsUrl($file_name){
		return static::getStaticUrl($file_name, 'js');
	}
	
	/**
	 * 调用css路径
	 * @param string $file_name
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getCssUrl($file_name){
		return static::getStaticUrl($file_name, 'css');
	}
	
	/**
	 * 调用img路径
	 * @param string $file_name
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getImgUrl($file_name){
		return static::getStaticUrl($file_name, 'img');
	}
	
	/**
	 * 调用flash路径
	 * @param string $file_name
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getFlashUrl($file_name){
		return static::getStaticUrl($file_name, 'flash');
	}
	
	/**
	 * reload current page
	 */
	public static function reload(){
		header('Location:'.$_SERVER['PHP_SELF'], true, 302);
	}
	
	/**
	 * 获取当前访问url
	 * @param array $patch_param 追加参数
	 * @return string
	 */
	public static function getCurrentPageUrl($patch_param = []){
		$url = $_SERVER['REQUEST_URI'];
		if($patch_param){
			$url .= (strpos($url, '?') !== false ? '&':'?').http_build_query($patch_param);
		}
		return $url;
	}
	
	/**
	 * 获取当前action页面url
	 * @param array $param
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getCurrentActionPageUrl($param = array()){
		$uri = static::getCurrentUri();
		return static::getUrl("$uri", $param);
	}
	
	/**
	 * 页面302, 301跳转
	 * @param string $uri 控制url
	 * @param array|string $args2 query参数
	 * @param int $status_code 状态
	 */
	public static function jumpTo($uri = null, $args2 = null, $status_code = 302){
		$args = func_get_args();
		
		if(stripos($args[0], '://')>0){
			if(!empty($args2)){
				$url = $uri.(stripos($uri, '?') !== false ? '&' : '?').http_build_query($args2);
			} else{
				$url = $uri;
			}
			if(headers_sent()){
				echo '<script>location.href="'.$url.'"</script>';
			} else{
				if($status_code){
					Http::sendHttpStatus($status_code);
				}
				header('Location:'.$url);
			}
			die;
		}
		$url = call_user_func_array(array('self', 'getUrl'), $args);
		if(headers_sent()){
			echo '<script>location.href = "'.$url.'";</script>';
		} else{
			if($status_code){
				Http::sendHttpStatus($status_code);
			}
			header('Location:'.$url);
		}
		exit;
	}
}