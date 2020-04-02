<?php
namespace Lite\I18N;

use function LFPhp\Func\array_clear_empty;
use function LFPhp\Func\explode_by;

/**
 * 国际化语言解析
 * 如果已经安装扩展intl，则使用\Locale::parseLocale()函数匹配，
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
			/** @var array $language_list */
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
			return self::LOCAL_STRUCTURE + $ret;
		}

		//init
		$language = $script = $region = $variant1 = $variant2 = $variant3 = $private1 = $private2 = $private3 = '';

		array_unshift($split, $language);
		if($split && strlen($split[0]) > 2){
			array_unshift($split, $script);
		}
		if($split){
			array_unshift($split, $region);
		}
		if($split){
			list($variant1, $variant2, $variant3, $private1, $private2, $private3) = $split;
		}
		return self::LOCAL_STRUCTURE + [
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
	 * 语言定义相似度计算
	 * 全等：4，地区：2，脚本：1，其他
	 * @param $a
	 * @param $b
	 * @return float 得分
	 */
	private static function cmpAndCal($a, $b){
		if($a['language'] != $b['language']){
			return 0;
		}
		if(!array_diff($a, $b)){
			return 4;
		}
		$p = 0;
		$p += strcasecmp($a['region'], $b['region']) === 0 ? 2 : 0;
		$p += strcasecmp($a['script'], $b['script']) === 0 ? 1 : 0;
		unset($a['language'], $a['region'], $a['script']);
		foreach($a as $field=>$val){
			if($val && strcasecmp($val, $b[$field]) === 0){
				$p += 0.1;
			}
		}
		return $p;
	}
	
	/**
	 * 语言相似度匹对
	 * @param array $accepted
	 * @param array $available
	 * @param bool $with_priority 是否返回相似度
	 * @return array 结果格式：['zh-CN'=>8, 'de'=>4.5, ...]
	 */
	public static function matches(array $accepted, array $available, $with_priority = false){
		//matches format ['zh-CN'=>8, 'de'=>4.5, ...]
		$matches = [];

		$parser = 'self::parseLocal';

		$av_list = array_map($parser, $available);
		$acc_list = array_map($parser, $accepted);

		foreach($av_list as $av){
			foreach($acc_list as $acc){
				if($score = self::cmpAndCal($av, $acc)){
					$matches[$av['language']] = $score;
				}
			}
		}
		
		sort($matches, SORT_DESC);
		
		if(!$with_priority){
			return array_keys($matches);
		}
		return $matches;
	}
}
