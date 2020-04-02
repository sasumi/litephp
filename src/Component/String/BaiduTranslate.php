<?php
namespace Lite\Component\String;

use LFPhp\Cache\CacheFile;
use Lite\Component\Net\Curl;
use const LFPhp\Func\ONE_DAY;

/**
 * Class BaiduTranslate
 * @package Lite\Component\String
 */
abstract class BaiduTranslate{
	/**
	 * 翻译
	 * @param $text
	 * @param string $from
	 * @param string $to
	 * @return mixed|string
	 */
	public static function translate($text, $from = 'zh', $to = 'en'){
		$cache_key = __CLASS__.__METHOD__.join(',', func_get_args());

		return CacheFile::instance()->cache($cache_key, function() use ($text, $from, $to){
			$response = Curl::post('https://fanyi.baidu.com/basetrans', [
				'query' => $text,
				'from'  => $from,
				'to'    => $to,
			], [CURLOPT_USERAGENT => 'Android']);

			$str = '';
			foreach(json_decode($response)->trans as $v){
				$str .= $v->dst;
			}
			$str = trim($str);
			$str = str_replace('。', '', $str);
			return $str;
		}, ONE_DAY*10);
	}
}
