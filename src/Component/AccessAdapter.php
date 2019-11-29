<?php
namespace Lite\Component;
use Lite\Core\Hooker;
use function Lite\func\session_start_once;

/**
 * 权限控制适配器
 * User: sasumi
 */
abstract class AccessAdapter {
	const EVENT_BEFORE_LOGIN = __CLASS__.'EVENT_BEFORE_LOGIN';
	const EVENT_AFTER_LOGIN = __CLASS__.'EVENT_AFTER_LOGIN';
	const EVENT_BEFORE_LOGOUT = __CLASS__.'EVENT_BEFORE_LOGOUT';
	const EVENT_AFTER_LOGOUT = __CLASS__.'EVENT_AFTER_LOGOUT';
	
	protected static $instance = array();
	protected $session_name = 'uid';
	protected $cookie_name = 'uid';
	protected $cookie_sid_name = 'sid';
	protected $cookie_expired = 0; //前台保持登录态时间
	protected $cookie_path = '/';
	protected $cookie_domain = null;
	protected $cookie_encrypt_tail_str = 'lp_cookie_20190609';
	
	/**
	 * 加密用户ID
	 * @param $val
	 * @return string
	 */
	protected function encryptUid($val){
		return md5($val.$_SERVER['HTTP_HOST'].date('Ym').$this->cookie_encrypt_tail_str);
	}
	
	/**
	 * 单例
	 * @return static
	 */
	public static function instance(){
		static $ins;
		if(!$ins){
			$ins = new static();
		}
		return $ins;
	}
	
	/**
	 * 构造方法
	 */
	protected function __construct(){}
	
	/**
	 * 从用户信息中获取用户ID
	 * @param $user
	 * @return mixed
	 */
	abstract protected function getIdFromUserInfo($user);
	
	/**
	 * 从用户ID中获取用户信息
	 * @param $user_id
	 * @return array | null
	 */
	abstract protected function getUserInfoFromId($user_id);
	
	/**
	 * 设置cookie过期时间
	 * @param integer $sec 过期时间(秒)
	 */
	public function setCookieExpired($sec=0){
		$this->cookie_expired = $sec;
	}
	
	/**
	 * 获取登录用户ID
	 */
	public function getLoginUserId(){
		return isset($_SESSION[$this->session_name]) ? $_SESSION[$this->session_name] : null;
	}
	
	/**
	 * 获取登录用户信息
	 * @return mixed
	 */
	public function getLoginInfo(){
		session_start_once();
		$session_uid = $this->getLoginUserId();
		$user_info = null;
		if($session_uid){
			$user_info = $this->getUserInfoFromId($session_uid);
		}
		if($this->cookie_expired){
			$this->updateCookieInfo($user_info);
		}
		return $user_info;
	}
	
	/**
	 * 以用户ID登录
	 * @param $uid
	 * @return bool
	 */
	public function loginById($uid){
		Hooker::fire(self::EVENT_BEFORE_LOGIN, $uid);
		session_start();
		$_SESSION[$this->session_name] = $uid;
		
		$result = null;
		if($this->cookie_expired){
			$result = $this->createCookieInfo($uid);
		}
		Hooker::fire(self::EVENT_AFTER_LOGIN, $uid);
		return $result;
	}
	
	/**
	 * 以用户信息登录
	 * @param array|mixed $user_info
	 * @return bool
	 */
	public function login($user_info){
		session_start();
		$uid = $this->getIdFromUserInfo($user_info);
		$this->loginById($uid);
		return true;
	}
	
	/**
	 * 创建登录cookie信息
	 * @param $uid
	 * @return bool
	 */
	private function createCookieInfo($uid){
		$now = time();
		$result = setcookie($this->cookie_name, $uid, $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
		$result = $result && setcookie($this->cookie_sid_name, $this->encryptUid($uid), $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
		return $result;
	}
	
	/**
	 * 更新cookie信息
	 * @param $user_info
	 */
	private function updateCookieInfo($user_info){
		//确保单次请求只发送一次setcookie，
		//避免nginx处理setcookie的header数据量过大导致502发生
		static $sent;
		if($sent){
			return;
		}
		$sent = true;
		
		$cookie_uid = isset($_COOKIE[$this->cookie_name]) ? $_COOKIE[$this->cookie_name] : null;
		$cookie_sid = isset($_COOKIE[$this->cookie_sid_name]) ? $_COOKIE[$this->cookie_sid_name] : null;
		if(!$cookie_sid || !$cookie_uid || $this->encryptUid($cookie_uid) != $cookie_sid){
			return;
		}
		$uid = $this->getIdFromUserInfo($user_info);
		$now = time();
		if(!headers_sent()){
			setcookie($this->cookie_name, $uid, $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
			setcookie($this->cookie_sid_name, $this->encryptUid($uid), $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
		}
	}
	
	/**
	 * 销毁cookie中的登录信息
	 */
	private function destroyCookieInfo(){
		setcookie($this->cookie_name, '', 0, $this->cookie_path, $this->cookie_domain);
		setcookie($this->cookie_sid_name, '', 0, $this->cookie_path, $this->cookie_domain);
	}
	
	/**
	 * 注销
	 */
	public function logout(){
		Hooker::fire(self::EVENT_BEFORE_LOGOUT);
		if(!headers_sent()){
			session_start();
		}
		unset($_SESSION[$this->session_name]);
		$this->destroyCookieInfo();
		Hooker::fire(self::EVENT_AFTER_LOGOUT);
		return true;
	}
	
	/**
	 * 检测是否登录
	 * @return bool
	 */
	public static function isLogin(){
		$ins = static::instance();
		return $ins->getLoginUserId();
	}
}
