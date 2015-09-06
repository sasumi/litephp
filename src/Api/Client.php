<?php
namespace Lite\Api;

use Lite\Cache\Helper;
use Lite\Component\Client as ComClient;
use Lite\Component\Request;
use Lite\Core\Result;
use Lite\Exception\Exception;

/**
 * API Client
 */
abstract class Client {
	public static $start_debug = false; //调试代码
	public static $_REQ_DEDUMPLICATE = true;        //请求去重

	private $_cache_time;                           //cache时间
	private $_cache_on_error;                //服务器返回错误时，还继续进行cache
	private $_var_cache_data = array();
	private $disaster_protect;                      //容灾保护
	public static $DISASTER_PROTECT_TIME = 864000;  //容灾时间

	public static $REPORT_CGI_SPEED_SEC = 2;        //超时上报时间（秒)
	public static $REPORT_CGI_SPEED_STATIC = true;  //上报CGI测速

	const RET_JSON = 0x002;
	const RET_NORMAL = 0x003;
	const RET_SSO = 0x007;
	const REQ_JSON = 0x004;
	const REQ_NORMAL = 0x005;
	const REQ_SSO = 0x006;

	/**
	 * 请求配置信息
	 * @var array
	 **/
	private $request_config = array(
		'timeout' => 20,
		'method' => 'post',
		'gzip' => true,
		'request_format' => self::REQ_NORMAL,
		'response_format' => self::RET_JSON
	);

	/**
	 * 请求之前触发事件
	 * 可覆盖
	 * @param $host_url
	 * @param $param
	 */
	public function onBeforeRequest($host_url, $param) {
	}

	/**
	 * 请求之后触发事件
	 * 可覆盖
	 * @param $host_url
	 * @param $param
	 * @param $result
	 */
	public function onAfterRequest($host_url, $param, $result) {
	}

	/**
	 * 合并请求参数
	 * 可覆盖
	 * @param $host_url
	 * @param $param
	 * @return array
	 */
	protected function buildRequestParam($host_url, $param) {
		return array(
			'url' => $host_url,
			'data' => $param,
			'method' => $this->request_config['method'],
		);
	}

	/**
	 * 解析返回结果
	 * 可覆盖
	 * @param $rsp
	 * @return mixed
	 */
	protected function parserResponse($rsp) {
//		$result = new Result();
//		switch($this->request_config['response_format']) {
//			//标准json返回
//			case self::RET_JSON:
//				$result->parserJson($rsp);
//				break;
//
//			//字符串返回
//			case self::RET_NORMAL:
//			default:
//				$result->setData($rsp);
//				$result->setCode(0);
//				break;
//		}
//		return $result;
	}

	/**
	 * 设置请求参数配置信息
	 * @param array $config
	 */
	final protected function setRequestConfig(array $config) {
		$this->request_config = array_merge($this->request_config, $config);
	}

	/**
	 * 获取请求参数配置
	 * @param string $key
	 * @return array
	 */
	final protected function getRequestConfig($key=''){
		if($key){
			return $this->request_config[$key];
		}
		return $this->request_config;
	}

	/**
	 * 设置文件cache
	 * @param integer $time cache时间（秒）
	 * @param bool $disaster_protect 是否启用容灾保护
	 * @param bool $cache_on_error
	 * @return $this
	 */
	final public function cache($time, $disaster_protect = true, $cache_on_error=false){
		$this->_cache_time = (int)$time;
		$this->disaster_protect = $disaster_protect;
		$this->_cache_on_error = $cache_on_error;
		return $this;
	}

	final public function getCacheTime(){
		return $this->_cache_time;
	}

	final public function hasDisasterProtect(){
		return $this->disaster_protect;
	}

	/**
	 * 获取内存cache内容
	 * @param $req
	 * @return null
	 */
	private function __getVarCacheData($req){
		$cache_key = $this->__genCacheKey($req);
		if(isset($this->_var_cache_data[$cache_key])){
			return $this->_var_cache_data[$cache_key];
		}
		return null;
	}

	/**
	 * 设置内存cache内容
	 * @param $req
	 * @param $data
	 */
	private function __setVarCacheData($req, $data){
		$cache_key = $this->__genCacheKey($req);
		$this->_var_cache_data[$cache_key] = $data;
	}

	/**
	 * 获取cache key
	 * @param $req
	 * @return string
	 */
	private function __genCacheKey($req){
		return '_taohai_library_'.md5(serialize($req));
	}

	/**
	 * 设置容灾数据
	 * @param $req
	 * @param $data
	 */
	private function __setDisasterProtectData($req, $data){
		$key = '_disaster_protect'.$this->__genCacheKey($req);
		Helper::init()->set($key, $data, 86400*2);   //容灾两天
	}

	/**
	 * 获取容灾数据
	 * @param $req
	 * @return mixed
	 */
	private function __getDisasterProtectData($req){
		$key = '_disaster_protect'.$this->__genCacheKey($req);
		Helper::init()->get($key);
	}

	/**
	 * 设置memcache
	 * @param $req
	 * @param $data
	 * @return mixed
	 */
	private function __setMemCacheData($req, $data){
		$key = $this->__genCacheKey($req);
		return Helper::init()->set($key, $data, $this->_cache_time);
	}

	/**
	 * 获取memcache数据
	 * @param $req
	 * @return mixed
	 */
	private function __getMemCacheData($req){
		$key = $this->__genCacheKey($req);
		return Helper::init()->get($key);
	}

	/**
	 * 上报库存
	 * @param string $cgi_url 请求接口地址
	 * @param mixed $param 返回结果
	 * @param $error_code
	 * @param $error_message
	 * @internal param \运行多长时间 $runtime
	 */
	public static function reportCgiRetCode($cgi_url, $param='', $error_code=1, $error_message=''){
		$data = array(
			'url' => $cgi_url,
			'params' => is_array($param) ? json_encode($param) : $param,
			'error_code' => $error_code,
			'error_message' => $error_message,
			'platform' => 3, //pc
			'ip' => ComClient::getIp(),
			'create_time' => date('Y-m-d H:i:s'),
		);
		$url = 'http://htadmin.hai0.com/order/api/index.php/log.cgiret';
		try {
			Request::post($url, $data, 2);
		} catch (Exception $ex){
		}
	}

	/**
	 * 上报cgi测试统计
	 * 只有超时超过设定值才会上报cgi超时
	 * @param $cgi_url
	 * @param $param
	 * @param $msec
	 * @internal param $url
	 */
	private function reportCgiSpeedStatic($cgi_url, $param, $msec){
		$off = (microtime(true)-$msec);
		if(!self::$REPORT_CGI_SPEED_STATIC || $off < self::$REPORT_CGI_SPEED_SEC){
			return;
		}
		$data = array(
			'url' => $cgi_url,
			'params' => $param ? json_encode($param) : '',
			'run_time' => number_format($off, 3),
			'app_id' => 4,  //hai360_web
			'platform' => 3, //pc
			'ip' => ComClient::getIp(),
		);
		$url = 'http://htadmin.hai0.com/order/api/index.php/log.cgispd';
		try {
			Request::post($url, $data, 2);
		} catch (Exception $ex){

		}
	}

	/**
	 * 请求调用curl接口
	 * @param $url
	 * @param $param
	 * @return Result
	 */
	public function request($url, $param) {
		if($this->onBeforeRequest($url, $param) === false){
			return false;
		}

		//构建请求数据
		$req = $this->buildRequestParam($url, $param);

		//防重复调用cache检测
		$result = null;
		if(self::$_REQ_DEDUMPLICATE){
			$result = $this->__getVarCacheData($req);
		}

		//memcache检测
		if(!$result && $this->_cache_time){
			$result = $this->__getMemcacheData($req);
		}

		if(($this->_cache_on_error && !isset($result)) || (!$this->_cache_on_error && !$result)){
			//调试代码
			$debug_file = dirname(__DIR__).DIRECTORY_SEPARATOR. 'request.log';
			if(self::$start_debug){
				self::$start_debug = false;
				@unlink($debug_file);
				$fp = fopen($debug_file, 'a+');
				fwrite($fp, '');
				fclose($fp);
			}
			if(file_exists($debug_file)){
				$content = "\n------------------------------\n".
					"请求: $url\n";
				$content .= "参数:\n" . print_r($param, true) . "\n";
				$fp = fopen($debug_file, 'a+');
				fwrite($fp, $content);
				fclose($fp);
			}

			//测速
			$time_mark = microtime(true);
			try {
				switch(strtolower($req['method'])) {
					case 'get':
						if($req['data']){
							$data = is_array($req['data']) ? http_build_query($req['data']) : $req['data'];
							$url = $req['url'] . (stripos($req['url'], '?') ? '&' : '?') . $data;
						}
						$result = Request::get($req['url'], $this->request_config['timeout']);
						break;

					case 'json':
						$result = Request::postInJSON($req['url'], $req['data'],  $this->request_config['timeout']);
						break;

					case 'post':
					default:
						if($this->request_config['request_format'] == self::REQ_JSON){
							$result = Request::postInJSON($req['url'], $req['data'], $this->request_config['timeout']);
						} else {
							$result = Request::post($req['url'], $req['data'], $this->request_config['timeout']);
						}
				}
			} catch(Exception $ex){
				$this->reportCgiSpeedStatic($url, $req['data'], $time_mark);
				if(!$this->disaster_protect){
					return new Result('服务器繁忙，请稍后重试');
				}
			}

			//测速
			$this->reportCgiSpeedStatic($url, $req['data'], $time_mark);

			//设置容灾数据
			if($result && $this->disaster_protect){
				$this->__setDisasterProtectData($req, $result);
			}

			//进程cache
			if(self::$_REQ_DEDUMPLICATE && $result){
				$this->__setVarCacheData($req, $result);
			}

			//memcache
			if($this->_cache_time){
				$this->__setMemcacheData($req, $result);
			}

			//读取容灾数据
			if(!$result && $this->disaster_protect){
				$result = $this->__getDisasterProtectData($req);
				$this->reportCgiRetCode($req['url'], $req['data'], $error_code=110, $error_message='容灾生效');
			}

			//调试代码
			if(file_exists($debug_file)){
				$content = "响应结果: \n";
				$content .= $result;
				$fp = fopen($debug_file, 'a+');
				fwrite($fp, $content);
				fclose($fp);
			}
		}

		$result = $this->parserResponse($result);
		$this->onAfterRequest($url, $param, $result);
		return $result;
	}
}