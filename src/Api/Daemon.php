<?php
namespace Lite\Api;

use Lite\Component\Request;
use Lite\Core\Config;
use Lite\Core\Router;
use Lite\Exception\Exception;

/**
 * Creator: sasumi
 * Date: 14-10-18
 * Time: 下午9:48
 */
abstract class Daemon{
	/**
	 * start api daemon
	 * @return bool
	 */
	public static function start(){
		$router = Config::get('api/parser');
		$api_path = Config::get('api/path');

		/** @var Server $api_instance */
		$api_instance = null;

		/** @var array $param */
		$param = null;
		list($api_instance, $param) = ($router ? call_user_func($router, $api_path) : self::defaultParser($api_path));
		if(!$api_instance){
			Request::sendHttpStatus(404);
			return false;
		}

		if($api_instance->beforeExecute($api_instance, $param) === false){
			return false;
		}

		//check request format
		$request_format = $api_instance->requestFormat();

		if(!$api_instance->checkRequestFormat($param, $request_format)){
			Request::sendHttpStatus(501);
		}

		if($api_instance->certify($param) !== true){
			Request::sendHttpStatus(401);
			return false;
		}

		$result = $api_instance->execute($param);

		//check response format
		$response_format = $api_instance->responseFormat();

		$api_instance->afterExecute($api_instance, $param, $result);
		return true;
	}

	/**
	 * default api router parser
	 * URI support http://www.site.com/api/index.php/module.action
	 * param support GET & POST, or mixed model
	 * use file name: module.action.class.php
	 * @param string $api_path
	 * @throws Exception
	 * @return array|null
	 */
	public static function defaultParser($api_path){
		$cmd = Router::getPathInfo();
		$param = array_merge(Router::post(), Router::get());
		if(preg_match('/^[\w|\.]+$/', $cmd)){
			$cmd_file = $api_path.$cmd.".php";
			if(is_file($cmd_file)){
				include $cmd_file;
				$arr = explode('.', $cmd);
				array_walk($arr, function (&$item){
					$item = ucfirst($item);
				});
				$class_name = 'Api_'.join('_', $arr);
				if(class_exists($class_name)){
					$ins = new $class_name($param);
					return array($ins, $param);
				}else{
					throw new Exception('API CLASS NOT FOUND:'.$class_name);
				}
			}else{
				throw new Exception('API FILE NOT FOUND:'.$cmd_file);
			}
		}else{
			throw new Exception('CMD ILLEGAL:'.$cmd);
		}
	}
}