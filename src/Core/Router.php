<?php
namespace Lite\Core;

use Lite\Component\Http;
use Lite\Component\Server;
use Lite\Exception\Exception;
use Lite\Exception\RouterException;
use function Lite\func\array_clear_empty;
use function Lite\func\array_clear_null;
use function Lite\func\decodeURIComponent;
use function Lite\func\dump;
use function Lite\func\encodeURIComponent;
use function Lite\func\glob_recursive;

/**
 * 路由基础类。当前路由路由基础类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class Router{
	const EVENT_BEFORE_ROUTER_INIT = 'EVENT_BEFORE_ROUTER_INIT';
	const EVENT_AFTER_ROUTER_INIT = 'EVENT_AFTER_ROUTER_INIT';
	const EVENT_ROUTER_RULE_MATCH = 'EVENT_ROUTER_RULE_MATCH';
	const EVENT_GET_STATIC_URL = 'EVENT_ROUTER_GET_STATIC_URL';
	
	const DEFAULT_ROUTER_KEY = 'r';

	const MODE_NORMAL = 0x01;
	const MODE_PATH = 0x02;
	const MODE_REWRITE = 0x03;

	public static $ROUTER_KEY;
	public static $DEFAULT_PATH = '/';
	public static $DEFAULT_CONTROLLER = '';
	public static $DEFAULT_ACTION = '';
	
	private static $CONTROLLER = '';
	private static $ACTION = '';

	public static $GET = array();
	public static $POST = array();
	public static $PUT = array();
	public static $DELETE = array();
	
	/**
	 * read php input data
	 * @return string
	 */
	public static function readInputData(){
		$data = file_get_contents('php://input');
		return $data;
	}
	
	/**
	 * read php input by chunk
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
	 * 获取当前调用action
	 * @return string
	 */
	public static function getAction(){
		return self::$ACTION;
	}
	
	/**
	 * 获取$_GET变量
	 * @param null $key
	 * @return array
	 */
	public static function get($key = null){
		return !$key ? self::$GET : self::$GET[$key];
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
		if($key){
			return isset(self::$POST[$key]) ? self::$POST[$key] : self::$GET[$key];
		}
		return array_merge(self::$GET, self::$POST);
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
	 */
	private static function parseCurrentRequest(){
   		$get = $_GET;
		$router_mode = Config::get('router/mode');

		//优先query参数
		if($get[self::$ROUTER_KEY] || $router_mode == self::MODE_NORMAL){
			list($_, $controller, $action) = self::resolveUri($get[self::$ROUTER_KEY]);
			unset($get[self::$ROUTER_KEY]);
		} else {
			$path_info = self::getPathInfo();
			list($_, $controller, $action, $param) = self::resolvePath($path_info);
			$get = array_merge($get, $param);
		}

		$controller = $controller ?: self::$DEFAULT_CONTROLLER;
		$action = $action ?: self::$DEFAULT_ACTION;

		//安全保护
		if(!preg_match('/^[\w|\\\]+$/', $controller) || !preg_match('/^\w+$/', $action)){
			throw new RouterException('PARAMETER ILLEGAL', array('controller'=>$controller, 'action' => $action));
		}
		
		//自动decode
		if(!empty($get)){
			array_walk_recursive($get, function(&$item){
				if(is_string($item)){
					$item = urldecode($item);
				}
			});
		}
		
		return array(
			'controller' => $controller,
			'action'     => $action,
			'get'        => $get
		);
	}
	
	/**
	 * 解析请求参数到当前环境的Controller、Action、GET、POST
	 */
	public static function init(){
		Hooker::fire(self::EVENT_BEFORE_ROUTER_INIT);
		self::$ROUTER_KEY = Config::get('router/router_key');
		self::$DEFAULT_PATH = Config::get('router/default_path');
		self::$DEFAULT_CONTROLLER = Application::getNamespace().'\\controller\\'.Config::get('router/default_controller').'Controller';
		self::$DEFAULT_ACTION = Config::get('router/default_action');

		$ret = self::parseCurrentRequest();

		self::$CONTROLLER = $ret['controller'];
		self::$ACTION = $ret['action'];
		self::$GET = $ret['get'];
		self::$POST = $_POST;

		$_GET = $ret['get'];
		Hooker::fire(self::EVENT_AFTER_ROUTER_INIT, self::$CONTROLLER, self::$ACTION, self::$GET, self::$POST);
	}
	
	/**
	 * 获取path信息
	 * @return string
	 **/
	private static function getPathInfo(){
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
	 * @deprecated 框架新版本已经支持路由混合解析，
	 * 因此推荐使用 Router::getUrlInPathMode来产生表单使用的action
	 * @param string $target
	 * @param array $params
	 * @return string
	 */
	public static function getFormAction($target = '', $params = array()){
		$html = '<input type="hidden" name="'.self::$ROUTER_KEY.'" value="'.$target.'"/>';
		foreach($params as $name => $p){
			$html .= '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars($p).'"/>';
		}
		return $html;
	}
	
	/**
	 * 追加host到url里面,主要为了seo优化
	 * @param $url
	 * @return string
	 */
	private static function patchHostPreset($url){
		$app_url = Config::get('app/url');
		if(stripos($app_url, 'http://') === false){
			return $url;
		}
		if(strpos($url, '/') === 0){
			$tmp = parse_url($app_url);
			return $tmp['scheme'].'://'.$tmp['host'].$url;
		}
		return $url;
	}

	/**
	 * 解析URI
	 * @param string $uri
	 * @return array
	 */
	private static function resolveUri($uri=''){
		$path = self::$DEFAULT_PATH;
		$c = '';
		$action = self::$DEFAULT_ACTION;
		$uri = trim($uri, '/ ');
		if($uri){
			$tmp = explode('/', $uri);
			switch(count($tmp)){
				case 1:
					list($c) = $tmp;
					break;

				case 2:
					list($c, $action) = $tmp;
					break;

				default:
					$action = array_shift($tmp);
					$c = array_shift($tmp);
					$path = join('/', $tmp);
					break;
			}
		}
		if($c){
			$controller = Application::getNamespace()."\\controller\\{$c}Controller";
		} else {
			$controller = self::$DEFAULT_CONTROLLER;
		}
		return array($path, $controller, $action);
	}

	private static function isDirCase($path, $fold){
		$path = str_replace('\\', '/', $path);
		if(Server::inWindows() && false){
			//
		} else {
			$dir_list = glob_recursive($path.'*', GLOB_ONLYDIR);
			foreach($dir_list as $dir){
				$dir = str_replace('\\', '/', $dir);
				if(strcasecmp($dir, $path.$fold) == 0){
					return $dir;
				}
			}
		}
		return false;
	}

	/**
	 * 从controller类名中解析对应名称（包含子级目录）
	 * @param $controller
	 * @return string
	 */
	public static function resolveNameFromController($controller){
		$ns = Application::getNamespace();
		$preg = "/^".preg_quote($ns)."\\\\controller\\\\(.*?)Controller/";
		$ctrl = preg_replace($preg, '$1', $controller);
		return $ctrl;
	}

	/**
	 * resolve param from path info
	 * @param $path_arr
	 * @return array
	 */
	private static function resolveParamFromPath($path_arr){
		$param = array();
		for($i=0; $i<count($path_arr); $i+=2){
			$param[$path_arr[$i]] = decodeURIComponent($path_arr[$i+1]);
		}
		return $param;
	}

	/**
	 * resolve pathinfo to path,controller,action,param
	 * @param $path_info
	 * @return array
	 * @throws \Lite\Exception\RouterException
	 */
	private static function resolvePath($path_info){
		$tmp = array_clear_empty(explode('/', $path_info));
		$controller_path = Config::get('app/path')."controller/";
		$p = $controller_path;
		$ns_prefix = Application::getNamespace().'\controller\\';

		while(is_dir($p) && count($tmp)){
			if(self::isDirCase($p, $tmp[0])){
				$ns_prefix = $ns_prefix.strtolower($tmp[0]).'\\';
				$p = $p.array_shift($tmp).'/';
				continue;
			}
			$c = "$ns_prefix{$tmp[0]}Controller";
			if(class_exists($c)){
				array_shift($tmp);
				$path = $p;
				$controller = $c;
				$action = array_shift($tmp);
				$param = self::resolveParamFromPath($tmp);
				return array($path, $controller, $action, $param);
				break;
			}
		}
		return array(null, null, null, self::resolveParamFromPath($tmp));
	}

	/**
	 * 合成参数
	 * @param $param
	 * @param $mode
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	private static function buildParam($param, $mode){
		switch($mode){
			case self::MODE_NORMAL:
				return http_build_query($param);

			case self::MODE_REWRITE:
			case self::MODE_PATH:
				$str = '';
				foreach($param as $k => $v){
					$str .= "$k=".encodeURIComponent($v);
				}
				return $str;
			default:
				throw new Exception('no router mode support');
		}
	}
	
	/**
	 * 路由
	 * @param string $uri
	 * @param array $params
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function getUrl($uri = '', $params = array()){
		if(stripos($uri, 'http://') === 0 || stripos($uri, 'https://') === 0){
			return $uri.(stripos($uri, '?') === false ? '?' : '&').http_build_query($params);
		}
		
		$app_url = Config::get('app/url');
		$router_mode = Config::get('router/mode');
		list($path, $controller, $action) = self::resolveUri($uri);

		//首页
		if(empty($params) &&
			strcasecmp($path, self::$DEFAULT_PATH) == 0 &&
			strcasecmp($controller, self::$DEFAULT_CONTROLLER) == 0 &&
			strcasecmp($action, self::$DEFAULT_ACTION) == 0){
			return $app_url;
		}

		$ctrl = self::resolveNameFromController($controller);
		$url = $app_url;
		if($router_mode == self::MODE_NORMAL){
			$query_string = http_build_query($params);
			if(!$query_string){
				if($path == '/'){
					if($action == self::$DEFAULT_ACTION){
						$url = $app_url.'index.php?'.self::$ROUTER_KEY.'='.$ctrl;
					} else {
						$url = $app_url.'index.php?'.self::$ROUTER_KEY.'='.$ctrl.'%2F'.$action;
					}
				} else {
					$url = $app_url.'index.php?'.self::$ROUTER_KEY.'='.$path.'%2F'.$ctrl.'%2F'.$action;
				}
			} else{
				$params[self::$ROUTER_KEY] = $ctrl.'/'.$action;
				$url .= '?'.http_build_query($params);
			}
		} else if($router_mode == self::MODE_REWRITE || $router_mode == self::MODE_PATH){
			if($router_mode == self::MODE_PATH){
				$url .= stripos($url, '.php') !== false ? '' : 'index.php/';
			}
			if($path == '/'){
				if($action == self::$DEFAULT_ACTION){
					$p = $ctrl;
				} else {
					$p = "$ctrl/$action";
				}
			} else {
				$p = "$path/$ctrl/$action";
			}
			$str = self::buildParam($params, $router_mode);
			$url = $url.($str ? "$p/$str" : $p);
		} else {
			throw new Exception('no router mode found');
		}
		return self::patchHostPreset($url);
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
	 */
	public static function getStaticUrl($file_name, $type = 'static'){
		if(strpos($file_name, '/') === 0){
			$url = Config::get('app/url').substr($file_name, 1);
		} else if(strpos($file_name, 'http://') === 0){
			$url = $file_name;
		} else{
			$map = array(
				'css'    => Config::get('app/css'),
				'js'     => Config::get('app/js'),
				'img'    => Config::get('app/img'),
				'flash'  => Config::get('app/flash'),
				'static' => Config::get('app/static')
			);
			if($map[strtolower($type)]){
				$url = $map[strtolower($type)].$file_name;
			} else{
				$url = Config::get('app/static').$file_name;
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
	 **/
	public static function getJsUrl($file_name){
		return self::getStaticUrl($file_name, 'js');
	}
	
	/**
	 * 调用css路径
	 * @param string $file_name
	 * @return string
	 **/
	public static function getCssUrl($file_name){
		return self::getStaticUrl($file_name, 'css');
	}
	
	/**
	 * 调用img路径
	 * @param string $file_name
	 * @return string
	 **/
	public static function getImgUrl($file_name){
		return self::getStaticUrl($file_name, 'img');
	}
	
	/**
	 * 调用flash路径
	 * @param string $file_name
	 * @return string
	 **/
	public static function getFlashUrl($file_name){
		return self::getStaticUrl($file_name, 'flash');
	}
	
	/**
	 * reload current page
	 */
	public static function reload(){
		header('Location:'.$_SERVER['PHP_SELF'], true, 302);
	}
	
	/**
	 * 获取当前访问url
	 * @return string
	 **/
	public static function getCurrentPageUrl(){
		$host = $_SERVER['HTTP_HOST'];
		$protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') ? 'https://' : 'http://';
		$port = $_SERVER['SERVER_PORT'] == 80 ? null : $_SERVER['SERVER_PORT'];
		$uri = $_SERVER['REQUEST_URI'];
		return $protocol.$host.($port ? ':'.$port : '').$uri;
	}
	
	/**
	 * 获取当前action页面url
	 * @param array $param
	 * @return string
	 */
	public static function getCurrentActionPageUrl($param = array()){
		$ctrl = static::getController();
		$act = static::getAction();
		return static::getUrl("$ctrl/$act", $param);
	}
	
	/**
	 * 页面302, 301跳转
	 * @param string $uri 控制url
	 * @param array|string $args2 query参数
	 * @param int $status_code
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