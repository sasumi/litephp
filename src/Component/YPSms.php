<?php
/**
 * 又拍云短信发送接口
 * User: sasumi
 * Date: 2014/10/31
 * Time: 10:27
 * @deprecated 这个是针对某个sms运营商的接口。
 */
namespace Lite\Component;

class YPSms{
	const SHORT_MSG = 0;
	const LONG_MSG = 1;

	private static $instance;
	private $config;

	private function __construct(array $config){
		$this->config = array_merge(array(
			'url'      => '',
			'username' => '',
			'password' => '',
			'sign'     => '',
			'max_count'
		), $config);
	}

	public static function instance($config = array()){
		if(!self::$instance){
			self::$instance = new self($config);
		}
		return self::$instance;
	}

	public function getConfig(){
		return $this->config;
	}

	/**
	 * 发送短信
	 * @param array|string $mobiles 手机号码,逗号分隔
	 * @param string $content 发送内容
	 * @param int $smstype 发送短信的类型：(值为0,1,2 )   0-网关1; 1-网关2; 2-网关3
	 * @param int $isLongSms 0-普通短信 1-长短信
	 * @param string $extno 扩展号，默认为空，在开通扩展功能的情况下填写
	 * @return bool|string success;1097076 失败：failure;用户名密码错误!
	 */
	public function send($mobiles, $content, $smstype = 2, $isLongSms = self::LONG_MSG, $extno = ''){
		$url = $this->config['url'];
		$username = $this->config['username'];
		$password = $this->config['password'];
		$password = base64_encode($password);

		if(!is_array($mobiles)){
			$mobiles = array($mobiles);
		}

		$max_count = $this->config['max_count'];
		if($max_count && count($mobiles) > $max_count){
			return '每次最多同时发送 '.$max_count.' 条短信';
		}

		$mobile_content = join(",", $mobiles);
		$content .= $this->config['sign'];
		$content = mb_convert_encoding($content, 'GBK', 'UTF-8');
		$param = array(
			'method'    => 'sendSMS',
			'isLongSms' => $isLongSms,
			'username'  => $username,
			'password'  => $password,
			'smstype'   => $smstype,
			'mobile'    => $mobile_content,
			'extno'     => $extno,
			'content'   => $content,
		);
		$request = $url.http_build_query($param);
		try{
			$result = Curl::get($request, 5);
		} catch(\Exception $ex){
			return $ex->getMessage();
		}
		$result = mb_convert_encoding($result, 'UTF-8', 'GBK');
		return $result;
	}
}