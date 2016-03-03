<?php
namespace Lite\Core;

use Exception as Exception;
use Lite\Api\Daemon;
use Lite\DB\Record;
use Lite\Exception\BizException;
use Lite\Exception\RouterException;
use function Lite\func\print_exception;
use Lite\Logger\Logger;
use Lite\Logger\LoggerLevel;
use Lite\Logger\Message\CommonMessage;
use ReflectionClass;
use function Lite\func\array_last;
use function Lite\func\decodeURI;
use function Lite\func\dump;
use function Lite\func\format_size;
use function Lite\func\print_sys_error;

/**
 * Lite框架应用初始化处理类
 * User: sasumi
 * Date: 2015/01/08
 * Time: 9:00
 */
class Application{
	const MODE_WEB = 1; //普通HTTP web模式
	const MODE_API = 2; //HTTP API模式
	const MODE_CLI = 3; //CLI命令模式
	const MODE_SRC = 4; //源码模式（该模式提供代码加载逻辑等，不进行初始化Controller）

	const EVENT_BEFORE_APP_INIT = 'EVENT_BEFORE_APP_INIT';
	const EVENT_AFTER_APP_INIT = 'EVENT_AFTER_APP_INIT';
	const EVENT_AFTER_APP_SHUTDOWN = 'EVENT_AFTER_APP_SHUTDOWN';
	const EVENT_ON_APP_EX = 'EVENT_ON_APP_EX';
	const EVENT_ON_APP_ERR = 'EVENT_ON_APP_ERR';

	//Controller文件名是否区分大小写
	public static $CONTROLLER_FILE_NAME_CASE_INSENSITIVE = true;

	private static $instance;
	private static $include_paths = array();
	private $namespace;

	/**
	 * 初始化框架逻辑
	 * @param null $app_root 项目物理路径
	 * @param string $namespace app namespace
	 * @param int $mode app模式（web模式、API模式、cli模式, SRC源码模式）
	 * @throws Exception
	 * @return Application
	 */
	public static function init($namespace, $app_root = null, $mode=self::MODE_WEB){
		if(!self::$instance){
			try{
				self::$instance = new self($namespace, $app_root, $mode);
			} catch(Exception $ex){
				//调试模式
				if(Config::get('app/debug')){
					print_exception($ex);
				}

				$log_level = ($ex instanceof RouterException) ? LoggerLevel::INFO : LoggerLevel::WARNING;
				Logger::instance('LITE')->log($log_level, new CommonMessage('APP EX:'.$ex->getMessage(),
					array('referer'=> $_SERVER['HTTP_REFERER'], 'exception'=>$ex->__toString())));

				//找不到路由
				if($ex instanceof RouterException){
					if($page404 = Config::get('app/page404')){
						Request::sendHttpStatus(404);
						$vc = Config::get('app/render');

						/** @var View $view */
						$view = new $vc();
						$view->render($page404);
					}
				}
				//其他类型错误
				else {
					if($page_error = Config::get('app/page_error')){
						Header('Location:'.$page_error);
					};
				}

				//即使上面页面跳转了，这里还会继续输出错误信息，方便调试
				die('<!-- '.htmlspecialchars($ex->getMessage()).'-->');
			}
		}
		return self::$instance;
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

		/** @var Controller $ctrl_ins */
		$ctrl_ins = null;

		try {
			$result = self::dispatch($ctrl_ins, $method);
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
			$tpl_file = $ctrl_ins::__getTemplate(Router::getController(), Router::getAction());
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
	 * 分发控制器
	 * @param null $ctrl
	 * @param null $action
	 * @return mixed
	 * @throws \Lite\Exception\RouterException
	 */
	private function dispatch(&$ctrl=null, &$action=null){
		$controller = Router::getController();
		$action = Router::getAction();
		$get = Router::get();
		$post = Router::post();

		$CtrlClass = $this->namespace.Config::get('app/controller_pattern');
		$CtrlClass = str_replace('{CONTROLLER}', ucfirst($controller), $CtrlClass);

		if(!class_exists($CtrlClass)){
			//在Linux环境下，如果Controller文件找不到，
			//会尝试再次不区分文件名大小写去目录找一次文件
			if(self::$CONTROLLER_FILE_NAME_CASE_INSENSITIVE){
				self::loadControllerCaseInsensitive($CtrlClass);
			}
			if(!class_exists($CtrlClass)){
				throw new RouterException('Controller not found:'.$CtrlClass);
			}
		}

		/** @var Controller $ctrl */
		$ctrl = new $CtrlClass($controller, $action);
		$is_ctrl_prototype = $ctrl instanceof Controller;

		//support some class non extends lite\controller
		if($is_ctrl_prototype){
			$cancel = $ctrl->__beforeExecute($controller, $action);
			if($cancel === false){
				die;
			}
		}

		if(!method_exists($ctrl, $action)){
			throw new RouterException('Controller Method No Exists: '.$controller.'/'.$action);
		}

		//禁止私有方法、静态方法被当做action访问
		$rc = new ReflectionClass($CtrlClass);
		$m = $rc->getMethod($action);
		if(!$m->isPublic() || $m->isStatic()){
			throw new RouterException('Action Should Be Public And Non Static');
		}

		$result = call_user_func(array($ctrl, $action), $get, $post);

		if($is_ctrl_prototype){
			$ctrl->__afterExecute($controller, $action, $result);
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
		if(PHP_SAPI != 'cli'){
			Request::sendHttpStatus(405);
			die('ACCESS DENY');
		}

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
	 * 框架初始化方法
	 * @param $namespace
	 * @param null $app_root
	 * @param $mode
	 * @throws \Exception
	 * @internal param null $app_root
	 */
	private function __construct($namespace, $app_root = null, $mode){
		$this->namespace = $namespace;

		//注册项目文件自动加载逻辑
		spl_autoload_register(array($this, 'autoload'));

		//记录系统启动时间
		$__init_time__ = microtime(true);

		Hooker::fire(self::EVENT_BEFORE_APP_INIT);

		//配置初始化
		Config::init($app_root);

		//自动性能统计（SQL），仅在web模式中生效
		if($mode == self::MODE_WEB && Config::get('app/auto_statistics')){
			$this->autoStatistics();
		}

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

		//APP EXCEPTION
		if(Hooker::exists(self::EVENT_ON_APP_EX)){
			set_exception_handler(function ($exception){
				Hooker::fire(Application::EVENT_ON_APP_EX, $exception);
			});
		}

		//APP ERROR
		if(Hooker::exists(self::EVENT_ON_APP_ERR)){
			set_error_handler(function ($code, $message, $file, $line, $context){
				Hooker::fire(Application::EVENT_ON_APP_ERR, $code, $message, $file, $line, $context);
			}, E_USER_ERROR | E_USER_WARNING);
		}

		register_shutdown_function(function () use ($__init_time__){
			$run_time = round((microtime(true) - $__init_time__)*1000, 2);
			Hooker::fire(Application::EVENT_AFTER_APP_SHUTDOWN, $run_time);
		});

		Hooker::fire(self::EVENT_AFTER_APP_INIT);

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

			case self::MODE_SRC:
				//source mode
				break;

			default:
				throw new Exception('NO SPEC MODE');
		}
	}

	/**
	 * 获取初始化应用ID
	 * @return string
	 */
	public function getNamespace(){
		return $this->namespace;
	}

	/**
	 * 系统性能统计
	 * 可以通过 ?SYS_STAT 查看上次查询结果
	 * 需要在相应的配置项中打开，才具备这个功能，缺省关闭
	 * @example http://www.hello.com/?SYS_STAT
	 */
	private function autoStatistics(){
		$SESSION_KEY = '_SYS_STATIC_INFO_';
		$STATIC_KEY = 'SYS';
		$GLOBALS['__DB_QUERY_COUNT__'] = 0;
		$GLOBALS['__DB_QUERY_TIME__'] = 0;
		$GLOBALS['__DB_QUERY_MEM__'] = 0;
		$GLOBALS['__DB_QUERY_DEDUPLICATION_COUNT__'] = 0;

		Hooker::add(Record::EVENT_BEFORE_DB_QUERY, function ($sql) use ($STATIC_KEY){
			$GLOBALS['__DB_QUERY_COUNT__']++;
			Statistics::instance($STATIC_KEY)->mark('BEFORE DB QUERY', $sql);
		});

		Hooker::add(Record::EVENT_AFTER_DB_QUERY, function ($sql) use ($STATIC_KEY){
			Statistics::instance($STATIC_KEY)->markAfter('AFTER DB QUERY', $sql);
			$tmp = Statistics::instance($STATIC_KEY)->getTimeTrackList();
			$tt = array_last($tmp);
			$GLOBALS['__DB_QUERY_TIME__'] += $tt['time_used'];
			$GLOBALS['__DB_QUERY_MEM__'] += $tt['mem_used'];
		});

		Hooker::add(Record::EVENT_ON_DB_QUERY_DISTINCT, function(){
			$GLOBALS['__DB_QUERY_DEDUPLICATION_COUNT__']++;
		});

		Hooker::add(self::EVENT_AFTER_APP_SHUTDOWN, function ($tm = null) use ($SESSION_KEY, $STATIC_KEY){
			$pc = 0;
			if($GLOBALS['__DB_QUERY_DEDUPLICATION_COUNT__'] + $GLOBALS['__DB_QUERY_COUNT__']){
				$pc = number_format($GLOBALS['__DB_QUERY_DEDUPLICATION_COUNT__'] / ($GLOBALS['__DB_QUERY_DEDUPLICATION_COUNT__'] + $GLOBALS['__DB_QUERY_COUNT__'])*100, 2, null, '');
			}

			$msg = 'DB QUERY COUNT:'.$GLOBALS['__DB_QUERY_COUNT__'].
				"\t\t\tDB QUERY TIME:".$GLOBALS['__DB_QUERY_TIME__']."ms\n".
				"DEDUPLICATION QUERY:".$GLOBALS['__DB_QUERY_DEDUPLICATION_COUNT__']."($pc%)".
				"\t\tDB QUERY COST MEM:".format_size($GLOBALS['__DB_QUERY_MEM__'])."\n\n";

			$msg .= "[PROCESS USED TIME] ".$tm."ms";
			Statistics::instance($STATIC_KEY)->mark($msg, str_repeat('=', 120)."\nAPP SHUTDOWN");
			if(!headers_sent()){
				session_start();
			}
			$_SESSION[$SESSION_KEY] = Statistics::instance($STATIC_KEY)->_toString();
		});

		//OUTPUT
		Hooker::add(Router::EVENT_AFTER_ROUTER_INIT, function ($ctrl, $act, $get) use ($SESSION_KEY, $STATIC_KEY){
			if(isset($get['SYS_STAT'])){
				if(!headers_sent()){
					session_start();
				}
				echo $_SESSION[$SESSION_KEY];
				exit;
			}
		});
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
			if(stripos($class, $this->namespace) === 0){
				$file = substr($class, strlen($this->namespace)+1);
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
	 * 不区分大小写加载controller文件（注意，仅文件名不区分大小写，目录还是区分的）
	 * @param $ctrl_class
	 * @return bool
	 */
	private function loadControllerCaseInsensitive($ctrl_class){
		$p = Config::get('app/path');
		$ns = $this->namespace;
		$ctrl_class = preg_replace('/^'.$ns.'\\\\/', '', $ctrl_class);
		$f = rtrim($p, '/\\').'/'.$ctrl_class.'.php';
		$dir = dirname($f);
		$base_name = strtolower(basename($f));
		$all_files = glob($dir.'/*.php', GLOB_NOSORT);
		foreach($all_files as $cf){
			if(strtolower(basename($cf)) == $base_name){
				include_once $cf;
				return true;
			}
		}
		return false;
	}
}
