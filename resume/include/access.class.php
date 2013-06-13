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

	public function checkLogin(){
		return !!$_SESSION[self::SESS_KEY];
	}

	public function logout(){
		$_SESSION[self::SESS_KEY] = null;
	}
}