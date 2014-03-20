<?php
define('LIB_PATH', __DIR__.DIRECTORY_SEPARATOR);
include 'function.php';
class Lite {
	private static $instance;

	public static function init($app_path = null){
		if(!self::$instance){
			try {
				self::$instance = new self($app_path);
			} catch(LException $ex){
				$ex->dump();
				die;
			}
		}
		return self::$instance;
	}

	private function __construct($app_path = null){
		//bind lib path
		self::addIncludePath(LIB_PATH);

		//bind lib com path
		self::addIncludePath(LIB_PATH.'com'.DIRECTORY_SEPARATOR);

		Hooker::fire('BEFORE_APP_INIT');

		//init
		Config::init($app_path);

		//get config path
		$config_path = Config::get('app/config');

		//bind include path
		self::addIncludePath(Config::get('app/include'));

		//bind root path
		self::addIncludePath(Config::get('app/path'));

		//import hook
		if(file_exists($config_path.'hook.inc.php')){
			include $config_path.'hook.inc.php';
		}

		if(Config::get('sys/close_magic_gpc') && get_magic_quotes_gpc()){
			function stripslashes_deep($value){
				$value = is_array($value) ?
				array_map('stripslashes_deep', $value) :
				stripslashes($value);
				return $value;
			}
			$_POST = array_map('stripslashes_deep', $_POST);
			$_GET = array_map('stripslashes_deep', $_GET);
			$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		}
		//router init
		Router::init();

		//call controller
		$result = Controller::dispatch();

		//auto render
		if(Config::get('app/autorender')){
			if(is_scalar($result) || $result instanceof CResult){
				echo $result;
			} else {
				$viewer = new View();
				$viewer->assign($result);
				$viewer->render();
			}
		}

		//APP EXCEPTION
		if(Hooker::exists('ON_APP_EX')){
			set_exception_handler(function($exception){
				Hooker::fire('ON_APP_EX', $exception);
			});
		}

		//APP ERROR
		if(Hooker::exists('ON_APP_ERR')){
			set_error_handler(function($code, $message, $file, $line, $context){
				Hooker::fire('ON_APP_ERR', $code, $message, $file, $line, $context);
			}, E_USER_ERROR | E_USER_WARNING);
		}

		Hooker::fire('AFTER_APP_INIT');

		//auto logic
		if(file_exists($config_path.'logic.inc.php')){
			$logic = include $config_path.'logic.inc.php';
			self::handlePageLogic($logic);
		}

		//stat app launch time
		$GLOBALS['__init_time__'] = microtime(true);
		register_shutdown_function(function(){
			$fin_time = microtime(true);
			$run_time = round(($fin_time - $GLOBALS['__init_time__'])*1000, 2);
			Hooker::fire('AFTER_APP_SHUTDOWN', $run_time);
		});
	}

	private static $include_paths = array();

	public static function getIncludePaths(){
		return self::$include_paths;
	}

	/**
	 * add more include path
	 * @param string $path
	 */
	public static function addIncludePath($path){
		foreach (func_get_args() as $path){
			if (!file_exists($path) || (file_exists($path) && filetype($path) !== 'dir')){
				//trigger_error("Include path '{$path}' not exists", E_USER_WARNING);
				continue;
			}

			$paths = explode(PATH_SEPARATOR, get_include_path());
			if(array_search($path, $paths) === false){
				array_push(self::$include_paths, $path);
				array_push($paths, $path);
			}
			set_include_path(implode(PATH_SEPARATOR, $paths));
		}
	}

	/**
	 * remove include path from php setting
	 * @param  string $path
	 */
	public static function removeIncludePath($path){
		foreach (func_get_args() as $path){
			$paths = explode(PATH_SEPARATOR, get_include_path());

			if(($k = array_search($path, self::$include_paths)) !== false){
				unset(self::$include_paths[$k]);
			}

			if(($k = array_search($path, $paths)) !== false){
				unset($paths[$k]);
			} else {
				continue;
			}

			if(!count($paths)){
				trigger_error("Include path '{$path}' can not be removed because it is the only", E_USER_NOTICE);
				continue;
			}
			set_include_path(implode(PATH_SEPARATOR, $paths));
		}
	}

	/**
	 * handler controller logic
	 * @param  array  $logic_config
	 */
	function handlePageLogic(array $logic_config){
		$current_controller = Router::getController();
		$current_action = Router::getAction();
		$get = Router::get();
		$post = Router::post();
		foreach($logic_config as $logic){
			list($route, $caller) = $logic;
			$controller = Router::getDefaultController();
			$action = Router::DEFAULT_ACTION;

			if($route == '*'){
				$controller = '*';
				$action = '*';
			} else if(strpos('/', $route) > 0){
				list($controller, $action) = explode('/', $route);
			} else {
				$controller = $route;
				$action = '*';
			}

			if($controller == '*' || $controller == $current_controller){
				if($action == '*' || $action == $current_action){
					call_user_func($caller, $get, $post, $current_controller, $current_action);
				}
			}
		}
	}
}

//auto class loader
spl_autoload_register(function($class){
	$paths = Lite::getIncludePaths();

	foreach($paths as $path){
		$class = str_replace('_', DIRECTORY_SEPARATOR , $class);
		$file = $path.strtolower($class).'.class.php';

		$file2 = $path.strtolower(str_replace("\\", DIRECTORY_SEPARATOR, $class));
		if(file_exists($file) && is_file($file)){
			include $file;
			return;
		} else if(file_exists($file2) && is_file($file2)){
			include $file2;
			return;
		}
	}
	dump($class, $file, $file2, $paths);
});