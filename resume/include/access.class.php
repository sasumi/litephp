<?php
class Access{
	const SESS_KEY = 'BE_ACESS_SK';
	private static $ins;
	private function __construct($option=array()){

	}

	public static function init($option=array()){
		if(!self::$ins){
			self::$ins = new self($option);
		}
		return self::$ins;
	}

	public static function setLoginInfo($info){
		return $_SESSION[self::SESS_KEY] = $info;
	}

	public function getLoginInfo(){
		return $_SESSION[self::SESS_KEY];
	}

	public static function checkLogin($request, $page, $action){
		$ins = self::init();
		if($info = $ins->getLoginInfo()){
			dump($info, 1);
		} else if($page != 'user' && $action != 'login' && $action != 'register'){
			jump_to('user/login');
		}
	}

	public function logout(){
		$_SESSION[self::SESS_KEY] = null;
	}
}