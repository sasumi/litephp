<?php
namespace Lite\Core;

use Lite\Api\Daemon;
use Lite\Component\Http;
use Lite\DB\Driver\DBAbstract;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use Lite\Exception\RouterException;
use Lite\Logger\Logger;
use Lite\Logger\LoggerLevel;
use Lite\Logger\Message\CommonMessage;
use ReflectionClass;
use function Lite\func\array_last;
use function Lite\func\decodeURI;
use function Lite\func\dump;
use function Lite\func\format_size;
use function Lite\func\glob_recursive;
use function Lite\func\print_exception;
use function Lite\func\print_sys_error;

/**
 * Lite框架应用初始化处理类
 * User: sasumi
 * Date: 2015/01/08
 * Time: 9:00
 */
class Application{
	const MODE_WEB = 0x01; //普通HTTP web模式
	const MODE_API = 0x02; //HTTP API模式
	const MODE_CLI = 0x03; //CLI命令模式（该模式提供代码加载逻辑等，不进行初始化Controller）

	const EVENT_BEFORE_APP_INIT = 'EVENT_BEFORE_APP_INIT';
	const EVENT_AFTER_APP_INIT = 'EVENT_AFTER_APP_INIT';
	const EVENT_AFTER_APP_SHUTDOWN = 'EVENT_AFTER_APP_SHUTDOWN';
	const EVENT_ON_APP_EX = 'EVENT_ON_APP_EX';
	const EVENT_ON_APP_ERR = 'EVENT_ON_APP_ERR';

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
		self::addIncludePath(Config::get('app/path'), true);

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

			case self::MODE_API:
				$this->initApiMode();
				break;

			case self::MODE_CLI:
				$this->initCLIMode();
				break;

			default:
				throw new Exception('NO SPEC MODE');
		}
	}

	/**
	 * 初始化框架逻辑
	 * @param null $app_root 项目物理路径
	 * @param string $namespace app namespace
	 * @param int $mode app模式（web模式、API模式、cli模式, SRC源码模式）
	 * @throws Exception
	 * @return Application
	 */
	public static function init($namespace=null, $app_root = null, $mode=self::MODE_WEB){
		if(!self::$instance){
			//BIND APP ERROR
			set_error_handler(function ($code, $message, $file, $line, $context){
				Hooker::fire(Application::EVENT_ON_APP_ERR, $code, $message, $file, $line, $context);
			}, E_USER_ERROR | E_USER_WARNING);

			//BIND APP EXCEPTION
			set_exception_handler(function ($exception){
				self::handleException($exception);
				Hooker::fire(Application::EVENT_ON_APP_EX, $exception);
			});

			//BIND LAST ERROR
			register_shutdown_function(function(){
				$error = error_get_last();
				if($error && ($error['type'] == E_ERROR || $error['type'] == E_WARNING)){
					self::handleException(new Exception($error['message'], null, $error));
				}
				Hooker::fire(Application::EVENT_AFTER_APP_SHUTDOWN);
			});

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
	private static function handleException(\Exception $ex){		
		//调试模式
		if(Config::get('app/debug')){
			print_exception($ex);
		}

		$log_level = ($ex instanceof RouterException) ? LoggerLevel::INFO : LoggerLevel::WARNING;
		Logger::instance('LITE')->log($log_level, new CommonMessage('APP EX:'.$ex->getMessage(),
			array('referer'=> $_SERVER['HTTP_REFERER'], 'exception'=>$ex->__toString())));

		//找不到路由
		if($ex instanceof RouterException){
			Http::sendHttpStatus(404);
			if($page404 = Config::get('app/page404')){
				Http::redirect($page404);
			}
		}
		//其他类型错误
		else {
			if($page_error = Config::get('app/pageError')){
				Http::redirect($page_error);
			};
		}
		//即使上面页面跳转了，这里还会继续输出错误信息，方便调试
		die('<!-- '.htmlspecialchars($ex->getMessage()).'-->');
	}

	/**
	 * send http chartset
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
		self::sendCharset();
		//router init
		Router::init();

		try {
			$result = self::dispatch();
		} catch(Exception $ex){
			if(($ex instanceof BizException) || //业务限制逻辑，直接使用友好输出格式
				(!Config::get('app/debug') && Config::get('app/auto_process_logic_error') && !($ex instanceof RouterException))){
				$result = new Result($ex->getMessage(), false, $ex);
			} else {
				throw $ex;
			}
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
	 */
	private function dispatch(){
		$controller = Router::getController();
		$action = Router::getAction();
		$get = Router::get();
		$post = Router::post();

		self::loadControllerCaseInsensitive($controller);

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
			throw new RouterException('Controller Method No Exists: '.$controller.'/'.$action);
		}

		//禁止私有方法、静态方法被当做action访问
		$rc = new ReflectionClass($controller);
		$m = $rc->getMethod($action);
		if(!$m->isPublic() || $m->isStatic()){
			throw new RouterException('Action Should Be Public And Non Static');
		}

		$result = call_user_func(array($ctrl_instance, $action), $get, $post);
		if($is_ctrl_prototype){
			$ctrl_instance->__afterExecute($controller, $action, $result);
		}
		return $result;
	}

	/**
	 * 初始化API模式
	 */
	private function initApiMode(){
		Router::$GET = $_GET;
		Router::$POST = $_POST;

		if(Router::isPut()){
			$tmp = Router::readInputData();
			Router::$PUT = $tmp ? json_decode($tmp, true) : array();
		}
		if(Router::isDelete()){
			$tmp = Router::readInputData();
			Router::$DELETE = $tmp ? json_decode($tmp, true) : array();
		}
		self::sendCharset();
		Daemon::start();
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
	public static function addIncludePath($path, $case_sensitive=true){
		self::$include_paths[] = $path;
	}

	/**
	 * 自动加载处理方法
	 * @param $class
	 */
	private function autoload($class){
		$paths = self::getIncludePaths();
		foreach($paths as $path){
			if(stripos($class, self::$namespace) === 0){
				$file = substr($class, strlen(self::$namespace)+1);
				$file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
				$file = $path.$file.'.php';
				if(is_file($file)){
					include_once $file;
					return;
				}
			}
			//不包含ns的情况
			$file = $path.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
			if(is_file($file)){
				include_once $file;
			}
		}
	}

	/**
	 * 不区分大小写加载controller文件，包括文件名和目录路径
	 * @param $ctrl_class
	 * @throws \Lite\Exception\RouterException
	 */
	private function loadControllerCaseInsensitive($ctrl_class){
		$controller_root = Config::get('app/path').'controller/';
		$files = glob_recursive($controller_root.'*.php', GLOB_NOSORT);

		$ns = self::$namespace;
		$c = preg_replace('/^'.$ns.'\\\\/', '', $ctrl_class);
		$class_file = str_replace('\\', '/', Config::get('app/path').$c.'.php');

		foreach($files as $f){
			$f = str_replace('\\','/',$f);
			if(strcasecmp($f, $class_file) === 0){
				include_once $f;
				if(!class_exists($ctrl_class)){
					throw new RouterException('controller class not found:'.$ctrl_class);
				}
				return;
			}
		}
		throw new RouterException('controller file not found:'.$ctrl_class);
	}
}