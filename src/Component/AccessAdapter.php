<?php
namespace Lite\Component;
use Lite\Core\Config;
use Lite\Core\Hooker;
use function Lite\func\session_start_once;
use function Lite\func\session_write_once;

session_start_once();

/**
 * 权限控制基类
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
abstract class AccessAdapter {
	const EVENT_BEFORE_LOGIN = 'EVENT_BEFORE_LOGIN';
	const EVENT_AFTER_LOGIN = 'EVENT_AFTER_LOGIN';
	const EVENT_BEFORE_LOGOUT = 'EVENT_BEFORE_LOGOUT';
	const EVENT_AFTER_LOGOUT = 'EVENT_AFTER_LOGOUT';

	protected static $instance = array();
	protected $session_name = 'uid';
	protected $cookie_name = 'uid';
	protected $cookie_sid_name = 'sid';
	protected $cookie_expired = 0;
	protected $cookie_path = '/';
	protected $cookie_domain = null;

	/**
	 * 加密用户ID
	 * @param $val
	 * @return string
	 */
	protected function encryptUid($val){
		$app_path = Config::get('app/path');
		return md5($val.$app_path.date('Y-m'));
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
	protected function __construct(){
	}

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
		$user_info = $this->getLoginInfo();
		if($user_info){
			return $this->getIdFromUserInfo($user_info);
		}
		return null;
	}

	/**
	 * 获取登录用户信息
	 * @return mixed
	 */
	public function getLoginInfo(){
		$session_uid = isset($_SESSION[$this->session_name]) ? $_SESSION[$this->session_name] : null;
		if($session_uid){
			return $this->getUserInfoFromId($session_uid);
		}
		if($this->cookie_expired){
			$cookie_uid = isset($_COOKIE[$this->cookie_name]) ? $_COOKIE[$this->cookie_name] : null;
			$cookie_sid = isset($_COOKIE[$this->cookie_sid_name]) ? $_COOKIE[$this->cookie_sid_name] : null;

			if($this->encryptUid($cookie_uid) == $cookie_sid){
				$user_info = $this->getUserInfoFromId($cookie_uid);
				if($user_info){
					$this->updateCookieInfo($user_info);
					return $user_info;
				}
			}
		}
		return null;
	}

	/**
	 * 以用户ID登录
	 * @param $uid
	 * @return bool
	 */
	public function loginById($uid){
		Hooker::fire(self::EVENT_BEFORE_LOGIN, $uid);
		$_SESSION[$this->session_name] = $uid;
		session_write_close();

		$result = null;
		if($this->cookie_expired){
			$now = time();
			$result = setcookie($this->cookie_name, $uid, $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
			$result = $result && setcookie($this->cookie_sid_name, $this->encryptUid($uid), $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
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
	 * 更新cookie信息
	 * @param $user_info
	 */
	protected function updateCookieInfo($user_info){
		static $sent;
		if(!$sent && $this->cookie_expired){
			$sent = true;
			$uid = $this->getIdFromUserInfo($user_info);
			$now = time();
			if(!headers_sent()){
				setcookie($this->cookie_name, $uid, $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
				setcookie($this->cookie_sid_name, $this->encryptUid($uid), $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
			}
		}
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
		session_write_once();
		setcookie($this->cookie_name, '', 0, $this->cookie_path, $this->cookie_domain);
		setcookie($this->cookie_sid_name, '', 0, $this->cookie_path, $this->cookie_domain);
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