<?php

namespace Lite\I18N;

use Lite\Component\Server;
use Lite\Exception\Exception;

/**
 * 国际化多语言支持
 * <pre>使用方法：
 * Lang::addDomain(Lang::DOMAIN_LITEPHP, dirname(__DIR__).'/src/I18N/litephp_lang', ['zh_CN', 'en_US'], 'en_US'); //添加域
 * Lang::setCurrentLanguage('zh_CN'); //设置当前语言
 * Lang::setCurrentDomain(Lang::DOMAIN_LITEPHP); //设置当前域，如果没有设置，可以在getText中直接指定
 * //以上步骤可以任意顺序
 * echo Lang::getTextSoft('hello world', [], Lang::DOMAIN_LITEPHP); //翻译
 * </pre>
 * @package Lite\I18N
 */
abstract class Lang {
	//框架指定域
	const DOMAIN_LITEPHP = 'litephp';

	//已绑定域列表
	//[domain => [lang_list, default_lang], ...]
	private static $domain_list = [];

	/**
	 * 设置当前域
	 * @param string $domain
	 */
	public static function setCurrentDomain($domain){
		textdomain($domain);
	}

	/**
	 * 获取当前域
	 * @return string
	 */
	public static function getCurrentDomain(){
		return textdomain(null);
	}

	/**
	 * 获取当前设置语言
	 * @param int $category 类目
	 * @return string
	 */
	public static function getCurrentLanguage($category = LC_ALL){
		$lang = setlocale($category, 0);
		if(Server::inWindows()){
			return str_replace('-', '_', $lang);
		}
		return $lang;
	}

	/**
	 * 设置当前环境语言
	 * @param string $language 语言名称，必须在support_language_list里面
	 * @param int $category 类目，缺省为所有类目：LC_ALL
	 * @param bool $force_check_all_domain_support 是否强制检查所有域必须支持
	 * @return string
	 */
	public static function setCurrentLanguage($language, $category = LC_ALL, $force_check_all_domain_support = false){
		if(Server::inWindows()){
			return self::setCurrentLanguageInWindows($language, $category);
		}

		$force_check_all_domain_support && self::checkLanguageSupportAll($language);

		//try difference language case ...
		$locale_set = setlocale($category, $language.'.utf8', $language.'.UTF8', $language.'.utf-8', $language.'.UTF-8');
		if($language && $locale_set != $language){
			throw new Exception(sprintf('Language set %s failure:%s, return:%s', $category, $language, $locale_set));
		}
		return $locale_set;
	}

	/**
	 * 设置Windows环境语言
	 * @param string $language
	 * @param int $category
	 * @param bool $force_check_all_domain_support 是否强制检查所有域必须支持
	 * @return string
	 */
	public static function setCurrentLanguageInWindows($language, $category = LC_ALL, $force_check_all_domain_support = false){
		$force_check_all_domain_support && self::checkLanguageSupportAll($language);

		$language = str_replace('_', '-', $language);

		static $win_lang_list;
		if(!$win_lang_list){
			$win_lang_list = include __DIR__.'/assert/windows.lang.php';
			$win_lang_list = array_map('strtolower', $win_lang_list);
		}

		if(!in_array(strtolower($language), $win_lang_list)){
			throw new Exception('Language no support in windows');
		}

		if(false == putenv("LANGUAGE=".$language)){
			throw new Exception(sprintf("Could not set the ENV variable LANGUAGE = $language"));
		}

		// set the LANG environmental variable
		if(false == putenv("LANG=".$language)){
			throw new Exception(sprintf("Could not set the ENV variable LANG = $language"));
		}

		//try difference language case ...
		$locale_set = setlocale($category, $language);
		if($language && $locale_set != $language){
			throw new Exception(sprintf('Language set %s failure:%s, return:%s', $category, $language, $locale_set));
		}
		return $locale_set;
	}

	/**
	 * 检查语言是否被所有域支持
	 * @param string $language
	 */
	public static function checkLanguageSupportAll($language){
		foreach(self::$domain_list as $domain => list($language_list)){
			if(!in_array($language, $language_list)){
				throw new Exception('Language no support in domain:'.$domain);
			}
		}
	}

	/**
	 * 获取绑定的所有域的支持的语言列表
	 * @return array
	 */
	private static function getAllLanguageList(){
		$ls = [];
		foreach(self::$domain_list as $domain => list($language_list)){
			$ls += $language_list;
		}
		return array_unique($ls);
	}

	/**
	 * 从浏览器发送的HTTP Header中侦测支持语言
	 * @param array $available_language_list 支持语言列表，缺省使用当前设置支持语言列表
	 * @return array language list
	 */
	public static function detectLanguageListFromBrowser($available_language_list = []){
		$accepted = Parser::parseLangAcceptString($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		return Parser::matches($accepted, $available_language_list ?: self::getAllLanguageList());
	}

	/**
	 * 添加域
	 * @param string $domain 域
	 * @param string $path 翻译件路径
	 * @param array $support_language_list 支持语言列表
	 * @param string $default_language 缺省支持语言，默认为支持语言列表第一个。当当前域不支持设定语言时，可使用缺省语言显示
	 * @param string $codeset 编码，缺省为UTF-8（windows暂不支持设定）
	 */
	public static function addDomain($domain, $path, array $support_language_list, $default_language = '', $codeset = 'UTF-8'){
		if(!bindtextdomain($domain, $path)){
			throw new Exception("Bind text domain fail, domain:$domain, path:$path");
		}
		if($codeset){
			bind_textdomain_codeset($domain, $codeset);
		}
		if(!$default_language){
			$default_language = current($support_language_list);
		}
		self::$domain_list[$domain] = [$support_language_list, $default_language];
	}

	/**
	 * 翻译，如果当前域不支持当前语言，则使用缺省语言
	 * @param string $text
	 * @param array $param
	 * @param string $domain
	 * @return string
	 */
	public static function getTextSoft($text, $param, $domain = ''){
		$current_language = self::getCurrentLanguage();
		$domain = $domain ?: self::getCurrentDomain();
		if(!in_array($current_language, self::$domain_list[$domain][0])){
			$current_language = self::$domain_list[$domain][1];
			return self::getTextInLanguageTemporary($text, $param, $current_language, $domain);
		}
		return self::getText($text, $param, $domain);
	}

	/**
	 * 临时以指定语种翻译翻译
	 * @param $text
	 * @param array $param
	 * @param string $language
	 * @param string $domain
	 * @return string
	 */
	public static function getTextInLanguageTemporary($text, $param, $language, $domain = ''){
		$old_language = self::getCurrentLanguage();
		self::setCurrentLanguage($language);
		$text = self::getText($text, $param, $domain);
		self::setCurrentLanguage($old_language);
		return $text;
	}

	/**
	 * 翻译
	 * @param $text
	 * @param array $param 变量参数，格式如：{var}，{obj.pro.key}
	 * @param string $domain 域，缺省为当前设定域
	 * @return string
	 */
	public static function getText($text, $param = [], $domain = ''){
		$text = $domain ? dgettext($domain, $text) : gettext($text);
		if(!$param){
			return $text;
		}

		extract($param, EXTR_OVERWRITE);
		$tmp = '';
		$text = preg_replace('/"/', '\\"', $text);
		$str = preg_replace_callback('/\{([^}]+)\}/', function($matches){
			$vs = explode('.', $matches[1]);
			list($vars) = $vs;
			if(count($vs) > 1){
				for($i = 1; $i < count($vs); $i++){
					$vars .= "['".$vs[$i]."']";
				}
			}
			return '{$'.$vars.'}';
		}, $text);
		$str = "\$tmp = \"$str\";";
		eval($str);
		return $tmp;
	}
}