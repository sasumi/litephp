<?php

namespace Lite\Component\Net\WebSocket;

use Lite\Component\Misc\Event;
use Lite\Exception\Exception;

/**
 * WebSocket服务
 * <pre>用例：
 * $server = new Server('8080');
 * $server->onMessage(function($msg, $client_hd){
		if($msg == 'hello'){
 *          $server->sendMessageToClient($client_hd, 'world');
 *      }
 * });
 * $server->start();
 * </pre>
 * @package Lite\Component\Net
 */
class WsServer {
	use Event;
	const LOCAL_IP = '127.0.0.1';

	const EVENT_ON_CREATE = 0x01;
	const EVENT_ON_BIND = 0x02;
	const EVENT_ON_LISTEN = 0x03;
	const EVENT_ON_MESSAGE = 0x04;
	const EVENT_ON_CLOSED = 0x05;
	const EVENT_ON_ERROR = 0x06;

	private $address;
	private $port;
	private $backlog;
	private $timeout;
	private $socket_handle;
	private $is_hand_shacked;
	private $websocket_key;
	private $current_message_length;
	private $mask_key;
	private $event_hooks = [];

	/**
	 * WebSocketServer constructor.
	 * @param number $port
	 * @param string $address
	 * @param int $backlog
	 * @param int $timeout
	 */
	public function __construct($port, $address = self::LOCAL_IP, $backlog = 10, $timeout = 0){
		$this->address = $address;
		$this->port = $port;
		$this->backlog = $backlog;
		$this->timeout = $timeout;
	}

	public function debug($logger = null){
		$logger = $logger ?: function(...$messages){
			echo date('Y-m-d H:i:s'), " ", join("\t", $messages)."\n";
		};

		$this->onMessage(function($msg) use ($logger){
			$logger('[Message]', $msg);
		});

		$this->onError(function($error) use ($logger){
			$logger('[Error]', $error);
		});

		$this->onCreate(function() use ($logger){
			$logger('[SocketCreated]');
		});

		$this->onClose(function()use($logger){
			$logger('[Closed]');
		});
	}

	public function onError($handle){
		return $this->bindEvent(self::EVENT_ON_ERROR, $handle);
	}

	public function onCreate($handle){
		return $this->bindEvent(self::EVENT_ON_CREATE, $handle);
	}

	public function onMessage($handle){
		$this->bindEvent(self::EVENT_ON_MESSAGE, $handle);
	}

	public function onClose($handle){
		$this->bindEvent(self::EVENT_ON_CLOSED, $handle);
	}

	private function error($error_message){
		$event_ret = $this->fireEvent(self::EVENT_ON_ERROR, $error_message);
		$this->close();
		if($event_ret === false){
			throw new Exception($error_message);
		}
		return false;
	}

	private function socketError($handle = null){
		$handle = $handle ?: $this->socket_handle;
		$error = socket_strerror(socket_last_error($handle));
		return $this->error($error);
	}

	private function createSocket(){
		$this->socket_handle = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(!$this->socket_handle){
			return $this->socketError();
		}
		$this->fireEvent(self::EVENT_ON_CREATE, $this->socket_handle);
		return true;
	}

	private function bindAddress(){
		if(!socket_bind($this->socket_handle, $this->address, $this->port)){
			return $this->socketError();
		}
		$this->fireEvent(self::EVENT_ON_BIND, $this->socket_handle);
		return true;
	}

	private function listen(){
		if(!socket_listen($this->socket_handle, $this->backlog)){
			return $this->socketError();
		}
		$this->fireEvent(self::EVENT_ON_LISTEN, $this->socket_handle);
		return true;
	}

	public function close(){
		$ret = socket_close($this->socket_handle);
		$this->fireEvent(self::EVENT_ON_CLOSED, $ret);
		return $ret;
	}

	private function shakeHand($client_socket_handle){
		if(socket_recv($client_socket_handle, $buffer, 1000, 0) < 0){
			return $this->socketError();
		}
		while(true){
			if(preg_match("/([^\r]+)\r\n/", $buffer, $match) > 0){
				$content = $match[1];
				if(strncmp($content, "Sec-WebSocket-Key", strlen("Sec-WebSocket-Key")) == 0){
					$this->websocket_key = trim(substr($content, strlen("Sec-WebSocket-Key:")), " \r\n");
				}
				$buffer = substr($buffer, strlen($content) + 2);
			}else{
				break;
			}
		}
		//响应客户端
		$this->writeToSocket($client_socket_handle, "HTTP/1.1 101 Switching Protocol\r\n");
		$this->writeToSocket($client_socket_handle, "Upgrade: websocket\r\n");
		$this->writeToSocket($client_socket_handle, "Connection: upgrade\r\n");
		$this->writeToSocket($client_socket_handle, "Sec-WebSocket-Accept:".$this->calculateResponseKey()."\r\n");
		$this->writeToSocket($client_socket_handle, "Sec-WebSocket-Version: 13\r\n\r\n");
		return true;
	}

	private function writeToSocket($client_socket, $content){
		$ret = socket_write($client_socket, $content, strlen($content));
		if(!$ret){
			return $this->socketError($client_socket);
		}
		return true;
	}

	private function accept(){
		while(true){
			$client_socket_handle = socket_accept($this->socket_handle);
			if(!$client_socket_handle){
				return $this->socketError();
			}

			//与客户端握手
			if(!$this->is_hand_shacked){
				$this->shakehand($client_socket_handle);
				$this->is_hand_shacked = true;
			}
			//等待客户端新传输的数据
			if(!socket_recv($client_socket_handle, $buffer, 1000, 0)){
				return $this->socketError($client_socket_handle);
			}

			//解析消息的长度
			$payload_length = ord($buffer[1])&0x7f;//第二个字符的低7位
			if($payload_length >= 0 && $payload_length < 125){
				$this->current_message_length = $payload_length;
				$payload_type = 1;
				echo $payload_length."\n";
			}else if($payload_length == 126){
				$payload_type = 2;
				$this->current_message_length = ((ord($buffer[2])&0xff)<<8)|(ord($buffer[3])&0xff);
				echo $this->current_message_length;
			}else{
				$payload_type = 3;
				$this->current_message_length = (ord($buffer[2])<<56)|(ord($buffer[3])<<48)|(ord($buffer[4])<<40)|(ord($buffer[5])<<32)|(ord($buffer[6])<<24)|(ord($buffer[7])<<16)|(ord($buffer[8])<<8)|(ord($buffer[9])<<0);
			}

			//解析掩码，这个必须有的，掩码总共4个字节
			$mask_key_offset = ($payload_type == 1 ? 0 : ($payload_type == 2 ? 2 : 8)) + 2;
			$this->mask_key = substr($buffer, $mask_key_offset, 4);

			//获取加密的内容
			$real_message = substr($buffer, $mask_key_offset + 4);
			$i = 0;
			$parsed_ret = '';
			//解析加密的数据
			while($i < strlen($real_message)){
				$parsed_ret .= chr((ord($real_message[$i])^ord(($this->mask_key[$i%4]))));
				$i++;
			}

			$this->fireEvent(self::EVENT_ON_MESSAGE, $parsed_ret, $client_socket_handle);
		}
		return true;
	}

	/**
	 * @param resource $client_socket_handle
	 * @param string $content
	 */
	public function sendMessageToClient($client_socket_handle, $content){
		$len = strlen($content);
		//第一个字节
		$char_seq = chr(0x80|1);

		$b_2 = 0;
		//fill length
		if($len > 0 && $len <= 125){
			$char_seq .= chr(($b_2|$len));
		}else if($len <= 65535){
			$char_seq .= chr(($b_2|126));
			$char_seq .= (chr($len>>8).chr($len&0xff));
		}else{
			$char_seq .= chr(($b_2|127));
			$char_seq .= (chr($len>>56).chr($len>>48).chr($len>>40).chr($len>>32).chr($len>>24).chr($len>>16).chr($len>>8).chr($len>>0));
		}
		$char_seq .= $content;
		$this->writeToSocket($client_socket_handle, $char_seq);
	}

	private function calculateResponseKey(){
		$GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
		$result = base64_encode(sha1($this->websocket_key.$GUID, true));
		return $result;
	}

	/**
	 * start server
	 */
	public function start(){
		set_time_limit($this->timeout);
		$this->createSocket();
		$this->bindAddress();
		$this->listen();
		$this->accept();
	}
}
