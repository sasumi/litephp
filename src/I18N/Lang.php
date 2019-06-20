<?php

namespace Lite\I18N;

use function Lite\func\t;

/**
 * 国际化多语言支持
 * @package Lite\I18N
 */
class Lang{
	const SESSION_KEY = '_lang_session_key_';
	
	private $language_list = [];
	private $default_language = '';
	private $current_language = '';
	
	/**
	 * Force singleton
	 */
	private function __construct(){}
	
	/**
	 * 翻译
	 * @param $message
	 * @param array $param
	 * @param string $domain
	 * @return string
	 */
	public function getText($message, $param = [], $domain = ''){
		return t($message, $param, $domain);
	}
	
	/**
	 * 单例
	 * @return static
	 */
	public static function instance(){
		static $instance;
		if(!$instance){
			$instance = new static();
		}
		return $instance;
	}
	
	/**
	 * 检测当前语言
	 * @return string
	 */
	public function detectedLanguage(){
		if(!headers_sent()){
			session_start();
		}
		return $_SESSION[static::SESSION_KEY] ?: $this->default_language ?: self::detectLanguageFromBrowser();
	}
	
	/**
	 * 设置当前环境语言
	 * @param string $language 语言名称，默认自动检测（从SESSION或HTTP Head中检测）
	 */
	public function setCurrentLanguage($language = ''){
		$this->current_language = $language ?: self::detectedLanguage();
		setlocale(LC_ALL, $this->current_language);
	}
	
	/**
	 * 从浏览器发送的HTTP Header中侦测支持语言
	 * @return string
	 */
	public function detectLanguageFromBrowser(){
		$accepted = Parser::parseLangAcceptString($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$matches = Parser::matches($accepted, $this->language_list);
		return array_keys($matches);
	}
	
	/**
	 * 绑定语言域文件目录
	 * @param $domain
	 * @param string $path
	 * @param string $as_default
	 */
	public function bindDomain($domain, $path = '', $as_default = ''){
		if($as_default){
			textdomain($domain);
		}
		bindtextdomain($domain, $path);
	}
	
	/**
	 * 设定语言列表
	 * @param $language_list
	 * @param string $default_language
	 */
	public function setLanguageList($language_list, $default_language = ''){
		$this->language_list = $language_list;
		$this->default_language = $default_language;
	}
}