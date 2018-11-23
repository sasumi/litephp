<?php
namespace Lite\Component;

use Exception;

/**
 * 邮件发送类
 * @package Lite\Component
 */
class Mail{
	public $id;
	public $date;
	public $headersRaw;
	public $headers;
	public $subject;
	public $fromName;
	public $fromAddress;
	public $to = array();
	public $toString;
	public $cc = array();
	public $bcc = array();
	public $replyTo = array();
	public $messageId;
	public $textPlain;
	public $textHtml;
	protected $attachments = array();
	
	public function addAttachment(MailAttachment $attachment){
		if($attachment->id){
			$this->attachments[$attachment->id] = $attachment;
		} else{
			$this->attachments[] = $attachment;
		}
	}
	
	/**
	 * @return MailAttachment[]
	 */
	public function getAttachments(){
		return $this->attachments;
	}
	
	public function sendMail(){
		if(is_object($this->smtp)){
			return $this->smtp->sendMail($this);
		}
		return false;
	}
	
	/*
	 * ---------------------以下几个方法兼容之前邮件发送--------
	 */
	private $config = array(); //邮件配置信息
	
	/** @var SMTP $smtp*/
	private $smtp = null; //邮件发送对象
	
	public function setSendConfig($config = null, $secure = false){
		$config = array_merge(array(
			'smtp'         => '',
			'mail_address' => '',
			'smtp_port'    => '',
			'smtp_user'    => '',
			'smtp_pwd'     => '',
			'message_id'   => '',
		), $config);
		
		if(empty($config['smtp']) || empty($config['smtp_port']) || empty($config['smtp_user']) || empty($config['smtp_pwd']) || empty($config['mail_address'])){
			throw new Exception('配置参数填写不完整');
		}
		$this->config = $config;
		$this->smtp = new SMTP($config['smtp'], $config['smtp_port'], $config['smtp_user'], $config['smtp_pwd'], $secure);
	}
	
	public function __destruct(){
		if($this->smtp){
			unset($this->smtp);
		}
	}
	
	/**
	 * @brief 邮件发送
	 * @param  $to      string 收件人
	 * @param  $title   string 标题
	 * @param  $content string 内容
	 * @param  $bcc     string 抄送人(";"分号间隔的email地址)
	 * @return bool true:成功;false:失败;
	 */
	public function send($to, $title, $content, $bcc = ''){
		if(is_object($this->smtp)){
			$from = $this->config['mail_address'];
			$message_id = $this->config['message_id'];
			$title = "=?UTF-8?B?".base64_encode($title)."?=";
			$headers = "MIME-Version: 1.0"."\r\n";
			$headers .= "Content-type: text/html; charset=utf-8"."\r\n";
			return $this->smtp->send($to, $from, $title, $content, $headers, "HTML", "", $bcc, $message_id);
		}
		return false;
	}
}

class MailAttachment{
	public $id;
	public $name;
	public $filePath;
	public $disposition;
	public $size;
	public $dataStream;
}