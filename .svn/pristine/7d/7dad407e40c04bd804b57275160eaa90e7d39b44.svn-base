<?php
namespace Lite\Component;
use Lite\Exception\Exception;

/**
 * Created in green.
 * File: email.php
 * User: sasumi
 * Date: 14-9-21
 * Time: 下午4:12
 */
class Mail {
	private $config = array(); //邮件配置信息
	private $smtp = null; //邮件发送对象

	public function __construct($config = null) {
		$config = array_merge(array(
			'smtp' => '',
			'mail_address' => '',
			'smtp_port' => '',
			'smtp_user' => '',
			'smtp_pwd' => '',
		), $config);

		if(empty($config['smtp']) ||
			empty($config['smtp_port']) ||
			empty($config['smtp_user']) ||
			empty($config['smtp_pwd']) ||
			empty($config['mail_address'])){
			throw new Exception('配置参数填写不完整');
		}
		$this->config = $config;
		$this->smtp = new SMTP($config['smtp'], $config['smtp_port'], $config['smtp_user'], $config['smtp_pwd']);
	}

	/**
	 * @brief 邮件发送
	 * @param  $to      string 收件人
	 * @param  $title   string 标题
	 * @param  $content string 内容
	 * @param  $bcc     string 抄送人(";"分号间隔的email地址)
	 * @return bool true:成功;false:失败;
	 */
	public function send($to, $title, $content, $bcc = '') {
		if(is_object($this->smtp)){
			$from = $this->config['mail_address'];
			$title = "=?UTF-8?B?" . base64_encode($title) . "?=";
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type: text/html; charset=utf-8" . "\r\n";
			return $this->smtp->send($to, $from, $title, $content, $headers, "HTML", "", $bcc);
		}
		return false;
	}
}