<?php
namespace Lite\Component;

/**
 * 客户端信息获取类
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
class Client {
	/**
	 * 获取客户端IP
	 * @return string
	 */
	public static function getIp() {
		$ip = '';
		if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')){
			$ip = getenv('HTTP_CLIENT_IP');
		}
		elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		}
		elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$ip = getenv('REMOTE_ADDR');
		}
		elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';
	}

	/**
	 * 解析客户端qua字串
	 * @param $qua_str
	 * @return array|bool
	 */
	public static function parseQua($qua_str) {
		$quaAry = explode('|', $qua_str);
		$queKey = array(
			'iOs',
			'sOsver',
			'sPackage',
			'sAppver',
			'sLang',
			'sTimezone',
			'sResolution',
			'sModel',
			'sImei',
			'sMac',
			'iChannel',
			'iLasttime'
		);
		if(!empty($quaAry) && !empty($queKey)){
			return array_combine($queKey, $quaAry);
		}
		return false;
	}

	/**
	 * 检测是否为网络爬虫
	 * @return bool
	 */
	public static function isCrawler() {
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if(!empty($agent)){
			$spiderSite = array(
				'TencentTraveler',
				'Baiduspider+',
				'BaiduGame',
				'Googlebot',
				'msnbot',
				'Sosospider+',
				'Sogou web spider',
				'ia_archiver',
				'Yahoo! Slurp',
				'YoudaoBot',
				'Yahoo Slurp',
				'MSNBot',
				'Java (Often spam bot)',
				'BaiDuSpider',
				'Voila',
				'Yandex bot',
				'BSpider',
				'twiceler',
				'Sogou Spider',
				'Speedy Spider',
				'Google AdSense',
				'Heritrix',
				'Python-urllib',
				'Alexa (IA Archiver)',
				'Ask',
				'Exabot',
				'Custo',
				'OutfoxBot/YodaoBot',
				'yacy',
				'SurveyBot',
				'legs',
				'lwp-trivial',
				'Nutch',
				'StackRambler',
				'The web archive (IA Archiver)',
				'Perl tool',
				'MJ12bot',
				'Netcraft',
				'MSIECrawler',
				'WGet tools',
				'larbin',
				'Fish search',
				'sitemapx'
			);
			foreach($spiderSite as $val){
				$str = strtolower($val);
				if(strpos($agent, $str) !== false){
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 获取浏览器标识
	 * @return mixed
	 */
	public static function getBrowser() {
		$sys = $_SERVER['HTTP_USER_AGENT'];
		if(stripos($sys, 'NetCaptor') > 0){
			$exp[0] = 'NetCaptor';
			$exp[1] = null;
		}
		elseif(stripos($sys, 'Firefox/') > 0) {
			preg_match('/Firefox\/([^;)]+)+/i', $sys, $b);
			$exp[0] = 'Firefox';
			$exp[1] = $b[1];
		}
		elseif(stripos($sys, 'MAXTHON') > 0) {
			preg_match('/MAXTHON\s+([^;)]+)+/i', $sys, $b);
			preg_match('/MSIE\s+([^;)]+)+/i', $sys, $ie);
			$exp[0] = $b[0] . ' (IE' . $ie[1] . ')';
			$exp[1] = $ie[1];
		}
		elseif(stripos($sys, 'MSIE') > 0) {
			preg_match('/MSIE\s+([^;)]+)+/i', $sys, $ie);
			$exp[0] = 'Internet Explorer';
			$exp[1] = $ie[1];
		}
		elseif(stripos($sys, 'Netscape') > 0) {
			$exp[0] = 'Netscape';
			$exp[1] = null;
		}
		elseif(stripos($sys, 'Opera') > 0) {
			$exp[0] = 'Opera';
			$exp[1] = null;
		}
		elseif(stripos($sys, 'Chrome') > 0) {
			preg_match('/Chrome\/([^;)]+)+/i', $sys, $b);
			$exp[0] = 'Chrome';
			$exp[1] = $b[1];
		}
		else {
			$exp[0] = 'Unknow brower';
			$exp[1] = null;
		}
		return $exp;
	}

	/**
	 * 获取客户端系统标识
	 * @return array
	 */
	public static function getSystem() {
		$sys = $_SERVER['HTTP_USER_AGENT'];
		if(stripos($sys, 'Windows')){
			preg_match('/Windows (.*);/iU', $sys, $b);
			$s = 'Windows';
			$v = $b[1];
		}
		elseif(stripos($sys, 'Mac')) {
			preg_match('/Mac (.*);/iU', $sys, $b);
			$s = 'Mac';
			$v = $b[1];
		}
		elseif(stripos($sys, 'Linux')) {
			preg_match('/Linux (.*);/iU', $sys, $b);
			$s = 'Linux';
			$v = $b[1];
		}
		elseif(stripos($sys, 'Unix')) {
			preg_match('/Unix (.*);/iU', $sys, $b);
			$s = 'Unix';
			$v = $b[1];
		}
		elseif(stripos($sys, 'FreeBSD')) {
			preg_match('/FreeBSD (.*);/iU', $sys, $b);
			$s = 'FreeBSD';
			$v = $b[1];
		}
		elseif(stripos($sys, 'SunOS')) {
			preg_match('/SunOS (.*);/iU', $sys, $b);
			$s = 'SunOS';
			$v = $b[1];
		}
		elseif(stripos($sys, 'BeOS')) {
			preg_match('/BeOS (.*);/iU', $sys, $b);
			$s = 'BeOS';
			$v = $b[1];
		}
		elseif(stripos($sys, 'OS/2')) {
			preg_match('/OS\/2 (.*);/iU', $sys, $b);
			$s = 'OS/2';
			$v = $b[1];
		}
		elseif(stripos($sys, 'PC')) {
			preg_match('/PC (.*);/iU', $sys, $b);
			$s = 'PC';
			$v = $b[1];
		}
		elseif(stripos($sys, 'AIX')) {
			preg_match('/AIX (.*);/iU', $sys, $b);
			$s = 'AIX';
			$v = $b[1];
		}
		else {
			$s = null;
			$v = null;
		}
		return array(
			$s,
			$v
		);
	}
}