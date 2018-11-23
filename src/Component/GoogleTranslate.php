<?php
namespace Lite\Component;

/**
 * 谷歌（中国）翻译
 * 注意，谷歌翻译接口有防恶意调用功能，同一IP长时间调用可能会使该IP被列入黑名单。
 * User: sasumi
 * Date: 2016/6/21
 * Time: 9:10
 */
abstract class GoogleTranslate{
	public static $debug = false;
	public static $timeout = 10;
	const LANG_AUTO = 'auto';
	const LANG_ZH_CN = 'zh-CN';
	const LANG_EN = 'en';
	const LANG_DE = 'de';
	
	private static function __log(){
		if(!self::$debug){
			return;
		}
		$str = join("\t", func_get_args());
		echo date('Y-m-d H:i:s')." ".$str."\n";
	}
	
	/** @var array $cache Query cache */
	private static $cache = [];
	
	/**
	 * @param $html
	 * @param array $keep_tags
	 * @param $to_lang
	 * @param null $from_lang
	 * @todo building
	 */
	public static function translateHtml($html, $keep_tags = [], $to_lang, $from_lang = null){
	
	}
	
	/**
	 * @param $text
	 * @param $to_lang
	 * @param $from_lang
	 * @return mixed
	 * @throws \Exception
	 */
	public static function translateText($text, $from_lang = self::LANG_AUTO, $to_lang = self::LANG_ZH_CN, $force = false){
		$fields = array(
			'sl' => $from_lang,
			'tl' => $to_lang,
			'q'  => $text
		);
		
		$u_key = md5(json_encode($fields));
		
		if(!$force && self::$cache[$u_key]){
			self::__log('Translate mem cache hits', $text, self::$cache[$u_key]);
			return self::$cache[$u_key];
		}
		
		if(strlen($fields['q'])>=5000){
			throw new \Exception("Maximum number of characters exceeded: 5000");
		}
		
		// Google translate URL
		$url = "https://translate.google.cn/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";
		
		// Open connection
		self::__log('Start translation', $text, $url, var_export($fields, true));
		$ch = curl_init();
		
		// Set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
		
		// Execute post
		$result = curl_exec($ch);
		self::__log('Curl result:', $result);
		
		// Close connection
		curl_close($ch);
		
		//parse sentences
		$text = self::getSentencesFromJSON($result);
		self::__log('Translate result:', $text);
		
		//save to cache
		self::$cache[$u_key] = $text;
		return $text;
	}
	
	/**
	 * Dump of the JSON's response in an array
	 * @param string $json The JSON object returned by the request function
	 * @return string A single string with the translation
	 */
	private static function getSentencesFromJSON($json){
		$sentencesArray = json_decode($json, true);
		$sentences = "";
		foreach($sentencesArray["sentences"] ?: [] as $s){
			$sentences .= isset($s["trans"]) ? $s["trans"] : '';
		}
		return $sentences;
	}
}