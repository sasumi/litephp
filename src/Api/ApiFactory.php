<?php
namespace Lite\Api;

use Lite\Component\Server;
use Lite\Core\Application;
use Lite\Core\Config;
use Lite\Exception\Exception;
use Lite\Exception\Exception as LException;
use Lite\Exception\RouterException;
use function Lite\func\file_exists_case_insensitive;

/**
 * Api 工厂类，定义基本Api调用规则。
 * @package Lite\Api
 */
abstract class ApiFactory{
	protected static $relative_path = 'api/';
	public static $debug = false;
	
	/**
	 * 私有构造器，阻止多实例调用
	 * @param $config
	 */
	private function __construct($config){
	}
	
	/**
	 * singleton
	 * @param array $config
	 * @return static
	 */
	final public static function instance($config = []){
		static $instance;
		if(!$instance){
			$instance = new static($config);
		}
		return $instance;
	}
	
	/**
	 * 路径名称保护
	 * @param $name
	 * @throws LException
	 */
	protected static function nameProtection($name){
		$reg = '/^[\w|\/]+$/';
		$args = func_get_args();
		foreach($args as $arg){
			if(!preg_match($reg, $arg)){
				throw new Exception('Name illegal', null, $arg);
			}
		}
	}
	
	/**
	 * 解析调用路径，默认从PATH_INFO中识别
	 * @return array[class, method] 返回类名、方法名
	 * @throws LException
	 * @throws \Lite\Exception\RouterException
	 * @throws \ReflectionException
	 */
	protected function resolveRequestPath(){
		$path = trim($_SERVER['PATH_INFO'], '/');
		$paths = explode('/', $path);
		$action = array_pop($paths);
		$class = ucfirst(array_pop($paths));
		$dir = join('/', $paths);
		
		static::nameProtection($dir, $class, $action);
		
		$file = Config::get('app/path').static::$relative_path."$dir/$class.php";
		if((Server::inWindows() && !file_exists_case_insensitive($file)) || !is_file($file)){
			throw new RouterException('Request path no found', null, $file);
		}
		include_once $file;
		
		$class_full = Application::getNamespace().str_replace('/', '\\', static::$relative_path."$dir/$class");
		if(!class_exists($class_full)){
			throw new RouterException('Class no found', null, $class_full);
		}
		
		$r = new \ReflectionClass($class_full);
		$method = $r->getMethod($action);
		if($method && $method->isPublic() && !$method->isStatic()){
			return [$class_full, $method];
		}
		throw new RouterException('Method no found', null, "$class_full->$action()");
	}
	
	/**
	 * 异常处理
	 * @param $ex
	 * @return bool
	 */
	protected function onException(\Exception $ex){
		return true;
	}
	
	/**
	 * 解析数据
	 * @return mixed
	 */
	protected abstract function resolveData();
	
	/**
	 * 格式化调用结果数据，返回到客户端
	 * @param mixed $response
	 * @return mixed
	 */
	protected abstract function formatResponse($response);
	
	/**
	 * 方法调用
	 * @param string $class_full
	 * @param string $method
	 * @param mixed $data
	 * @return mixed
	 */
	protected function call($class_full, $method, $data){
		$ins = new $class_full($method, $data);
		return $ins->$method($data);
	}
	
	/**
	 * 开始监听请求
	 * @return mixed|null
	 * @throws \Exception
	 */
	final public function listen(){
		try{
			list($class, $method) = $this->resolveRequestPath();
			$data = $this->resolveData();
			$response = $this->call($class, $method, $data);
			return $this->formatResponse($response);
		} catch(\Exception $e){
			if(static::$debug){
				LException::convertExceptionToArray($e);
			}
			if($this->onException($e) !== false){
				throw $e;
			}
		}
	}
}
