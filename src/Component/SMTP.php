<?php
namespace Lite\Component;
use Lite\Exception\Exception;

/**
 * SMTP服务类
 * File: SMTP.php
 * User: sasumi
 * Date: 14-9-21
 * Time: 下午4:15
 */
class SMTP {
	private $smtp_port; //端口号
	private $time_out; //超时时间
	private $host_name; //主机名
	private $relay_host; //响应主机ip
	private $auth; //认证
	private $user; //用户名
	private $pass; //端口
	private $socket;
	private $sendType;

	/**
	 * @brief 构造函数
	 * @param string $relay_host 响应的服务器地址 如果利用本地smtp发送邮件请留空值
	 * @param int $smtp_port 端口号
	 * @param bool|string $user 用户名
	 * @param bool|string $pass 密码
	 * @note 如果采用本地服务器方式发送邮件那么所有参数可以不填写
	 */
	function __construct($relay_host = "", $smtp_port = 25, $user = false, $pass = false) {
		$this->relay_host = $relay_host;
		$this->smtp_port = $smtp_port;
		$this->user = $user;
		$this->pass = $pass;

		$this->time_out = 40;
		$this->host_name = "localhost"; //测试本地socket

		$this->auth = false;
		if($this->user || $this->pass){
			$this->auth = true;
		}
		if(!$this->relay_host){
			$this->sendType = "mail";
		}
	}

	/**
	 * @brief 发送邮件前数据初始化
	 * @param string $to 收件人email地址
	 * @param string $from 发件人email地址
	 * @param string $subject 邮件主题
	 * @param string $body 邮件内容
	 * @param string $additional_headers 附加头信息
	 * @param string $mailtype 邮件发送类型
	 * @param string $cc 抄送其他人
	 * @param string $bcc 暗送其他人
	 * @throws Exception
	 * @return bool 发送状态 值: true:成功; false:失败;
	 */
	public function send($to, $from = "", $subject = "", $body = "", $additional_headers = "", $mailtype = "HTML", $cc = "", $bcc = "") {
		$mail_from = $this->get_address($this->strip_comment($from));
		$body = preg_replace("/(^|(\r\n))(\\.)/i", "\\1.\\3", $body);
		$header = "";
		$header .= "MIME-Version:1.0\r\n";
		if($mailtype == "HTML"){
			$header .= "Content-Type:text/html\r\n";
		}
		$header .= "To: " . $to . "\r\n";
		if($cc != ""){
			$header .= "Cc: " . $cc . "\r\n";
		}
		$header .= "From: $from<" . $from . ">\r\n";
		$header .= "Subject: " . $subject . "\r\n";
		$header .= $additional_headers;
		$header .= "Date: " . date("r") . "\r\n";
		$header .= "X-Mailer:By Redhat (PHP/" . phpversion() . ")\r\n";
		list($msec, $sec) = explode(" ", microtime());
		$header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from . ">\r\n";
		$TO = explode(",", $this->strip_comment($to));

		if($cc != ""){
			$TO = array_merge($TO, explode(",", $this->strip_comment($cc)));
		}

		if($bcc != ""){
			$TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
		}

		$sent = true;
		foreach($TO as $rcpt_to){
			//php内置mail发送
			if($this->sendType == "mail"){
				return mail($rcpt_to, '', $body, $header);
			}
			//socket发送方式
			$rcpt_to = $this->get_address($rcpt_to);
			$this->smtp_sockopen($rcpt_to);
			if(!$this->smtpSend($this->host_name, $mail_from, $rcpt_to, $header, $body)){
				throw new Exception("Error: Cannot send email to <" . $rcpt_to . ">");
			};
			fclose($this->socket);
		}
		return $sent;
	}

	/**
	 * @brief 开始发送邮件
	 * @param string $helo 链接smtp hello
	 * @param string $from 发件人
	 * @param string $to 收件人
	 * @param string $header 头信息
	 * @param string $body 邮件内容
	 * @throws Exception
	 * @return bool 发送状态 值: true:成功; false:失败;
	 */
	private function smtpSend($helo, $from, $to, $header, $body = "") {

		if(!$this->smtp_putcmd("HELO", $helo)){
			throw new Exception('sending HELO command');
		}

		if($this->auth){
			if(!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user))){
				throw new Exception('sending HELO command');
			}

			if(!$this->smtp_putcmd("", base64_encode($this->pass))){
				throw new Exception('sending HELO command');
			}
		}
		if(!$this->smtp_putcmd("MAIL", "FROM:<" . $from . ">")){
			throw new Exception('sending MAIL FROM command');
		}

		if(!$this->smtp_putcmd("RCPT", "TO:<" . $to . ">")){
			throw new Exception('sending RCPT TO command');
		}

		if(!$this->smtp_putcmd("DATA")){
			throw new Exception('sending DATA command');
		}

		fwrite($this->socket, $header . "\r\n" . $body);

		if(!$this->smtp_eom()){
			throw new Exception('sending <CR><LF>.<CR><LF> [EOM]');
		}

		if(!$this->smtp_putcmd("QUIT")){
			throw new Exception('sending QUIT command');
		}

		return true;
	}

	/**
	 * @brief 链接socket
	 * @param string $address 自定义链接地址
	 * @return bool 是否成功打开socket
	 */
	private function smtp_sockopen($address) {
		if($this->relay_host == ""){
			$this->smtp_sockopen_mx($address);
		}
		else {
			$this->smtp_sockopen_relay();
		}
	}

	/**
	 * @brief 打开smtp配置的socket
	 * @throws Exception
	 * @return bool 是否成功打开socket
	 */
	private function smtp_sockopen_relay() {
		$this->socket = @fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
		if(!($this->socket && $this->smtp_ok())){
			throw new Exception($errstr, $errno);
		}
		return true;
	}

	/**
	 * @brief 打开自定义的socket链接
	 * @param $address
	 * @throws Exception
	 * @return bool 是否成功打开socket
	 */
	private function smtp_sockopen_mx($address) {
		$domain = preg_replace("/^.+@([^@]+)$/i", "\\1", $address);
		if(!@getmxrr($domain, $MXHOSTS)){
			throw new Exception('Error: Cannot resolve MX :'.$domain);
		}
		foreach($MXHOSTS as $host){
			$this->socket = @fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
			if(!($this->socket && $this->smtp_ok())){
				continue;
			}
			return true;
		}
		throw new Exception("Warning: Cannot connect to mx host " . implode(", ", $MXHOSTS));
	}

	private function smtp_eom() {
		fwrite($this->socket, "\r\n.\r\n");
		return $this->smtp_ok();
	}

	private function smtp_ok() {
		$response = str_replace("\r\n", "", fgets($this->socket, 512));
		if(!preg_match("/^[23]/i", $response)){
			fputs($this->socket, "QUIT\r\n");
			fgets($this->socket, 512);
			throw new Exception('Error: Remote host returned:'.$response);
		}
		return true;
	}

	/**
	 * @brief 命令处理
	 * @param string $cmd 命令
	 * @param string $arg
	 * @return bool 命令处理状态
	 */
	private function smtp_putcmd($cmd, $arg = "") {
		if($arg != ""){
			if($cmd == "")
				$cmd = $arg;
			else $cmd = $cmd . " " . $arg;
		}

		fwrite($this->socket, $cmd . "\r\n");
		return $this->smtp_ok();
	}

	private function strip_comment($address) {
		$comment = "\\([^()]*\\)";
		while(preg_match('/' . $comment . '/i', $address)){
			$address = preg_replace('/' . $comment . '/i', "", $address);
		}
		return $address;
	}

	/**
	 * @brief 处理email地址
	 * @param string $address 地址
	 * @return string 处理后的地址
	 */
	private function get_address($address) {
		$address = preg_replace("/([ \t\r\n])+/i", "", $address);
		$address = preg_replace("/^.*<(.+)>.*$/i", "\\1", $address);
		return $address;
	}

	/**
	 * @brief 获取附件信息
	 * @param string $image_tag 附件文件
	 * @return array 附件信息 键: context:内容; filename:文件名; type:文件类型;
	 */
	public function get_attach_type($image_tag) {
		$filedata = array();
		$img_file_con = fopen($image_tag, "r");
		$image_data = null;
		while($tem_buffer = addSlashes(fread($img_file_con, filesize($image_tag)))){
			$image_data .= $tem_buffer;
		}
		fclose($img_file_con);
		$filedata['context'] = $image_data;
		$filedata['filename'] = basename($image_tag);
		$extension = substr($image_tag, strrpos($image_tag, "."), strlen($image_tag) - strrpos($image_tag, "."));
		switch($extension) {
			case ".gif":
				$filedata['type'] = "image/gif";
				break;
			case ".gz":
				$filedata['type'] = "application/x-gzip";
				break;
			case ".htm":
				$filedata['type'] = "text/html";
				break;
			case ".html":
				$filedata['type'] = "text/html";
				break;
			case ".jpg":
				$filedata['type'] = "image/jpeg";
				break;
			case ".tar":
				$filedata['type'] = "application/x-tar";
				break;
			case ".txt":
				$filedata['type'] = "text/plain";
				break;
			case ".zip":
				$filedata['type'] = "application/zip";
				break;
			default:
				$filedata['type'] = "application/octet-stream";
				break;
		}
		return $filedata;
	}
}