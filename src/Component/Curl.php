<?php
namespace Lite\Component;
use Lite\Exception\Exception;

/**
 * HTTP请求基类
 * 提供http(curl)请求方法封装
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
abstract class Curl {
	const DEFAULT_TIMEOUT = 10;

	/**
	 * 合并保留关键字
	 * @return mixed
	 */
	private static function arrayMergeKeepKeys(){
		$arg_list = func_get_args();
		$Zoo = null;
		foreach((array)$arg_list as $arg){
			foreach((array)$arg as $K => $V){
				$Zoo[$K]=$V;
			}
		}
		return $Zoo;
	}

	/**
	 * 获取CURL实例
	 * @param $url
	 * @param array $curl_option
	 * @throws Exception
	 * @return resource
	 */
	private static function getCurlInstance($url, $curl_option=array()){
		if(!$url){
			throw new Exception('CURL URL NEEDED');
		}

		//use ssl
		$ssl = substr($url, 0, 8) == 'https://' ? true : false;

		$opt = array(
			CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],       //在HTTP请求中包含一个"User-Agent: "头的字符串。
			CURLOPT_FOLLOWLOCATION => 1,                            //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
			CURLOPT_RETURNTRANSFER => true,                         //文件流形式
		);

		if($ssl){
			$opt[CURLOPT_SSL_VERIFYPEER] = 0;                       //对认证证书来源的检查
			$opt[CURLOPT_SSL_VERIFYHOST] = 1;                       //从证书中检查SSL加密算法是否存在
		}

		//设置缺省参数
		$curl_option = self::arrayMergeKeepKeys($opt, $curl_option);

		if($curl_option[CURLOPT_TIMEOUT] && $curl_option[CURLOPT_TIMEOUT] > ini_get('max_execution_time')){
			throw new Exception('curl timeout setting larger than php.ini setting');
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);

		$a[CURLOPT_URL]=$url;
		foreach($curl_option as $k=>$val){
			if($k == 'USE_COOKIE'){
				curl_setopt($curl, CURLOPT_COOKIEJAR, $val);    //连接结束后保存cookie信息的文件。
				curl_setopt($curl, CURLOPT_COOKIEFILE, $val);   //包含cookie数据的文件名，cookie文件的格式可以是Netscape格式，或者只是纯HTTP头部信息存入文件。
			} else {
				$a[$k]=$val;
				curl_setopt($curl, $k, $val);
			}
		}
		return $curl;
	}

	/**
	 * CURL-get方式获取数据
	 * @param string $url URL
	 * @param int $timeout 请求时间
	 * @param array $curl_option
	 * @throws Exception
	 * @return bool|mixed
	 */
	public static function get($url, $timeout = self::DEFAULT_TIMEOUT, $curl_option=array()) {
		$opt = array(
			CURLOPT_TIMEOUT => $timeout,
		);

		$curl_option = self::arrayMergeKeepKeys($opt, $curl_option);
		$curl = self::getCurlInstance($url, $curl_option);
		$content = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		if($curl_errno > 0){
			throw new Exception(curl_error($curl));
		}
		curl_close($curl);
		return $content;
	}

	/**
	 * CURL-post方式获取数据
	 * @param string $url URL
	 * @param String $data POST数据
	 * @param int $timeout 请求时间
	 * @param array $curl_option
	 * @throws Exception
	 * @return bool|mixed
	 */
	public static function post($url, $data, $timeout = self::DEFAULT_TIMEOUT, $curl_option=array()) {
		$opt = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_RETURNTRANSFER=> 1
		);

		$curl_option = self::arrayMergeKeepKeys($opt, $curl_option);
		$curl = self::getCurlInstance($url, $curl_option);
		$content = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		$curl_msg=curl_error($curl);
		if($curl_errno > 0){
			throw new Exception($curl_msg);
		}
		curl_close($curl);
		return $content;
	}

	/**
	 * post文件
	 * @param $url
	 * @param array $data
	 * @param array $files
	 * @param int $timeout
	 * @param array $curl_option
	 * @throws \Lite\Exception\Exception
	 * @return mixed
	 */
	public static function postFiles($url, $data=array(), array $files, $timeout = self::DEFAULT_TIMEOUT, $curl_option=array()) {
		$opt = array(
			CURLOPT_POST => true,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_RETURNTRANSFER=> 1
		);

		$curl_option = self::arrayMergeKeepKeys($opt, $curl_option);
		$curl = self::getCurlInstance($url, $curl_option);
		self::setCurlPostFields($curl, $data, $files);

		$content = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		$curl_msg=curl_error($curl);
		if($curl_errno > 0){
			throw new Exception($curl_msg);
		}
		curl_close($curl);
		return $content;
	}

	/**
	 * 多文件post提交 PHP5.3 ~ PHP 5.4.
	 * @param resource $ch cURL resource
	 * @param array $assoc "name => value"
	 * @param array $files "name => path"
	 * @return bool
	 */
	private static function setCurlPostFields($ch, $assoc = array(), array $files = array()) {
		// invalid characters for "name" and "filename"
		static $disallow = array("\0", "\"", "\r", "\n");
		$body = array();

		// build normal parameters
		foreach ($assoc as $k => $v) {
			$k = str_replace($disallow, "_", $k);
			$body[] = implode("\r\n", array(
				"Content-Disposition: form-data; name=\"{$k}\"",
				"",
				filter_var($v),
			));
		}

		// build file parameters
		foreach ($files as $k => $v) {
			switch (true) {
				case false === $v = realpath(filter_var($v)):
				case !is_file($v):
				case !is_readable($v):
					continue; // or return false, throw new InvalidArgumentException
			}
			$data = file_get_contents($v);
			$v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
			$k = str_replace($disallow, "_", $k);
			$v = str_replace($disallow, "_", $v);
			$body[] = implode("\r\n", array(
				"Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
				"Content-Type: application/octet-stream",
				"",
				$data,
			));
		}

		// generate safe boundary
		do {
			$boundary = "---------------------" . md5(mt_rand() . microtime());
		} while (preg_grep("/{$boundary}/", $body));

		// add boundary for each parameters
		array_walk($body, function (&$part) use ($boundary) {
			$part = "--{$boundary}\r\n{$part}";
		});

		// add final boundary
		$body[] = "--{$boundary}--";
		$body[] = "";

		// set options
		return @curl_setopt_array($ch, array(
			CURLOPT_POST       => true,
			CURLOPT_POSTFIELDS => implode("\r\n", $body),
			CURLOPT_HTTPHEADER => array(
				"Expect: 100-continue",
				"Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
			),
		));
	}

	/**
	 * 使用JSON头post数据
	 * @param $url
	 * @param array $data
	 * @param int $timeout
	 * @param array $curl_option
	 * @return bool|mixed
	 */
	public static function postInJSON($url, $data=array(), $timeout = self::DEFAULT_TIMEOUT, $curl_option=array()){
		$data = is_array($data) ? http_build_query($data) : $data;
		$curl_option = self::arrayMergeKeepKeys(array(
			CURLOPT_HTTPHEADER=>array(
				'Content-Type: application/json; charset=utf-8',
				'Content-Length: '.strlen($data)
			)
		), $curl_option);
		return self::post($url, $data, $timeout, $curl_option);
	}

	/**
	 * 使用JSON头get数据
	 * @param $url
	 * @param int $timeout
	 * @param array $curl_option
	 * @return bool|mixed
	 */
	public static function getInJSON($url, $timeout=self::DEFAULT_TIMEOUT, $curl_option=array()){
		$curl_option = self::arrayMergeKeepKeys(array(
			CURLINFO_CONTENT_TYPE => 'application/json'
		), $curl_option);
		return self::get($url, $timeout, $curl_option);
	}

	/**
	 * CURL-put方式获取数据
	 * @param string $url URL
	 * @param array $data POST数据
	 * @param int $timeout 请求时间
	 * @param array $curl_option
	 * @throws Exception
	 * @return bool|mixed
	 */
	public static function put($url, $data, $timeout = self::DEFAULT_TIMEOUT, $curl_option=array()) {
		if($data){
			$data = http_build_query($data);
		}
		$opt = array(
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_HTTPHEADER => array('Content-Length: ' . strlen($data))
		);
		$curl_option = self::arrayMergeKeepKeys($opt, $curl_option);
		$curl = self::getCurlInstance($url, $curl_option);
		$content = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		if($curl_errno > 0){
			throw new Exception($curl_errno);
		}
		curl_close($curl);
		return $content;
	}

	/**
	 * CURL-DEL方式获取数据
	 * @param string $url URL
	 * @param array $data POST数据
	 * @param int $timeout 请求时间
	 * @param array $curl_option
	 * @throws Exception
	 * @return bool|mixed
	 */
	public static function del($url, $data, $timeout = self::DEFAULT_TIMEOUT, $curl_option=array()) {
		if($data){
			$data = http_build_query($data);
		}
		$opt = array(
			CURLOPT_CUSTOMREQUEST => 'DEL',
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_HTTPHEADER => array('Content-Length: ' . strlen($data))
		);
		$curl_option = self::arrayMergeKeepKeys($opt, $curl_option);
		$curl = self::getCurlInstance($url, $curl_option);
		$content = curl_exec($curl);
		$curl_errno = curl_errno($curl);
		if($curl_errno > 0){
			throw new Exception($curl_errno);
		}
		curl_close($curl);
		return $content;
	}
}