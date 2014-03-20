<?php
abstract class Router {
	const MODE_NORMAL = 'normal';
	const MODE_PATH = 'path';
	const MODE_REWRITE = 'rewrite';

	private static $ROUTER_MODE;
	private static $CONTROLLER_KEY = '';
	private static $ACTION_KEY = '';
	private static $DEFAULT_CONTROLLER = '';
	private static $DEFAULT_ACTION = '';

	private static $CONTROLLER = '';
	private static $ACTION = '';
	private static $GET = array();
	private static $POST = null;

	final public static function getDefaultController(){
		return self::$DEFAULT_CONTROLLER;
	}

	final public static function getDefaultAction(){
		return self::$DEFAULT_ACTION;
	}

	final public static function getController(){
		return self::$CONTROLLER;
	}

	final public static function getAction(){
		return self::$ACTION;
	}

	final public static function get($key=null){
		return !$key ? self::$GET : self::$GET[$key];
	}

	final public static function post($key=null){
		return !$key ? self::$POST : self::$POST[$key];
	}

	final public static function parseRequest(&$controller, &$action, &$get=array(), &$post=null){
		switch(self::$ROUTER_MODE){
			case self::MODE_REWRITE:
			case self::MODE_PATH:
				$path_info = self::getPathInfo();

				$ps = explode('/', $path_info);
				list($controller, $action) = $ps;
				$tmp = count($ps) > 2 ? array_slice($ps, 2) : array();

				for($i=0; $i<=count($tmp); $i+=2){
					if($tmp[$i] !== NULL){
						$get[$tmp[$i]] = $tmp[$i+1];
					}
				}
				break;

			case self::MODE_NORMAL:
			default:
				$controller = $_GET[self::$CONTROLLER_KEY];
				unset($_GET[self::$CONTROLLER_KEY]);
				$action = $_GET[self::$ACTION_KEY];
				unset($_GET[self::$ACTION]);
				$get = $_GET;
		}
		$post = $_POST;
		$controller = $controller ?: self::$DEFAULT_CONTROLLER;
		$action = $action ?: self::$DEFAULT_ACTION;

		//hack $_GET
		if(Config::get('route/mode') != Router::MODE_NORMAL){
			$_GET = $gets;
		}
	}

	/**
	 * 解析GET请求
	 * @param string $controller
	 * @param string $action
	 * @param string $request
	 **/
	final public static function init(){
		self::$ROUTER_MODE = Config::get('router/mode');
		self::$DEFAULT_CONTROLLER = Config::get('router/default_controller');
		self::$DEFAULT_ACTION = Config::get('router/default_action');
		self::$CONTROLLER_KEY = Config::get('router/controller_key');
		self::$ACTION_KEY = Config::get('router/action_key');

		self::parseRequest(self::$CONTROLLER, self::$ACTION, self::$GET, self::$POST);
	}

	/**
	 * 监听路由
	 * @param string $action
	 * @param string $call
	 */
	final public static function listen($action='*', $callback=null){
		$param_arr = array(self::getAction(), self::getController());
		if($action == '*' || !$action || $action == self::getAction()){
			call_user_func_array($callback, $param_arr);
		}
	}

	/**
	 * 获取path信息
	 * @return string
	 **/
	final public static function getPathInfo(){
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
	 * 从$_GET中获取参数
	 * @param string $key
	 * @param string||array $rules
	 * @param boolean $throwException
	 * @return mix
	 **/
	final public static function gets($key=null, $rules, $throwException=true){
		$data = $key ? self::$GET[$key] : self::$GET;

		if($key){
			Filter::filteOne($data, $rules, $throwException);
		} else {
			Filter::filteArray($data, $rules, $throwException);
		}
		return $data;
	}

	/**
	 * 从$_POST中获取参数
	 * @param string $key
	 * @param string||array $rules
	 * @param boolean $throwException
	 * @return mix
	 **/
	final public static function posts($key=null, $rules, $throwException=true){
		$data = $key ? $_POST[$key] : $_POST;
		if($key){
			Filter::filteOne($data, $rules, $throwException);
		} else {
			Filter::filteArray($data, $rules, $throwException);
		}
		return $data;
	}

	/**
	 * 检测当前请求是否为POST
	 * @return boolean
	 **/
	final public static function isPost(){
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	/**
	 * 检测当前请求是否为GET
	 * @return boolean
	 **/
	final public static function isGet(){
		return $_SERVER['REQUEST_METHOD'] == 'GET';
	}

	/**
	 * 路由
	 * @param string $target
	 * @param array $params
	 * @return string
	 **/
	final public static function getUrl($target='', $params=array()){
		list($controller, $action) = explode('/', $target);
		$controller = $controller ?: self::$DEFAULT_CONTROLLER;
		$action = $action ?: self::$DEFAULT_ACTION;
		$url = '';

		if(empty($params) && $controller == self::$DEFAULT_CONTROLLER && $action == self::$DEFAULT_ACTION){
			return Config::get('app/url');
		}

		switch(self::$ROUTER_MODE){
			case self::MODE_PATH:
				$url = Config::get('app/url')."index.php/$controller/$action";
				foreach($params as $k=>$p){
					$url .= "/".urlencode($k)."/".urlencode($p);
				}
				break;

			case self::MODE_REWRITE:
				$url = Config::get('app/url').$controller.(empty($params) && $action == self::$DEFAULT_ACTION ? '' : '/'.$action);
				foreach($params as $k=>$p){
					$url .= "/".urlencode($k)."/".urlencode($p);
				}
				break;

			case self::MODE_NORMAL:
			default:
				$url = Config::get('app/url').'index.php?'.self::$CONTROLLER_KEY.'='.$controller.'&'.self::$ACTION_KEY.'='.$action;
				if($params){
					$url .= http_build_query($params);
				}
		}
		return $url;
	}

	/**
	 * 静态资源url规则
	 * 规则：/ 为开始的url，直接返回应用根目录
	 * http开始的url，返回自身
	 * 其他规则返回资源目录，如无资源目录，则相对于静态资源目录
	 * @deprecate 依赖 Config::get('app/url'), Config::get('app/css')等常量参数
	 * @param  string $file_name
	 * @param  string $type
	 * @return string
	 */
	final public static function getStaticUrl($file_name, $type){
		if(strpos($file_name, '/') === 0){
			return Config::get('app/url').substr($file_name, 1);
		} else if(strpos($file_name, 'http://') === 0){
			return $file_name;
		} else {
			$map = array(
					'css' => Config::get('app/css'),
					'js' => Config::get('app/js'),
					'img' => Config::get('app/img'),
					'flash' => Config::get('app/flash')
			);
			if($map[strtolower($type)]){
				return $map[strtolower($type)].$file_name;
			}
			return Config::get('app/static').$file_name;
		}
	}

	/**
	 * 调用js路径
	 * @param string $file_name
	 * @return string
	 **/
	final public static function getJsUrl($file_name){
		return self::getStaticUrl($file_name, 'js');
	}

	/**
	 * 调用css路径
	 * @param string $file_name
	 * @return string
	 **/
	final public static function getCssUrl($file_name){
		return self::getStaticUrl($file_name, 'css');
	}

	/**
	 * 调用img路径
	 * @param string $file_name
	 * @return string
	 **/
	final public static function getImgUrl($file_name){
		return self::getStaticUrl($file_name, 'img');
	}

	/**
	 * 调用flash路径
	 * @param string $file_name
	 * @return string
	 **/
	final public static function getFlashUrl($file_name){
		return self::getStaticUrl($file_name, 'flash');
	}

	/**
	 * reload current page
	 */
	final public static function reload(){
		return header('Location:'.$_SERVER['PHP_SELF'], true, 302);
	}

	/**
	 * 获取当前访问url
	 * @return string
	 **/
	final public static function getCurrentPageUrl(){
		$host = $_SERVER['HTTP_HOST'];
		$protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') ? 'https://' : 'http://';
		$port = $_SERVER['SERVER_PORT'] == 80 ? null : $_SERVER['SERVER_PORT'];
		$uri = $_SERVER['REQUEST_URI'];
		return $protocol.$host.($port ? ':'.$port : '').$uri;
	}

	/**
	 * 页面302跳转
	 * @deprecate 调用了url函数功能，所以参数跟url函数的参数一致
	 **/
	final public static function jumpTo(){
		$args = func_get_args();

		//ignore normal url parameter
		if(stripos($args[0], '://') > 0){
			header('Location:'.$args[0]);
		}

		$url = call_user_func_array(array(self, 'getUrl'), $args);
		header('Location:'.$url);
		die;
	}
}
