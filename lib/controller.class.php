<?php
abstract class Controller extends Router{
	/**
	 * dispatch controller
	 * @throws LException
	 * @return mixed
	 */
	public static function dispatch(){
		$controller = self::getController();
		$action = self::getAction();
		$get = self::get();
		$post = self::post();

		$cn = 'Controller_'.ucfirst($controller);
		$ctrl = new $cn($controller, $action);

		$cancel = $ctrl->__beforeExecute($controller, $action);
		if($cancel === false){
			die;
		}

		if(!method_exists($ctrl, $action)){
			throw new LException('controller method no exists', $ctrl, $action);
		}

		$result = call_user_func(array($ctrl, $action), $get, $post);
		$ctrl->__afterExecute($controller, $action, $result);

		return $result;
	}

	public function __construct(){
		//ignore error
	}

	/**
	 * before controller execute
	 * return false to cancel current dispatch
	 */
	public function __beforeExecute($controller, $action){
		return true;
	}

	/**
	 * after controller method execute
	 */
	public function __afterExecute($controller, $action, $result=null){

	}
}