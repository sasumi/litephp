<?php

namespace Lite\Component\TPService\Aliyun;

use Lite\Exception\Exception;

class AliyunSms {
	private $config;

	public function __construct($config){
		$this->config = array_merge([
			'access_key_id'     => $config['access_key_id'],
			'access_key_secret' => $config['access_key_secret'],
			'action'            => $config['action'] ?: 'SendSms', //动作，默认为发送短信
			'region_id'         => 'cn-hangzhou',
			'sign_name'         => $config['sign_name'], //签名
			'template_code'     => $config['template_code'], //模板ID
			'timeout'           => 5, //请求超时时间
		], $config);
	}

	/**
	 * 单例方法，可以提供 access key & secret、template_code等参数，方便后续缺省调用
	 * @param array $config
	 * @return \Lite\Component\TPService\Aliyun\AliyunSms
	 */
	public static function instance($config = []){
		static $instance;
		if(!$instance){
			$instance = new self($config);
		}
		return $instance;
	}

	/**
	 * 发送短信
	 * @param string $phone 手机号
	 * @param array $template_param 模板参数
	 * @param string $sign_name 签名
	 * @param string $template_code 模板ID
	 * @return bool|\stdClass
	 */
	public function sendSms($phone, array $template_param = [], $sign_name = '', $template_code = ''){
		$sign_name = $sign_name ?: $this->config['sign_name'];
		$template_code = $template_code ?: $this->config['template_code'];

		$helper = new SignatureHelper($this->config['timeout']);
		$param = [
			'Version'       => '2017-05-25',
			'Action'        => $this->config['action'],
			'RegionId'      => $this->config['region_id'],
			'PhoneNumbers'  => $phone,
			'TemplateCode'  => $template_code,
			'SignName'      => $sign_name,
			'TemplateParam' => $template_param ? json_encode($template_param, JSON_UNESCAPED_UNICODE) : null,
		];
		$content = $helper->request($this->config['access_key_id'], $this->config['access_key_secret'], 'dysmsapi.aliyuncs.com', $param);
		if(is_array($content) || $content['Code'] == 'OK'){
			return $content;
		}
		throw new Exception($content['Message'], -1, $content);
	}
}
