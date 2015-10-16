<?php
namespace Lite\Component;
use Lite\Core\Config;
use function Lite\func\dump;

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
	protected $cookie_expired = 3600;
	protected $cookie_path = '/';
	protected $cookie_domain = null;

	protected $config = array();

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
	 * @param array $config
	 * @return self
	 */
	public static function instance(array $config=array()){
		if(!static::$instance){
			$class = get_called_class();
			static::$instance = new $class($config);
		}
		return static::$instance;
	}

	/**
	 * 构造方法
	 * @param $config
	 */
	protected function __construct($config){
		$this->config = array_merge($this->config, $config);
	}

	/**
	 * 从用户信息中获取用户ID
	 * @param $user
	 * @return mixed
	 */
	abstract public function getIdFromUserInfo($user);

	/**
	 * 从用户ID中获取用户信息
	 * @param $user_id
	 * @return array | null
	 */
	abstract public function getUserInfoFromId($user_id);

	/**
	 * 设置cookie过期时间
	 * @param integer $sec 过期时间(秒)
	 */
	public function setCookieExpired($sec=0){
		$this->cookie_expired = $sec;
	}

	/**
	 * 获取配置
	 * @param string $key
	 * @return array | string
	 */
	protected function getConfig($key=''){
		return $key ? $this->config[$key] : $this->config;
	}

	/**
	 * 设置配置
	 * @param array $config
	 */
	protected function setConfig(array $config){
		$this->config = array_merge($this->config, $config);
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
	 * @return array | null
	 */
	public function getLoginInfo(){
		$session_uid = $_SESSION[$this->session_name];
		if($session_uid){
			return $this->getUserInfoFromId($session_uid);
		}

		if($this->cookie_expired){
			$cookie_uid = $_COOKIE[$this->cookie_name];
			$cookie_sid = $_COOKIE[$this->cookie_sid_name];
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
		$_SESSION[$this->session_name] = $uid;

		$result = null;
		if($this->cookie_expired){
			$now = time();
			$result = setcookie($this->cookie_name, $uid, $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
			$result = $result && setcookie($this->cookie_sid_name, $this->encryptUid($uid), $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
		}
		return $result;
	}

	/**
	 * 以用户信息登录
	 * @param array $user_info
	 * @return bool
	 */
	public function login($user_info){
		session_start();
		$uid = $this->getIdFromUserInfo($user_info);
		$this->loginById($uid);
	}

	/**
	 * 更新cookie信息
	 * @param $user_info
	 */
	protected function updateCookieInfo($user_info){
		if($this->cookie_expired){
			$uid = $this->getIdFromUserInfo($user_info);
			$now = time();
			setcookie($this->cookie_name, $uid, $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
			setcookie($this->cookie_sid_name, $this->encryptUid($uid), $this->cookie_expired+$now, $this->cookie_path, $this->cookie_domain);
		}
	}

	/**
	 * 注销
	 */
	public function logout(){
		unset($_SESSION[$this->session_name]);
		setcookie($this->cookie_name, '', 0, $this->cookie_path, $this->cookie_domain);
		setcookie($this->cookie_sid_name, '', 0, $this->cookie_path, $this->cookie_domain);
		return true;
	}

	/**
	 * 检测是否登录
	 * @return bool
	 */
	public function isLogin(){
		return !!$this->getLoginUserId();
	}
}