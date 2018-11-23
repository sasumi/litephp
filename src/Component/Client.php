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
		return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : $ip;
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
	 * 判断是不是手机访问
	 * @return bool
	 */
	public static function isMobile(){
		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
		if(isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
			return true;
		}
		// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		if(isset ($_SERVER['HTTP_VIA'])){
			// 找不到为false,否则为true
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		// 脑残法，判断手机发送的客户端标志,兼容性有待提高
		if(isset ($_SERVER['HTTP_USER_AGENT'])){
			$client_keywords = array('nokia',
				'sony',
				'ericsson',
				'mot',
				'samsung',
				'htc',
				'sgh',
				'lg',
				'sharp',
				'sie-',
				'philips',
				'panasonic',
				'alcatel',
				'lenovo',
				'iphone',
				'ipod',
				'blackberry',
				'meizu',
				'android',
				'netfront',
				'symbian',
				'ucweb',
				'windowsce',
				'palm',
				'operamini',
				'operamobi',
				'openwave',
				'nexusone',
				'cldc',
				'midp',
				'wap',
				'mobile'
			);
			// 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if(preg_match("/(" . implode('|', $client_keywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
				return true;
			}
		}
		// 协议法，因为有可能不准确，放到最后判断
		if(isset ($_SERVER['HTTP_ACCEPT'])){
			// 如果只支持wml并且不支持html那一定是移动设备
			// 如果支持wml和html但是wml在html之前则是移动设备
			if((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
				return true;
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

	public static function inCli(){
		return PHP_SAPI == 'cli';
	}

	/**
	 * check script in windows
	 * @return bool
	 */
	public static function inWindows(){
		if(self::inCli()){
			return stripos($_SERVER['OS'], 'windows') !== false;
		}
		list($os) = self::getSystem();
		return $os == 'Windows';
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