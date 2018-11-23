<?php
namespace Lite\Core;

/**
 * 控制器基础类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class Controller {
	public static $common_success_message = '操作成功';
	public static $common_fail_message = '操作失败，请重试';

	/**
	 * 构造方法（该方法主要用于容错，避免类继承基础控制器，自身也没有实现构造方法）
	 */
	public function __construct(){}

	/**
	 * 获取当前controller、action配置的模板
	 * 返回空表示由view来指定模板
	 * @param $controller
	 * @param $action
	 * @return string
	 */
	public static function __getTemplate($controller, $action){
		return null;
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
			return new Result(static::$common_success_message, true, null, $jump_url);
		}
		return new Result(static::$common_fail_message);
	}
}