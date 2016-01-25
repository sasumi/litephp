<?php
namespace Lite\Core;
use Lite\Core\Request;
use Lite\Exception\RouterException;
use function Lite\func\array_clear_empty;
use function Lite\func\array_clear_null;
use function Lite\func\dump;

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
	const MODE_NORMAL = 'normal';
	const MODE_PATHINFO = 'pathinfo';
	const MODE_REWRITE = 'rewrite';

	public static $ROUTER_KEY;
	public static $ROUTER_MODE;
	public static $DEFAULT_CONTROLLER = '';
	public static $DEFAULT_ACTION = '';

	private static $CONTROLLER = '';
	private static $ACTION = '';
	private static $listen_flag = false; //listen stopped by last handler

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
	public static function readInputDataChunk($handler, $chunk_size=1024){
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
		if(self::$listen_flag){
			return false;
		}
		$path = $path ?: '/';
		$current_path = self::getPathInfo();
		if(preg_match($path, '/\{(\w+)\}/', $matches)){
			$path_reg = str_replace('/(\{\w+\})/', '(\w+)', $path);
		} else {
			$path_reg = $path;
		}
		$path_reg = str_replace('\/', '\\/', $path_reg);
		if(preg_match($current_path, $path_reg, $matches_vars)){
			array_shift($matches_vars);
			$ret = call_user_func_array($handler, $matches_vars);
			if($ret == false){
				self::$listen_flag = true;
			}
			return true;
		}
		return false;
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
	 * 获取当前路由模式
	 * @return string
	 */
	public static function getMode(){
		return self::$ROUTER_MODE;
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
	 * 获取$_GET变量，如果使用了pathinfo模式，框架会自动将pathinfo内变量转换到$_GET变量中
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

		//检查当前访问是否命中路由规则，
		//命中了就不再使用通用规则进行解析
		//优先使用pathinfo里面的数据(PATHINFO|REWRITE模式需要)
		$path_info = self::getPathInfo();
		$path_list = explode('/', $path_info) ?: array();

		list($controller, $action) = $path_list;
		$param = array_slice($path_list, 2);
		$tmp_param = null;
		for($i = 0; $i <= count($param); $i += 2){
			if($param[$i] !== null){
				$tmp_param[] = "{$param[$i]}={$param[$i + 1]}";
			}
		}
		if($tmp_param){
			parse_str(join('&', $tmp_param), $tmp_param);
			$get = array_merge($get, $tmp_param);
		}

		//在PATHINFO和REWRITE模式下解析r
		if(!$controller && $get[self::$ROUTER_KEY]){
			list($controller, $action) = explode('/', $get[self::$ROUTER_KEY]) ?: array();
			unset($get[self::$ROUTER_KEY]);
		}

		$controller = $controller ?: self::$DEFAULT_CONTROLLER;
		$action = $action ?: self::$DEFAULT_ACTION;

		//安全保护
		if(!preg_match('/^\w+$/', $controller) || !preg_match('/^\w+$/', $action)){
			throw new RouterException($controller ?: $action, 23, array('parameter illegal'));
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
			'action' => $action,
			'get' => $get
		);
	}

	/**
	 * 解析请求参数到当前环境的Controller、Action、GET、POST
	 */
	public static function init(){
		Hooker::fire(self::EVENT_BEFORE_ROUTER_INIT);
		self::$ROUTER_MODE = Config::get('router/mode');
		self::$ROUTER_KEY = Config::get('router/router_key');
		self::$DEFAULT_CONTROLLER = Config::get('router/default_controller');
		self::$DEFAULT_ACTION = Config::get('router/default_action');

		$ret = self::parseUrlByRules(self::getCurrentPageUrl());
		if($ret){
			Hooker::fire(self::EVENT_ROUTER_RULE_MATCH, $ret['rule'], $ret['controller'], $ret['action'], $ret['get']);
		} else {
			$ret = self::parseCurrentRequest();
		}

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
		}else{
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
	 * @deprecate 框架新版本已经支持路由混合解析，
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
	 * 已pathinfo模式产生url，主要提供给form[method=get]表单使用，避免参数丢失
	 * @param string $target
	 * @param array $params
	 * @return string
	 */
	public static function getUrlInPathMode($target = '', $params = array()){
		return self::getUrl($target, $params, self::MODE_PATHINFO);
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
	 * 路由
	 * @param string $target
	 * @param array $params
	 * @param null $router_mode
	 * @return string
	 */
	public static function getUrl($target = '', $params = array(), $router_mode = null){
		if(stripos($target, 'http://') === 0 || stripos($target, 'https://') === 0){
			return $target.(stripos($target, '?') === false ? '?':'&').http_build_query($params);
		}

		$app_url = Config::get('app/url');
		list($controller, $action) = explode('/', trim($target, '/'));
		$controller = $controller ?: self::$DEFAULT_CONTROLLER;
		$action = $action ?: self::$DEFAULT_ACTION;

		//首页
		if(empty($params) &&
			strtolower($controller) == strtolower(self::$DEFAULT_CONTROLLER)
			&& strtolower($action) == strtolower(self::$DEFAULT_ACTION)){
			return $app_url;
		}

		$url = self::makeUrlByRules($controller, $action, $params);
		if($url){
			return self::patchHostPreset($url);
		}

		$query_string = http_build_query($params);
		$router_mode = $router_mode ?: self::$ROUTER_MODE;

		switch($router_mode){
			//PATHINFO MODE
			case self::MODE_PATHINFO:
				$url = rtrim($app_url, '/').'/index.php';
				if(!$query_string){
					if(strtolower($action) == strtolower(self::$DEFAULT_ACTION)){
						$url .= '/'.$controller;
					}else{
						$url .= '/'.$controller.'/'.$action;
					}
				}else{
					$url .= '/'.$controller.'/'.$action;
					$url .= '/'.str_replace(array('&', '='), '/', $query_string);
				}
				break;

			//URL REWRITE MODE
			case self::MODE_REWRITE:
				$url = rtrim($app_url, '/');
				if(!$query_string){
					if(strtolower($action) == strtolower(self::$DEFAULT_ACTION)){
						$url .= '/'.$controller;
					}else{
						$url .= '/'.$controller.'/'.$action;
					}
				}else{
					$url .= '/'.$controller.'/'.$action.'/'.str_replace(array('&', '='), '/', $query_string);;
				}
				break;

			//NORMAL MODE
			case self::MODE_NORMAL:
			default:
				$url = rtrim($app_url, '/').'/index.php';
				if(!$query_string){
					if(strtolower($action) == strtolower(self::$DEFAULT_ACTION)){
						$url = $app_url.'index.php?'.self::$ROUTER_KEY.'='.$controller;
					}else{
						$url = $app_url.'index.php?'.self::$ROUTER_KEY.'='.$controller.'%2F'.$action;
					}
				}else{
					$params[self::$ROUTER_KEY] = $controller.'/'.$action;
					$url .= '?'.http_build_query($params);
				}
				break;
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
		}else if(strpos($file_name, 'http://') === 0){
			$url = $file_name;
		}else{
			$map = array(
				'css' => Config::get('app/css'),
				'js' => Config::get('app/js'),
				'img' => Config::get('app/img'),
				'flash' => Config::get('app/flash'),
				'static' => Config::get('app/static')
			);
			if($map[strtolower($type)]){
				$url = $map[strtolower($type)].$file_name;
			} else {
				$url = Config::get('app/static').$file_name;
			}
		}

		//event
		$ref = new RefParam(array('url' => $url, 'type'=>$type));
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
	 * 页面302, 301跳转
	 * @deprecate 调用了url函数功能，所以参数跟url函数的参数一致
	 * @param null $uri
	 * @param null $args2
	 * @param int $status_code
	 */
	public static function jumpTo($uri = null, $args2 = null, $status_code=302){
		$args = func_get_args();

		if(stripos($args[0], '://') > 0){
			if(!empty($args2)){
				$url = $uri.(stripos($uri, '?') !== false ? '&' : '?').http_build_query($args2);
			}else{
				$url = $uri;
			}
			if(headers_sent()){
				echo '<script>location.href="'.$url.'"</script>';
			}else{
				if($status_code){
					Request::sendHttpStatus($status_code);
				}
				header('Location:'.$url);
			}
			die;
		}
		$url = call_user_func_array(array('self', 'getUrl'), $args);
		if(headers_sent()){
			echo '<script>location.href = "'.$url.'";</script>';
		}else{
			if($status_code){
				Request::sendHttpStatus($status_code);
			}
			header('Location:'.$url);
		}
		exit;
	}

	/**
	 * 解析路由
	 * 路由规则格式为 array(
	 *      array($target_url, $source_url),
	 *      array($target_url, $source_url),
	 * )
	 * $target_url:  /blog-{cid}-{id}.html
	 * $source_url: ctrl=blog/act=detail/cid={cid}/id={id}
	 * @param $url
	 * @return bool
	 */
	public static function parseUrlByRules($url){
		$rules = Config::get('router/rules');
		if(empty($rules)){
			return false;
		}

		//按照目标匹配的url长度进行优先排序
		usort($rules, function($a, $b){
			return strlen($a[0]) < strlen($b[1]);
		});

		$host = trim(Config::get('app/url'), '/');
		$url = trim($url, '/');
		$url = str_replace($host, '', $url);
		$url = '/'.trim($url, '/');

		foreach($rules as $rule_key=>$rule){
			list($target_url, $source_url) = $rule;

			//匹配Key
			preg_match_all('/\{([^\}]+)\}/', $target_url, $match_keys);
			$match_keys = $match_keys[1] ?: array();

			//正则语法保护
			$tmp = str_replace(array('{', '}'), array('%', '@'), $target_url);
			$tmp = preg_quote($tmp, '/');
			$pattern = preg_replace('/\%[^@]+@/', '(\w*?)', $tmp);
			$pattern = "/^{$pattern}$/";

			if(preg_match_all($pattern, $url, $matches)){
				$ctrl = $act = $get = $_ = null;

				//这里限定了参数不允许出现数组
				if(!empty($matches[1])){
					foreach($match_keys as $k=>$key){
						$get[$key] = $matches[$k+1][0];
					}
				}
				self::parseSourceRule($source_url, $ctrl, $act, $get);

				//继续做容错判断, 避免业务配置引发不可用情况
				if($ctrl && $act){
					return array(
						'rule' => $rule,
						'controller' => $ctrl,
						'action' => $act,
						'get' => $get
					);
				}
			}
		}
		return false;
	}

	/**
	 * 解析后台源路由配置规则
	 * 这里会融合param参数,将配置里面的固定的key-value转换到param里面.
	 * @param $source_rule
	 * @param $controller
	 * @param $action
	 * @param array $req
	 * @throws \Lite\Exception\RouterException
	 */
	private function parseSourceRule($source_rule, &$controller, &$action, &$req=array()){
		$tmp = explode('/', $source_rule);
		$tmp = array_clear_empty($tmp);

		$req = $req ?: array();
		foreach($tmp as $item){
			list($k, $v) = explode('=', $item);
			if($k == 'ctrl'){
				$controller = $v;
			} else if($k == 'act'){
				$action = $v;
			}
			//非变量类型, 配置优先于入参
			else if(stripos($v, '{') === false){
				$req[$k] = $v;
			} else if(!isset($req[$k])){
				$req[$k] = $v;
			}
		}
		if(empty($controller) || empty($action)){
			throw new RouterException('Router rules need specify controller & action name');
		}
	}

	/**
	 * 根据提交controller,action,params 组装seo后的url
	 * @param string $controller
	 * @param string $action
	 * @param array $req_param
	 * @return string
	 */
	private static function makeUrlByRules($controller, $action, array $req_param=array()){
		$rules = Config::get('router/rules');
		if(empty($rules)){
			return '';
		}

		//去掉为null值的参数
		foreach($rules as $rule){
			list($target_url, $source_url) = $rule;
			$ctrl = $act = $rule_param = null;
			self::parseSourceRule($source_url, $ctrl, $act, $rule_param);
			if(strcasecmp($ctrl, $controller) === 0 &&
				strcasecmp($act, $action) === 0){
				if(self::cmpSeoParam($req_param, $rule_param)){
					return self::buildTargetUrl($req_param, $target_url);
				} else {
					//暂未考虑不能完全命中的逻辑
				}
			}
		}
		return '';
	}

	/**
	 * 匹对请求参数与规则参数是否吻合可用
	 * @param array $req_param
	 * @param array $rule_param
	 * @return bool
	 */
	private static function cmpSeoParam($req_param, $rule_param){
		$rule_param = array_clear_null($rule_param);
		$rule_keys = array_keys($rule_param);
		sort($rule_keys);

		$req_param = array_clear_null($req_param);
		$req_keys = array_keys($req_param);
		sort($req_keys);

		//如果是固定值, 需要检查严格匹配
		if($rule_keys == $req_keys){
			foreach($rule_keys as $k){
				if(stripos($rule_param[$k], '{') === false &&
					$req_param[$k] != $rule_param[$k]){
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * 转换字符串中的{}变量为实际的变量
	 * @param $params
	 * @param $target_url
	 * @return mixed
	 */
	private static function buildTargetUrl($params=array(), $target_url){
		$str = $target_url;
		foreach($params as $k=>$v){
			$str = str_replace('{'.$k.'}', $v, $str);
		}
		return $str;
	}

	public static function parseUrl(){

	}
}