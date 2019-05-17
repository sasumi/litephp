<?php
namespace Lite\Core;

use Lite\Component\Net\Http;
use Lite\Exception\BizException;
use Lite\Exception\Exception as Exception;
use Lite\Exception\RouterException;
use Lite\Logger\Logger;
use Lite\Logger\LoggerLevel;
use Lite\Logger\Message\CommonMessage;
use ReflectionClass;
use function Lite\func\decodeURI;
use function Lite\func\file_exists_case_insensitive;
use function Lite\func\file_exists_case_sensitive;
use function Lite\func\microtime_diff;

/**
 * Lite框架应用初始化处理类
 */
class Application{
	const MODE_WEB = 0x01; //普通HTTP web模式
	const MODE_CLI = 0x03; //CLI命令模式（该模式提供代码加载逻辑等，不进行初始化Controller）

	const EVENT_BEFORE_APP_INIT = __CLASS__ . 'EVENT_BEFORE_APP_INIT';
	const EVENT_AFTER_APP_INIT = __CLASS__ . 'EVENT_AFTER_APP_INIT';
	const EVENT_AFTER_APP_SHUTDOWN = __CLASS__ . 'EVENT_AFTER_APP_SHUTDOWN';
	const EVENT_ON_APP_EX = __CLASS__ . 'EVENT_ON_APP_EX';
	const EVENT_ON_APP_ERR = __CLASS__ . 'EVENT_ON_APP_ERR';
	
	public static $init_microtime;

	//current controller(only in web mode)
	private static $controller;

	//project config include paths
	private static $include_paths = array();

	//project namespace
	private static $namespace;

	/**
	 * 框架初始化方法
	 * @param $namespace
	 * @param null $app_root
	 * @param $mode
	 * @throws \Exception
	 * @internal param null $app_root
	 */
	private function __construct($namespace, $app_root = null, $mode){
		self::$namespace = $namespace;

		//注册项目文件自动加载逻辑
		spl_autoload_register(array($this, 'autoload'));

		Hooker::fire(self::EVENT_BEFORE_APP_INIT);

		//配置初始化
		Config::init($app_root);

		//绑定项目根目录
		if($app_path = Config::get('app/path')){
			self::addIncludePath($app_path.'controller/', self::$namespace.'\\controller', false);
			self::addIncludePath($app_path, self::$namespace);
		}

		//绑定项目include目录
		if($include_path = Config::get('app/path').'include/'){
			self::addIncludePath($include_path, self::$namespace);
		}

		//启用相应的应用模式
		switch($mode){
			case self::MODE_WEB:
				$this->initWebMode();
				break;

			case self::MODE_CLI:
				$this->initCLIMode();
				break;

			default:
				throw new Exception('NO SPEC MODE');
		}
		
		if(Hooker::exists(self::EVENT_AFTER_APP_SHUTDOWN)){
			Hooker::fire(self::EVENT_AFTER_APP_SHUTDOWN, microtime_diff(self::$init_microtime));
		}
	}
	
	/**
	 * 初始化框架逻辑
	 * @param null $app_root 项目物理路径
	 * @param string $namespace app namespace
	 * @param int $mode app模式（web模式、API模式、cli模式, SRC源码模式）
	 * @return Application
	 * @throws \Exception
	 */
	public static function init($namespace=null, $app_root = null, $mode=self::MODE_WEB){
		static $instance;

		if(!$instance){
			self::$init_microtime = microtime();
			
			//BIND APP ERROR
			set_error_handler(function ($code, $message, $file, $line, $context){
				Hooker::fire(Application::EVENT_ON_APP_ERR, $code, $message, $file, $line, $context);
			}, E_USER_ERROR | E_USER_WARNING);

			Hooker::fire(self::EVENT_AFTER_APP_INIT);

			//init app
			$instance = new self($namespace, $app_root, $mode);
		}
		return $instance;
	}

	/**
	 * handle application exception
	 * @param \Exception $ex
	 * @throws \Exception
	 */
	private static function handleWebException(\Exception $ex){
		$render = Config::get('app/render');

		$log_level = ($ex instanceof RouterException) ? LoggerLevel::INFO : LoggerLevel::WARNING;
		Logger::instance('LITE')->log($log_level, new CommonMessage('APP EX:'.$ex->getMessage(),
			array('referer'=> $_SERVER['HTTP_REFERER'], 'exception'=>$ex->__toString())));

		//business exception
		if(($ex instanceof BizException)){
			$result = new Result($ex->getMessage());
			/** @var View $tmp */
			$tmp = new $render($result);
			$tmp->render();
		}

		//调试模式
		else if(Config::get('app/debug')){
			Exception::convertExceptionToArray($ex);
		}

		//路由错误，重定向到404页面
		else if($ex instanceof RouterException){
			Http::sendHttpStatus(404);
			if($page404 = Config::get('app/page404')){
				if(is_callable($page404)){
					call_user_func($page404, $ex->getMessage(), $ex);
				} else {
					Http::redirect($page404, 301);
				}
			}
		}

		//其他类型错误，重定向到错误页
		else {
			if($page_error = Config::get('app/pageError')){
				if(is_callable($page_error)){
					call_user_func($page_error, $ex->getMessage(), $ex);
				} else {
					Http::redirect($page_error);
				}
			} else {
				die("Uncaught exception '".get_class($ex)."' with message '".$ex->getMessage()."'");
			}
		}
		exit;
	}
	
	/**
	 * send http charset
	 * @throws Exception
	 */
	private static function sendCharset(){
		if(!headers_sent()){
			header('Content-Type:text/html; charset='.Config::get('app/charset'));
		}
	}

	/**
	 * 初始化web模式
	 * @throws \Exception
	 */
	private function initWebMode(){
		$result = null;
		try {
			//init charset
			self::sendCharset();

			//init router
			Router::init();

			//start controller dispatch
			$result = self::dispatch();
		} catch(\Exception $ex){
			$this->handleWebException($ex);
		}

		//auto render
		if(Config::get('app/auto_render')){
			//controller有可能因为construct失败，导致没有实例化
			$ctrl_ins = self::getController();
			$tpl_file = $ctrl_ins ? $ctrl_ins::__getTemplate(Router::getController(), Router::getAction()) : null;

			if($result instanceof View){
				$result->render($tpl_file);
			} else {
				/** @var View $viewer */
				$render = Config::get('app/render');
				$viewer = new $render($result);
				$viewer->render($tpl_file);
			}
		}
	}

	/**
	 * 获取WEB模式使用的controller对象
	 * @return Controller
	 */
	public static function getController(){
		return self::$controller;
	}
	
	/**
	 * 分发控制器
	 * @return mixed
	 * @throws \Lite\Exception\RouterException
	 * @throws \ReflectionException
	 */
	private function dispatch(){
		$controller = Router::getController();
		$action = Router::getAction();
		$get = Router::get();
		$post = Router::post();

		if(!class_exists($controller)){
			throw new RouterException('Controller no found: '.$controller);
		}

		/** @var Controller $ctrl_instance */
		$ctrl_instance = new $controller($controller, $action);
		self::$controller = $ctrl_instance;
		$is_ctrl_prototype = $ctrl_instance instanceof Controller;

		//support some class non extends lite\controller
		if($is_ctrl_prototype){
			$cancel = $ctrl_instance->__beforeExecute($controller, $action);
			if($cancel === false){
				die;
			}
		}

		if(!method_exists($ctrl_instance, $action)){
			if(!method_exists($ctrl_instance, '__call')){
				throw new RouterException('Method no exists: '.$controller.'/'.$action);
			}
			//支持__call魔术方法
			$result = call_user_func(array($ctrl_instance, $action));
			if($is_ctrl_prototype){
				$ctrl_instance->__afterExecute($controller, $action, $result);
			}
			return $result;
		}

		//禁止私有方法、静态方法被当做action访问
		$rc = new ReflectionClass($controller);
		$m = $rc->getMethod($action);
		if(!$m->isPublic() || $m->isStatic()){
			throw new RouterException('Action should be public and non static');
		}

		$result = call_user_func(array($ctrl_instance, $action), $get, $post);
		if($is_ctrl_prototype){
			$ctrl_instance->__afterExecute($controller, $action, $result);
		}
		return $result;
	}

	/**
	 * 初始化CLI模式
	 * CLI模式支持参数格式为：
	 * php -f test.php -- id=3 name=hello
	 * 相应的参数会被转换为$_REQUEST变量
	 */
	private function initCLIMode(){
		$argv = $_SERVER['argv'];
		$params = array();

		if(!empty($argv)){
			//remove file name
			array_shift($argv);
			foreach($argv as $index => $arg){
				if(strpos($arg, '=') !== false){
					list($k, $v) = explode('=', $arg);
					$params[$k] = decodeURI($v);
				}else{
					$params[$index] = $arg;
				}
			}
		}
		$_REQUEST = $params;
	}

	/**
	 * 获取初始化应用ID
	 * @return string
	 */
	public static function getNamespace(){
		return self::$namespace;
	}

	/**
	 * 获取当前 框架 include_paths
	 * @return array
	 */
	private static function getIncludePaths(){
		return self::$include_paths;
	}

	/**
	 * 添加自动加载目录
	 * @param string $path 搜索目录
	 * @param string $namespace 命名空间
	 * @param bool $case_sensitive 是否大小写敏感，缺省为大小写敏感
	 * 一般项目中只有类似Controller才需要忽略大小写，缺省为严格匹配大小写。
	 * 由于项目基本运行于Linux中，而Windows一般为开发者环境，因此在Windows中，
	 * 默认自动加载的文件名、路径大小写将严格匹配，避免项目发布后在Linux环境出现文件无法访问情况。
	 */
	public static function addIncludePath($path, $namespace = '\\', $case_sensitive = true){
		$namespace = trim($namespace, '\\');
		self::$include_paths[] = [$path, $namespace, $case_sensitive];
	}

	/**
	 * 自动加载处理方法
	 * @param $class
	 * @throws \Lite\Exception\Exception
	 */
	private function autoload($class){
		$paths = self::getIncludePaths();
		foreach($paths as $item){
			list($path, $ns, $case_sensitive) = $item;
			if(!$ns || stripos($class, $ns) === 0){
				$file = $path.str_replace('\\', '/', substr($class, strlen($ns)+1)).'.php';

				//大小写敏感
				if($case_sensitive){
					$ret = file_exists_case_sensitive($file);
					if($ret){
						include_once $file;
						return;
					} else if($ret === null){
						throw new Exception("文件名大小写不一致，将可能导致代码发布到Linux环境之后不可用：\n".$file."\n".realpath($file));
					} else {
						//文件不存在
					}
				}

				//大小写不敏感，优先通过系统判断文件是否存在
				else if(is_file($file)){
					include_once $file;
					return;
				}

				//大小写不敏感，对目录进行枚举
				else {
					$file = file_exists_case_insensitive($file, $path);
					if($file){
						include $file;
						return;
					}
				}
			}
		}
	}
}