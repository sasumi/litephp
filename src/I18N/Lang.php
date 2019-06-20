<?php
namespace Lite\I18N;

/**
 * Class Lang
 * @package Lite\I18N
 */
class Lang {
	const SESSION_KEY = '_lang_session_key_';
	
	private $language_list = [];
	private $default_language = '';
	private $current_language = '';
	
	private $domain_default_path = '';
	private $domain_paths = [];
//
//	protected $config = [
//		'language_list' => ['zh_CN', 'en_US'],
//		'default_language' => 'zh_CN',
//		'domain_default_path' => '',
//		'domain_paths' => [
//			'default' => '/',
//			'menu' => '/'
//		],
//	];
	
	private function __construct($config){
		$this->config = $config;
	}
	
	/**
	 * @param $config
	 * @return static
	 */
	public static function instance($config){
		static $instance;
		if(!$instance){
			$instance = new static($config);
		}
		return $instance;
	}
	
	public function detectLanguage(){
		if(!headers_sent()){
			session_start();
		}
		return $_SESSION[static::SESSION_KEY] ?: $this->default_language;
	}
	
	/**
	 * 从浏览器发送的HTTP Head中侦测支持语言
	 * @return string
	 */
	public function detectLanguageFromBrowser(){
		return 'zh_CN';
	}
	
	/**
	 * @param $language
	 */
	public function setCurrentLanguage($language){
		$this->current_language = $language;
	}
	
	/**
	 * 绑定语言域文件目录
	 * @param $domain
	 * @param string $path
	 */
	public function bindDomain($domain, $path = ''){
		bindtextdomain($domain, $path);
	}
	
	/**
	 * 设定语言列表
	 * @param $language_list
	 * @param string $default_language
	 */
	public function setLanguageList($language_list, $default_language = ''){
	
	}
}