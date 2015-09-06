<?php
namespace Lite\Core;
use Lite\Exception\RouterException;

/**
 * 控制器基础类.,
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class Controller extends Router{
	public static $common_success_message = '操作成功';
	public static $common_fail_message = '操作失败，请重试';

	/**
	 * @var Controller
	 */
	private static $controller;

	/**
	 * get current controller instance
	 * @return Controller
	 */
	public static function getCurrentControllerInstance(){
		return self::$controller;
	}

	/**
	 * 检查控制器目录是否存在
	 * @param $path
	 * @return bool
	 */
	public static function checkPathExists($path){
		$app_path = Config::get('app/path');
		$controller_path = $app_path.DS.'controller'.DS;
		return is_dir($controller_path.$path);
	}

	/**
	 * 分发控制器
	 * @param $namespace
	 * @throws \Lite\Exception\RouterException
	 * @return mixed
	 */
	public static function dispatch($namespace){
		$controller = self::getController();
		$action = self::getAction();
		$get = self::get();
		$post = self::post();

		$cn = $namespace.Config::get('app/controller_pattern');
		$cn = str_replace('{CONTROLLER}', ucfirst($controller), $cn);
		if(!class_exists($cn)){
			throw new RouterException('Controller not found:'.$cn);
		}

		/** @var Controller $ctrl */
		$ctrl = new $cn($controller, $action);
		self::$controller = $ctrl;
		$is_ctrl_prototype = $ctrl instanceof Controller;

		//support some class non extends lite\controller
		if($is_ctrl_prototype){
			$cancel = $ctrl->__beforeExecute($controller, $action);
			if($cancel === false){
				die;
			}
		}

		if(!method_exists($ctrl, $action)){
			throw new RouterException('controller method no exists: '.$controller.'/'.$action);
		}

		$result = call_user_func(array($ctrl, $action), $get, $post);

		if($is_ctrl_prototype){
			$ctrl->__afterExecute($controller, $action, $result);
		}
		return $result;
	}

	/**
	 * 构造方法（该方法主要用于容错，避免类继承基础控制器，自身也没有实现构造方法）
	 */
	public function __construct(){

	}

	/**
	 * 控制器执行之前调用事件方法
	 * @param null $controller
	 * @param null $action
	 * @return bool 返回控制项，如为false，则终端系统后续执行流程
	 */
	public function __beforeExecute($controller=null, $action=null){
		return true;
	}

	/**
	 * 控制器执行之后调用事件方法
	 * @param $controller
	 * @param $action
	 * @param null $result
	 */
	public function __afterExecute($controller, $action, $result=null){

	}

	/**
	 * 获取通用result
	 * @param bool|false $success
	 * @param null $jump_url
	 * @return Result
	 */
	protected function getCommonResult($success=false, $jump_url=null){
		if($success){
			return new Result(self::$common_success_message, true, null, $jump_url);
		}
		return new Result(self::$common_fail_message);
	}

	/**
	 * 检查输入是否为数字
	 * @param $p
	 * @param bool $allow_zero
	 * @return int
	 * @throws RouterException
	 */
	protected function assertPrimaryId($p, $allow_zero=false){
		if(is_array($p)){
			foreach($p as $k=>$v){
				$p[$k] = $this->assertPrimaryId($v, $allow_zero);
			}
			return $p;
		}
		if($allow_zero && $p == '0'){
			return 0;
		}
		if(is_numeric($p) && $p[0] != '0'){
			return intval($p);
		}
		throw new RouterException('number required');
	}
}