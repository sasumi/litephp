<?php
session_start();
if(!defined('IN_DEBUG')){
	define('IN_DEBUG', false);
}
define('WEIBO_API_LIB_PATH', str_replace("\\",'/',dirname(__FILE__)).'/');

include_once WEIBO_API_LIB_PATH.'weibodemo/weibooauth.php';
include_once WEIBO_API_LIB_PATH.'sdkdom/opent.php';
include_once WEIBO_API_LIB_PATH.'sdkdom/api_client.php';

/**
 * 微博接口自动处理类库
 * @deprecate 该库包含对oAuth验证回调请求处理，涉及页面的跳转、传参
 * 因此在一些业务逻辑中，如需要对此进行控制，可能满足不了相关需求
 **/
class Telcom_Twitter_Interface {
	private $WEIBO_AUTH_TOKEN_KEY = 'weibo_key_';
	private $WEIBO_LAST_AUTH_TOKEN_KEY = 'weibo_key_last_';
	
	private $WEIBO_CLIENT_CLASS;
	private $WEIBO_OAUTH_CLASS;
	
	private static $instance_list = array();
	private $weibo_obj;
	private $weibo_client;
	
	private $config = array(
		'WB_TYPE'=> '',
		'WB_AKEY'=> '',
		'WB_SKEY'=> ''
	);
	
	/**
	 * 单例构建方法
	 * @param array $config
	 * @return Twitter_Interface
	 **/
	public static function init(array $config){
		if(!self::$instance_list[$config['WB_TYPE']]){
			self::$instance_list[$config['WB_TYPE']] = new self($config, $session_token);
		}
		return self::$instance_list[$config['WB_TYPE']];
	}
	
	/**
	 * 私有构造方法
	 * @private
	 * @param array $config
	 **/
	private function __construct(array $config){
		$this->setConfig($config);
		
		$auth_token = $this->getAuthToken();
		$last_token = $this->getLoginToken();
		
		$WEIBO_CLIENT_CLASS = $this->WEIBO_CLIENT_CLASS;
		$WEIBO_OAUTH_CLASS = $this->WEIBO_OAUTH_CLASS;
		
		//已登录
		if($last_token && $last_token['oauth_token'] && $last_token['oauth_token_secret']){
			$this->weibo_client = new $WEIBO_CLIENT_CLASS($this->config['WB_AKEY'] , $this->config['WB_SKEY'] , $last_token['oauth_token'], $last_token['oauth_token_secret']); 
		}
		
		//登录中
		else if($_GET['WB_CALLBACK'] && $auth_token && $auth_token['oauth_token'] && $auth_token['oauth_token_secret']){
			$this->weibo_obj = new $WEIBO_OAUTH_CLASS($this->config['WB_AKEY'] , $this->config['WB_SKEY'] , $auth_token['oauth_token'], $auth_token['oauth_token_secret']); 
		}
		
		//未登录
		else {
			$this->weibo_obj = new $WEIBO_OAUTH_CLASS($this->config['WB_AKEY'] , $this->config['WB_SKEY']); 
		}
	}
	
	/**
	 * 设置配置
	 * @private
	 * @param array $config
	 */
	private function setConfig(array $config){
		if($config['WB_TYPE'] == 'sina'){
			$this->WEIBO_CLIENT_CLASS = 'WeiboClient';
			$this->WEIBO_OAUTH_CLASS = 'WeiboOAuth';
		}
		
		else if($config['WB_TYPE'] == 'qq'){
			$this->WEIBO_CLIENT_CLASS = 'MBApiClient';
			$this->WEIBO_OAUTH_CLASS = 'MBOpenTOAuth';
		}
		
		else {
			throw new Exception('WEIBO TYPE MUST SPECIFIED!');
		}
		
		$this->WEIBO_AUTH_TOKEN_KEY .= $config['WB_TYPE'];
		$this->WEIBO_LAST_AUTH_TOKEN_KEY .= $config['WB_TYPE'];
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * 获取oAuth Token
	 * @private
	 * @return string
	 **/
	private function getAuthToken(){
		return $_SESSION[$this->WEIBO_AUTH_TOKEN_KEY];
	}
	
	/**
	 * 设置oAuth Token
	 * @private
	 * @param string $oauth_token
	 * @param string $oauth_token_secret
	 * @return array
	 **/
	private function setAuthToken($oauth_token, $oauth_token_secret){
		$_SESSION[$this->WEIBO_AUTH_TOKEN_KEY] = array(
			'oauth_token' => $oauth_token,
			'oauth_token_secret' => $oauth_token_secret
		);
		return $_SESSION[$this->WEIBO_AUTH_TOKEN_KEY];
	}
	
	/**
	 * 清理oAuth session
	 * @private
	 **/
	private function cleanAuthToken(){
		$_SESSION[$this->WEIBO_AUTH_TOKEN_KEY] = null;
	}
	
	/**
	 * 检测是否已经通过oAuth验证
	 * @return boolean
	 **/
	public function checkLoginState(){
		if($_SESSION[$this->WEIBO_LAST_AUTH_TOKEN_KEY]){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 获取登录token
	 * @return string
	 **/
	public function getLoginToken(){
		return $_SESSION[$this->WEIBO_LAST_AUTH_TOKEN_KEY];
	}
	
	/**
	 * 设置登录oAuth token信息到session
	 * @param string $login_token
	 * @param string $login_token_secret
	 * @param string $type
	 **/
	public static function setLoginedAuthToken($login_token, $login_token_secret, $type){
		if(!$login_token || !$login_token_secret){
			throw new Exception('LOGIN TOKEN ERROR:'.$login_token.':::'.$login_token_secret);
		}
		
		$session_key = 'weibo_key_last_'.$type;
		$_SESSION[$session_key]['oauth_token'] = $login_token;
		$_SESSION[$session_key]['oauth_token_secret'] = $login_token_secret;
	}
	
	/**
	 * 设置登录oAuth token信息到session
	 * @param string $login_token
	 * @param string $login_token_secret
	 **/
	public function setLoginToken($login_token, $login_token_secret){
		if(!$login_token || !$login_token_secret){
			throw new Exception('LOGIN TOKEN ERROR:'.$login_token.':::'.$login_token_secret);
		}
		$_SESSION[$this->WEIBO_LAST_AUTH_TOKEN_KEY]['oauth_token'] = $login_token;
		$_SESSION[$this->WEIBO_LAST_AUTH_TOKEN_KEY]['oauth_token_secret'] = $login_token_secret;
	}
	
	/**
	 * 自动微博认证
	 * @deprecate 这里的认证主要利用GET方式进行URL跳转，并利用WB_CALLBACK参数实现不同的action标记
	 * 因此业务在调用这个东西的时候不能有同名参数的干扰
	 */
	public function autoConnect(){
		//回调流程
		if($_GET['WB_CALLBACK']){ 
			$last_key = $this->weibo_obj->getAccessToken($_REQUEST['oauth_verifier']);
			
			$this->setLoginToken($last_key['oauth_token'], $last_key['oauth_token_secret']);
			//$this->cleanAuthToken();
			
			$current_url = $this->getCurrentUrl();
			$current_url = str_replace(array('?WB_CALLBACK=1', '&WB_CALLBACK=1'), '?1=1', $current_url);

			if(IN_DEBUG){
				echo 'last_key:'.$last_key.'<br/>';
				echo '$current_url: <a href="'.$current_url.'">'.$current_url.'</a>';
				exit;
			}
			header('Location:'.$current_url);	//继续正常的业务逻辑
			exit;
		}
		
		//未登录流程
		else if(!$this->checkLoginState()) {
			$callback_url = $this->getCurrentUrl();
			$callback_url .= strstr($callback_url, '?') ? '&WB_CALLBACK=1' : '?WB_CALLBACK=1';
			
			//获取准备认证的token
			$keys = $this->weibo_obj->getRequestToken($callback_url);
			
			$this->setAuthToken($keys['oauth_token'], $keys['oauth_token_secret']);
			$recuse_url = $this->weibo_obj->getAuthorizeURL($keys['oauth_token'], false, '');
			
			if(IN_DEBUG){
				echo '$recuse_url: <a href="'.$recuse_url.'">'.$recuse_url.'</a><br/>';
				echo '$callback_url: '.$callback_url;
				exit;
			}
			
			header('Location:'.$recuse_url);	//进入微博认证回调
			exit;
		} else {
			//已经登录，继续走后面的流程
		}
	}

	/**
	 * 获取当前请求的URL
	 * @deprecate 这里默认为http协议，80端口，GET方式
	 */
	public function getCurrentUrl(){
		return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}
	
	/**
	 * 提供微博接口对象调用魔术方法
	 * @param string $method
	 * @param array $params
	 * @return mix
	 **/
	public function __call($method, $params){
		if(method_exists($this->weibo_client, $method)){
			return call_user_func_array(array($this->weibo_client, $method), $params);
		} else {
			throw new Exception('METHOD NO FOUND IN WEIBO INSTANCE:'.$method);
		}
	}
}
?>