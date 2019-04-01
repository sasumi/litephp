<?php
namespace Lite\Core;

use Lite\Component\Net\Http;
use Lite\Component\Server;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use Lite\Exception\RouterException;
use Lite\Logger\Logger;
use Lite\Logger\LoggerLevel;
use Lite\Logger\Message\CommonMessage;
use ReflectionClass;
use function Lite\func\decodeURI;
use function Lite\func\file_exists_ci;
use function Lite\func\file_real_exists;
use function Lite\func\microtime_diff;
use function Lite\func\print_exception;

/**
 * Lite框架应用初始化处理类
 * User: sasumi
 * Date: 2015/01/08
 * Time: 9:00
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

	//application instance
	private static $instance;

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
		self::addIncludePath(Config::get('app/path'));

		//绑定项目include目录
		self::addIncludePath(Config::get('app/path').'include/');

		//绑定vendor目录loader
		$vl = Config::get('app/root').'vendor/autoload.php';
		if(is_file($vl)){
			include_once $vl;
		}

		//绑定项目数据库定义目录
		self::addIncludePath(Config::get('app/database_source'));

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
		if(!self::$instance){
			self::$init_microtime = microtime();
			
			//BIND APP ERROR
			set_error_handler(function ($code, $message, $file, $line, $context){
				Hooker::fire(Application::EVENT_ON_APP_ERR, $code, $message, $file, $line, $context);
			}, E_USER_ERROR | E_USER_WARNING);

			Hooker::fire(self::EVENT_AFTER_APP_INIT);

			//init app
			self::$instance = new self($namespace, $app_root, $mode);
		}
		return self::$instance;
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
			print_exception($ex);
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
	 * @throws \Lite\Exception\Exception
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
	public static function getIncludePaths(){
		return self::$include_paths;
	}

	/**
	 * 添加include path
	 * @param string $path
	 */
	public static function addIncludePath($path){
		self::$include_paths[] = $path;
	}

	/**
	 * 自动加载处理方法
	 * @param $class
	 */
	private function autoload($class){
		$case_sensitive = Server::inWindows();
		$paths = self::getIncludePaths();
		foreach($paths as $path){
			if(stripos($class, self::$namespace) === 0){
				$file = substr($class, strlen(self::$namespace)+1);
				$file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
				$file = $path.$file.'.php';
				if(is_file($file) || (!Server::inWindows() && $file = file_exists_ci($file))){
					include_once $file;
					return;
				}
			}
			//不包含ns的情况
			$file = $path.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
			if(is_file($file) && (!$case_sensitive || file_real_exists($file))){
				include_once $file;
				return;
			}
		}
	}
}