<?php
namespace Lite\Component\Mail;
use Lite\Component\File\MimeInfo;
use Lite\Exception\Exception;

/**
 * SMTP服务类
 * File: SMTP.php
 * User: sasumi
 * Date: 14-9-21
 * Time: 下午4:15
 */
class SMTP{
	private $smtp_port; //端口号
	private $time_out; //超时时间
	private $host_name; //主机名
	private $relay_host; //响应主机ip
	private $auth; //认证
	private $user; //用户名
	private $pass; //端口
	private $socket;
	private $sendType;
	
	private $charset = "UTF-8";
	private $secure = false;//加密协议ssl,tls
	const CRLF = "\n";
	
	/**
	 * 单例
	 * @param string $relay_host
	 * @param int $smtp_port
	 * @param bool $user
	 * @param bool $pass
	 * @param bool $secure
	 * @return self
	 */
	public static function instance($relay_host = "", $smtp_port = 25, $user = false, $pass = false, $secure = false){
		static $instance_list;
		$guid = serialize(func_get_args());
		if(!$instance_list || !$instance_list[$guid]){
			$instance_list[$guid] = new self($relay_host, $smtp_port = 25, $user, $pass, $secure);
		}
		return $instance_list[$guid];
	}
	
	/**
	 * @brief 构造函数
	 * @param string $relay_host 响应的服务器地址 如果利用本地smtp发送邮件请留空值
	 * @param int $smtp_port 端口号
	 * @param bool|string $user 用户名
	 * @param bool|string $pass 密码
	 * @param bool|string $secure 协议
	 * @note 如果采用本地服务器方式发送邮件那么所有参数可以不填写
	 */
	public function __construct($relay_host = "", $smtp_port = 25, $user = false, $pass = false, $secure = false){
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
		if($secure){
			$this->secure = strtolower($secure);
			if($this->secure == 'ssl'){
				$this->relay_host = "ssl://".$relay_host;
			}
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
	 * @param string $message_id msg_id
	 * @throws Exception
	 * @return bool 发送状态 值: true:成功; false:失败;
	 */
	public function send($to, $from = "", $subject = "", $body = "", $additional_headers = "", $mailtype = "HTML", $cc = "", $bcc = "", $message_id = "") {
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
		$message_id = $message_id?:date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from;
		$header .= "Message-ID: <$message_id>\r\n";
		$TO = explode(",", $this->strip_comment($to));

		if($cc != ""){
			$TO = array_merge($TO, explode(",", $this->strip_comment($cc)));
		}

		if($bcc != ""){
			$TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
		}

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
		return $message_id;
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
			return $this->smtp_sockopen_mx($address);
		}
		else {
			return $this->smtp_sockopen_relay();
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
	
	/**
	 * @param $address
	 * @return null|string|string[]
	 */
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
		$field_data = array();
		$img_file_con = fopen($image_tag, "r");
		$image_data = null;
		while($tem_buffer = addslashes(fread($img_file_con, filesize($image_tag)))){
			$image_data .= $tem_buffer;
		}
		fclose($img_file_con);
		$field_data['context'] = $image_data;
		$field_data['filename'] = basename($image_tag);
		$extension = substr($image_tag, strrpos($image_tag, "."), strlen($image_tag) - strrpos($image_tag, "."));
		switch($extension) {
			case ".gif":
				$field_data['type'] = "image/gif";
				break;
			case ".gz":
				$field_data['type'] = "application/x-gzip";
				break;
			case ".htm":
				$field_data['type'] = "text/html";
				break;
			case ".jpg":
				$field_data['type'] = "image/jpeg";
				break;
			case ".tar":
				$field_data['type'] = "application/x-tar";
				break;
			case ".txt":
				$field_data['type'] = "text/plain";
				break;
			case ".zip":
				$field_data['type'] = "application/zip";
				break;
			default:
				$field_data['type'] = "application/octet-stream";
				break;
		}
		return $field_data;
	}
	
	
	//------------------------------------------------------//
	/**
	 * 以下方法实现发送邮件附件
	 */
	
	/**
	 * 发送邮件--包括附件
	 * @param \Lite\Component\Mail\Mail $mail
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public function sendMail(Mail $mail){
		if(!$mail->textHtml){
			return false;
		}
		if(!is_array($mail->to)){
			$mail->to = array($mail->to);
		}
		//Create Body Content--8bit
		$body = $mail->textHtml;
		$body = str_replace(array("\r\n", "\r"), "\n", $body);
		$body = str_replace("\n", self::CRLF, $body);
		$body .= self::CRLF;
		
		foreach($mail->to ?: [] as $k => $toAddress){
			if($mail->getAttachments()){
				$header = $this->mimeHeader($mail, $body, $toAddress);
			} else{
				$header = $this->mailHeader($mail, $body, $toAddress);
			}
			//开始连接
			$this->smtp_sockopen($toAddress);
			if(!$this->smtp_putcmd("HELO", $this->relay_host)){
				throw new Exception('sending HELO command');
			}
			if($this->secure == 'tls'){
				if(!$this->startTLS()){
					throw new Exception('StartTLS command');
				}
				if(!$this->smtp_putcmd("HELO", $this->relay_host)){
					throw new Exception('sending HELO command');
				}
			}
			if($this->auth){
				if(!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user))){
					throw new Exception('sending HELO command');
				}
				if(!$this->smtp_putcmd("", base64_encode($this->pass))){
					throw new Exception('sending HELO command');
				}
			}
			if(!$this->smtp_putcmd("MAIL", "FROM:<".$mail->fromAddress.">")){
				throw new Exception('sending MAIL FROM command');
			}
			if(!$this->smtp_putcmd("RCPT", "TO:<".$toAddress.">")){
				throw new Exception('sending RCPT TO command');
			}
			if(!$this->smtp_putcmd("DATA")){
				throw new Exception('sending DATA command');
			}
			fwrite($this->socket, $header . "\r" );
			
			if(!$this->smtp_eom()){
				throw new Exception('sending <CR><LF>.<CR><LF> [EOM]');
			}
			
			if(!$this->smtp_putcmd("QUIT")){
				throw new Exception('sending QUIT command');
			}
			
			fclose($this->socket);
		}
		return true;
	}
	
	/**
	 * Initiate a TLS communication with the server.
	 * SMTP CODE 220 Ready to start TLS
	 * SMTP CODE 501 Syntax error (no parameters allowed)
	 * SMTP CODE 454 TLS not available due to temporary reason
	 * @access public
	 * @return bool success
	 * @throws Exception
	 */
	public function startTLS(){
		if(!$this->smtp_putcmd("STARTTLS".self::CRLF)){
			throw new Exception('sending STARTTLS command');
		}
		// Begin encrypted connection
		if(!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)){
			return false;
		}
		return true;
	}
	
	/**
	 * 带附件的头部信息
	 * @param $mail
	 * @param $body
	 * @param $toAddress
	 * @return string
	 */
	private function mimeHeader(Mail $mail, $body, $toAddress){
		if($attachments = $mail->getAttachments()){
			$headers = array();
			$uuid = md5(uniqid(time()));
			$boundary = '----='.$uuid;
			$headers[] = 'Date: '.self::RFCDate();
			$headers[] = 'Return-Path: '.$mail->fromAddress;
			
			$headers[] = 'To: "'.'=?'.$this->charset.'?B?'.base64_encode($toAddress).'?=" <'.$toAddress.'>';
			$headers[] = 'From: "=?'.$this->charset.'?B?'.base64_encode($mail->fromName).'?=" <'.$mail->fromAddress.'>';
			$headers[] = 'Subject: =?'.$this->charset.'?B?'.base64_encode($mail->subject).'?=';
			
			$headers[] = 'Message-ID: <'.$uuid.'@localhost>';
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-Type: multipart/mixed;';
			$headers[] = "\t".'boundary="'.$boundary.'"'.self::CRLF.self::CRLF;
			$headers[] = '--'.$boundary;
			
			//add body
			$headers[] = 'Content-Type: text/html;charset="'.$this->charset.'"';
			$headers[] = 'Content-Transfer-Encoding: 8bit'.self::CRLF;
			$headers[] = $body;
			
			foreach($attachments as $k => $attach){
				$filename = $attach->filePath;
				$ext = substr(strrchr($filename, '.'), 1);
				$mimeType = MimeInfo::getMimesByExtensions([$ext])[0];
				$mimeType = $mimeType ?: 'application/octet-stream';

				//add attachment
				$headers[] = "--".$boundary;
				$headers[] = "Content-Type: ".$mimeType."; name=\"=?".$this->charset."?B?".base64_encode(basename($filename)).'?="';
				$headers[] = 'Content-Transfer-Encoding: base64';
				$headers[] = "Content-Disposition: attachment; filename=\"=?".$this->charset."?B?".base64_encode(basename($filename)).'?="'.self::CRLF;
				
				$f = @fopen($filename, 'r');
				
				$contents = '';
				while(!feof($f)){
					$contents .= @fread($f, 8192);
				}
				fclose($f);
				
				$headers[] = chunk_split(base64_encode($contents), 76, self::CRLF);
				$headers[] = '';
			}
			$headers[] = "--".$boundary."--".self::CRLF;
			$headers = str_replace(self::CRLF.'.', self::CRLF.'..', trim(implode(self::CRLF, $headers)));
			return $headers;
		}
		return '';
	}
	
	/**
	 * 添加普通邮件头信息
	 * @param $mail
	 * @param $body
	 * @param $toAddress
	 * @return array
	 */
	private function mailHeader(Mail $mail, $body, $toAddress){
		$headers = array();
		$headers[] = 'Date: '.self::RFCDate();
		$headers[] = 'To: "'.'=?'.$this->charset.'?B?'.base64_encode($this->getMailUser($toAddress)).'?="<'.$toAddress.'>';
		$headers[] = 'From: "=?'.$this->charset.'?B?'.base64_encode($mail->fromName).'?="<'.$mail->fromAddress.'>';
		$headers[] = 'Subject: =?'.$this->charset.'?B?'.base64_encode($mail->subject).'?=';
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset='.$this->charset.'; format=flowed';
		$headers[] = 'Content-Transfer-Encoding: 8bit'.self::CRLF;
		$headers[] = $body.self::CRLF;
		$headers = str_replace(self::CRLF.'.', self::CRLF.'..', trim(implode(self::CRLF, $headers)));
		return $headers;
	}
	
	/**
	 * 返回邮件地址前缀
	 * @param $to
	 * @return mixed
	 */
	private function getMailUser($to){
		$temp = explode('@', $to);
		return $temp[0];
	}

	/**
	 * Returns the proper RFC 822 formatted date.
	 * @access public
	 * @return string
	 * @static
	 */
	private static function RFCDate(){
		return date('D, j M Y H:i:s O');
	}
	
	/**
	 * 尝试连接，say hello
	 * @param $toAddress
	 * @throws
	 */
	public function tryConnect($toAddress){
		$this->smtp_sockopen($toAddress);
		if(!$this->smtp_putcmd("HELO", $this->relay_host)){
			throw new Exception('sending HELO command');
		}
		if($this->secure == 'tls'){
			if(!$this->startTLS()){
				throw new Exception('StartTLS command');
			}
			if(!$this->smtp_putcmd("HELO", $this->relay_host)){
				throw new Exception('sending HELO command');
			}
		}
		if(!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user))){
			throw new Exception('sending AUTH LOGIN command');
		}
		if(!$this->smtp_putcmd("", base64_encode($this->pass))){
			throw new Exception('sending  command');
		}
		fclose($this->socket);
	}
}
