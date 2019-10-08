<?php
namespace Lite\I18N;

use Lite\Component\Server;
use Lite\Exception\Exception;

/**
 * 国际化多语言支持
 * @package Lite\I18N
 */
abstract class Lang{
	const DOMAIN_DEFAULT = 'default';
	const DOMAIN_LITEPHP = 'litephp';

	private static $support_language_list = [];
	private static $default_language = '';

	/**
	 * @return array
	 */
	public static function getSupportLanguageList(){
		return self::$support_language_list;
	}

	/**
	 * 设置支持语言列表
	 * @param array $support_language_list
	 * @param null $default_language 默认语言，缺省使用语言列表第一个
	 * @throws \Lite\Exception\Exception
	 */
	public static function setSupportLanguageList($support_language_list, $default_language = null){
		self::$support_language_list = $support_language_list;
		if($default_language){
			self::setDefaultLanguage($default_language);
		}else if(!self::$default_language){
			self::setDefaultLanguage(current(self::$support_language_list));
		}
	}

	/**
	 * 获取默认语言，缺省为语言清单的第一个
	 * @return string
	 */
	public static function getDefaultLanguage(){
		return self::$default_language;
	}

	/**
	 * 设置默认语言
	 * @param string $default_language
	 */
	public static function setDefaultLanguage($default_language){
		if(!in_array($default_language, self::$support_language_list)){
			throw new Exception('Default language must exists in support language list');
		}
		self::$default_language = $default_language;
	}

	/**
	 * 获取当前设置语言
	 * @param int $category 类目
	 * @return string
	 */
	public static function getCurrentLanguage($category = LC_ALL){
		return setlocale($category, 0);
	}

	/**
	 * 设置当前环境语言
	 * @param string $language 语言名称，必须在support_language_list里面
	 * @param int $category 类目，缺省为所有类目：LC_ALL
	 * @return string
	 */
	public static function setCurrentLanguage($language, $category = LC_ALL){
		if(!in_array($language, self::$support_language_list)){
			throw new Exception('Current language must exists in language list:'.$language);
		}

		if(Server::inWindows()){
			return self::setCurrentLanguageInWindows($language, $category);
		}

		//try difference language case ...
		$locale_set = setlocale($category, $language.'.utf8', $language.'.UTF8', $language.'.utf-8', $language.'.UTF-8');
		if($language && $locale_set != $language){
			throw new Exception(sprintf('Language set %s failure:%s, return:%s', $category, $language, $locale_set));
		}
		return $locale_set;
	}

	/**
	 * 设置Windows环境语言
	 * @param $language
	 * @param int $category
	 * @return string
	 * @throws \Lite\Exception\Exception
	 */
	public static function setCurrentLanguageInWindows($language, $category = LC_ALL){
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
	 * 从浏览器发送的HTTP Header中侦测支持语言
	 * @param array $available_language_list 支持语言列表，缺省使用当前设置支持语言列表
	 * @return array language list
	 */
	public static function detectLanguageListFromBrowser($available_language_list = []){
		$accepted = Parser::parseLangAcceptString($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		return Parser::matches($accepted, $available_language_list ?: self::$support_language_list);
	}

	/**
	 * 绑定语言域文件目录
	 * @param string $domain 域
	 * @param string $path 路径
	 * @param string $codeset 字符集
	 * @param bool $as_default 是否作为默认域
	 */
	public static function bindDomain($domain, $path, $codeset = 'UTF-8', $as_default = false){
		if($as_default){
			textdomain($domain);
		}
		if(!bindtextdomain($domain, $path)){
			throw new Exception("Bind text domain fail, domain:$domain, path:$path");
		}
		if($codeset){
			bind_textdomain_codeset($domain, $codeset);
		}
	}

	/**
	 * 翻译
	 * @param $text
	 * @param array $param 变量参数，格式如：{var}，{obj.pro.key}
	 * @param string $domain
	 * @return string
	 */
	public static function getText($text, $param = [], $domain = self::DOMAIN_DEFAULT){
		if(!$param){
			return dgettext($domain, $text);
		}
		$text = dgettext($domain, $text);
		extract($param, EXTR_OVERWRITE);
		$tmp = '';
		$text = preg_replace('/"/', '\\"', $text);
		$str = preg_replace_callback('/\{([^}]+)\}/', function($matches){
			$vs = explode('.', $matches[1]);
			list($vars) = $vs;
			if(count($vs)>1){
				for($i = 1; $i<count($vs); $i++){
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