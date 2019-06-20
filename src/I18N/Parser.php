<?php
namespace Lite\I18N;

use function Lite\func\array_clear_empty;
use function Lite\func\explode_by;

/**
 * 国际化语言解析
 */
abstract class Parser{
	const LOCAL_STRUCTURE = [
		'language' => '',
		'script'   => '',
		'region'   => '',
		'variant1' => '',
		'variant2' => '',
		'variant3' => '',
		'private1' => '',
		'private2' => '',
		'private3' => '',
	];

	/**
	 * 解析允许语言清单
	 * @param string $str 格式如：zh-CN,zh-TW;q=0.9,zh;q=0.8,en-US;q=0.7,en;q=0.6,de;q=0.5
	 * @param bool $with_quality 是否返回结果中包含权重
	 * @return array 格式1（包含权重）：[0.5=>[lang1,lang2], ....]，格式2（不包含权重） [lang1, lang2, ...]
	 */
	public static function parseLangAcceptString($str, $with_quality = false){
		$items = array_clear_empty(explode_by(',;', $str));

		//格式：[ [[lang1,lang2], 0.9], [[lang3, lang4], 0.5], ...]
		$data = [];
		foreach($items as $item){
			if(stripos($item, 'q=') !== false){
				$quality = floatval(str_replace('q=', '', $item));
				if($data){
					$data[count($data)-1][1] = $quality;
				}
			} //空数据，或者最后一个已经设置了权重，则追加新item
			else if(!$data || isset($data[count($data)-1][1])){
				$data[] = [[$item]];
			} //最后一个未设置权重，则追加语言
			else{
				$data[count($data)-1][0][] = $item;
			}
		}

		//restructure
		$groups = [];
		foreach($data as list($language_list, $quality)){
			$groups[(string)$quality] = array_merge($language_list, $groups[$quality] ?: []);
		}
		uksort($groups, function($a, $b){
			return $a<=$b;
		});

		if($with_quality){
			return $groups;
		} else{
			$language_list = [];
			foreach($groups as $ls){
				$language_list = array_merge($language_list, $ls);
			}
			return $language_list;
		}
	}

	/**
	 * @param $local_str
	 * @return array
	 */
	public static function parseLocal($local_str){
		$split = explode('-', $local_str);
		if(method_exists('\Locale', 'parseLocale')){
			$ret = \Locale::parseLocale($local_str);
			return self::LOCAL_STRUCTURE+$ret;
		}

		//init
		$language = $script = $region = $variant1 = $variant2 = $variant3 = $private1 = $private2 = $private3 = '';

		array_unshift($split, $language);
		if($split && strlen($split[0])>2){
			array_unshift($split, $script);
		}
		if($split){
			array_unshift($split, $region);
		}
		if($split){
			list($variant1, $variant2, $variant3, $private1, $private2, $private3) = $split;
		}
		return self::LOCAL_STRUCTURE+[
			'language' => $language,
			'script'   => $script,
			'region'   => $region,
			'variant1' => $variant1,
			'variant2' => $variant2,
			'variant3' => $variant3,
			'private1' => $private1,
			'private2' => $private2,
			'private3' => $private3,
		];
	}

	/**
	 * @param $item
	 * @param $haystack
	 * @return bool
	 */
	private static function in_array_case_insensitive($item, $haystack){
		foreach($haystack as $d){
			if(strcasecmp($d, $item) === 0){
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array $accepted
	 * @param array $available
	 * @return array
	 */
	public static function matches(array $accepted, array $available){
		$matches = [];
		foreach($available as $lang){
			if(self::in_array_case_insensitive($lang, $accepted)){
				$matches[] = $lang;
			}
		}
		return $matches;
	}
}