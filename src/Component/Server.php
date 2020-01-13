<?php
namespace Lite\Component;
use function Lite\func\format_size;
use function Lite\func\resolve_size;

/**
 * 服务器环境集成类
 * User: sasumi
 */
class Server{
	public static function inWindows(){
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	/**
	 * 服务器最大上传文件大小
	 * 通过对比文件上传限制与post大小获取
	 * @param bool $human_readable 是否以可读方式返回
	 * @return int
	 */
	public static function getUploadMaxSize($human_readable = false){
		$upload_sz = trim(ini_get('upload_max_filesize'));
		$upload_sz = resolve_size($upload_sz);
		$post_sz = trim(ini_get('post_max_size'));
		$post_sz = resolve_size($post_sz);
		$ret = min($upload_sz, $post_sz);
		if($human_readable){
			return format_size($ret);
		}
		return $ret;
	}

	/**
	 * get php core summary released info
	 * @return string
	 */
	public static function getPhpReleaseSummary(){
		$info = self::getPhpInfo();
		$ts = $info['phpinfo']['Thread Safety'] == 'enabled' ? 'ts' : 'nts';
		$compiler = $info['phpinfo']['Compiler'];
		if(preg_match('/ms(vc\d+)\s/i', $compiler, $matches)){
			$compiler = strtolower($matches[1]);
		}
		$ver = phpversion();
		return join('-', [
			$ver,
			$ts,
			$compiler,
			$info['phpinfo']['Architecture'],
		]);
	}

	/**
	 * get phpinfo() as array
	 * @return array
	 */
	public static function getPhpInfo(){
		static $phpinfo;
		if($phpinfo){
			return $phpinfo;
		}

		$entitiesToUtf8 = function($input){
			return preg_replace_callback("/(&#[0-9]+;)/", function($m){
				return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
			}, $input);
		};
		$plainText = function($input) use ($entitiesToUtf8){
			return trim(html_entity_decode($entitiesToUtf8(strip_tags($input))));
		};
		$titlePlainText = function($input) use ($plainText){
			return '# '.$plainText($input);
		};

		ob_start();
		phpinfo(-1);

		$phpinfo = array('phpinfo' => array());

		// Strip everything after the <h1>Configuration</h1> tag (other h1's)
		if(!preg_match('#(.*<h1[^>]*>\s*Configuration.*)<h1#s', ob_get_clean(), $matches)){
			return array();
		}

		$input = $matches[1];
		$matches = array();

		if(preg_match_all('#(?:<h2.*?>(?:<a.*?>)?(.*?)(?:<\/a>)?<\/h2>)|'.'(?:<tr.*?><t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>)?)?</tr>)#s', $input, $matches, PREG_SET_ORDER)){
			foreach($matches as $match){
				$fn = strpos($match[0], '<th') === false ? $plainText : $titlePlainText;
				if(strlen($match[1])){
					$phpinfo[$match[1]] = array();
				}elseif(isset($match[3])){
					$keys1 = array_keys($phpinfo);
					$phpinfo[end($keys1)][$fn($match[2])] = isset($match[4]) ? array(
						$fn($match[3]),
						$fn($match[4]),
					) : $fn($match[3]);
				}else{
					$keys1 = array_keys($phpinfo);
					$phpinfo[end($keys1)][] = $fn($match[2]);
				}

			}
		}
		return $phpinfo;
	}
}
