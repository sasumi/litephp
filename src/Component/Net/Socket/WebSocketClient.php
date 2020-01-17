<?php

namespace Lite\Component\Net\Socket;

/**
 * WebSocket服务
 * <pre>用例：
 * $server = new Server('8080');
 * $server->onMessage(function($msg, $client_hd){
 * if($msg == 'hello'){
 *          $server->sendMessageToClient($client_hd, 'world');
 *      }
 * });
 * $server->start();
 * </pre>
 * @package Lite\Component\Net
 */

/**
 * Simple WebSocket client.
 * @author Simon Samtleben <foo@bloatless.org>
 * @version 2.0
 */
class WebSocketClient {
	private $host;
	private $port;
	private $path;
	private $origin;

	private $socket = null;
	private $connected = false;

	public function __destruct(){
		$this->disconnect();
	}

	public function __construct($host, $port, $path, $origin = ''){
		$this->host = $host;
		$this->port = $port;
		$this->path = $path;
		$this->origin = $origin;
	}

	/**
	 * Sends data to remote server.
	 * @param string $data
	 * @param string $type
	 * @param bool $masked
	 * @return bool
	 */
	public function sendData($data = '', $type = 'text', $masked = true){
		if($this->connected === false){
			trigger_error("Not connected", E_USER_WARNING);
			return false;
		}
		if(!is_string($data)){
			trigger_error("Not a string data was given.", E_USER_WARNING);
			return false;
		}
		if(strlen($data) === 0){
			return false;
		}
		$res = @fwrite($this->socket, $this->encode($data, $type, $masked));
		if($res === 0 || $res === false){
			return false;
		}
		$buffer = ' ';
		while($buffer !== ''){
			$buffer = fread($this->socket, 512);// drop?
		}
		return true;
	}

	/**
	 * Connects to a websocket server.
	 * @return bool
	 */
	public function connect(){
		$key = base64_encode($this->generateRandomString(16, false, true));
		$header = "GET ".$this->path." HTTP/1.1\r\n";
		$header .= "Host: ".$this->host.":".$this->port."\r\n";
		$header .= "Upgrade: websocket\r\n";
		$header .= "Connection: Upgrade\r\n";
		$header .= "Sec-WebSocket-Key: ".$key."\r\n";
		if(!empty($origin)){
			$header .= "Sec-WebSocket-Origin: ".$origin."\r\n";
		}
		$header .= "Sec-WebSocket-Version: 13\r\n\r\n";
		$this->socket = fsockopen($this->host, $this->port, $errno, $err_str, 2);
		if($this->socket === false){
			return false;
		}
		socket_set_timeout($this->socket, 0, 10000);
		@fwrite($this->socket, $header);
		$response = @fread($this->socket, 1500);
		if(preg_match('#Sec-WebSocket-Accept:\s(.*)$#mU', $response, $matches)){
			$keyAccept = trim($matches[1]);
			$expectedResponse = base64_encode(pack('H*', sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			$this->connected = ($keyAccept === $expectedResponse) ? true : false;
		}
		return $this->connected;
	}

	/**
	 * Checks if connection to web server is active.
	 * @return bool
	 */
	public function checkConnection(){
		$this->connected = false;
		// send ping:
		$data = 'ping?';
		@fwrite($this->socket, $this->encode($data, 'ping', true));
		$response = @fread($this->socket, 300);
		if(empty($response)){
			return false;
		}
		$response = $this->decode($response);
		if(!is_array($response)){
			return false;
		}
		if(!isset($response['type']) || $response['type'] !== 'pong'){
			return false;
		}
		$this->connected = true;
		return true;
	}

	/**
	 * Disconnect from websocket server.
	 * @return void
	 */
	public function disconnect(){
		$this->connected = false;
		is_resource($this->socket) && fclose($this->socket);
	}

	/**
	 * Reconnects to previously connected websocket server.
	 * @return void
	 */
	public function reconnect(){
		fclose($this->socket);
		$this->connected = false;
		$this->connect();
	}

	/**
	 * Generates a random string.
	 * @param int $length
	 * @param bool $addSpaces
	 * @param bool $addNumbers
	 * @return string
	 */
	private function generateRandomString($length = 10, $addSpaces = true, $addNumbers = true){
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
		$useChars = [];
		// select some random chars:
		for($i = 0; $i < $length; $i++){
			$useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
		}
		// add spaces and numbers:
		if($addSpaces === true){
			array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
		}
		if($addNumbers === true){
			array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
		}
		shuffle($useChars);
		$randomString = trim(implode('', $useChars));
		$randomString = substr($randomString, 0, $length);
		return $randomString;
	}

	/**
	 * Encodes data according to the WebSocket protocol standard.
	 * @param string $payload
	 * @param string $type
	 * @param bool $masked
	 * @return string
	 */
	private function encode($payload, $type = 'text', $masked = true){
		$frameHead = [];
		$payloadLength = strlen($payload);
		switch($type){
			case 'text':
				// first byte indicates FIN, Text-Frame (10000001):
				$frameHead[0] = 129;
				break;
			case 'close':
				// first byte indicates FIN, Close Frame(10001000):
				$frameHead[0] = 136;
				break;
			case 'ping':
				// first byte indicates FIN, Ping frame (10001001):
				$frameHead[0] = 137;
				break;
			case 'pong':
				// first byte indicates FIN, Pong frame (10001010):
				$frameHead[0] = 138;
				break;
		}
		// set mask and payload length (using 1, 3 or 9 bytes)
		if($payloadLength > 65535){
			$payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
			$frameHead[1] = ($masked === true) ? 255 : 127;
			for($i = 0; $i < 8; $i++){
				$frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
			}
			// most significant bit MUST be 0 (close connection if frame too big)
			if($frameHead[2] > 127){
				$this->disconnect();
				throw new \RuntimeException('Invalid payload. Could not encode frame.');
			}
		}elseif($payloadLength > 125){
			$payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
			$frameHead[1] = ($masked === true) ? 254 : 126;
			$frameHead[2] = bindec($payloadLengthBin[0]);
			$frameHead[3] = bindec($payloadLengthBin[1]);
		}else{
			$frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
		}
		// convert frame-head to string:
		foreach(array_keys($frameHead) as $i){
			$frameHead[$i] = chr($frameHead[$i]);
		}
		$mask = [];
		if($masked === true){
			// generate a random mask:
			for($i = 0; $i < 4; $i++){
				$mask[$i] = chr(rand(0, 255));
			}
			$frameHead = array_merge($frameHead, $mask);
		}
		$frame = implode('', $frameHead);
		// append payload to frame:
		for($i = 0; $i < $payloadLength; $i++){
			$frame .= ($masked === true) ? $payload[$i]^$mask[$i%4] : $payload[$i];
		}
		return $frame;
	}

	/**
	 * Decodes a received frame/sting according to the WebSocket protocol standards.
	 * @param string $data
	 * @return array
	 */
	private function decode($data){
		$unmaskedPayload = '';
		$decodedData = [];
		// estimate frame type:
		$firstByteBinary = sprintf('%08b', ord($data[0]));
		$secondByteBinary = sprintf('%08b', ord($data[1]));
		$opcode = bindec(substr($firstByteBinary, 4, 4));
		$isMasked = ($secondByteBinary[0] == '1') ? true : false;
		$payloadLength = ord($data[1])&127;
		switch($opcode){
			// text frame:
			case 1:
				$decodedData['type'] = 'text';
				break;
			case 2:
				$decodedData['type'] = 'binary';
				break;
			// connection close frame:
			case 8:
				$decodedData['type'] = 'close';
				break;
			// ping frame:
			case 9:
				$decodedData['type'] = 'ping';
				break;
			// pong frame:
			case 10:
				$decodedData['type'] = 'pong';
				break;
			default:
				throw new \RuntimeException('Could not decode frame. Invalid type.');
		}
		if($payloadLength === 126){
			$mask = substr($data, 4, 4);
			$payloadOffset = 8;
			$dataLength = bindec(sprintf('%08b', ord($data[2])).sprintf('%08b', ord($data[3]))) + $payloadOffset;
		}elseif($payloadLength === 127){
			$mask = substr($data, 10, 4);
			$payloadOffset = 14;
			$tmp = '';
			for($i = 0; $i < 8; $i++){
				$tmp .= sprintf('%08b', ord($data[$i + 2]));
			}
			$dataLength = bindec($tmp) + $payloadOffset;
			unset($tmp);
		}else{
			$mask = substr($data, 2, 4);
			$payloadOffset = 6;
			$dataLength = $payloadLength + $payloadOffset;
		}
		if($isMasked === true){
			for($i = $payloadOffset; $i < $dataLength; $i++){
				$j = $i - $payloadOffset;
				if(isset($data[$i])){
					$unmaskedPayload .= $data[$i]^$mask[$j%4];
				}
			}
			$decodedData['payload'] = $unmaskedPayload;
		}else{
			$payloadOffset = $payloadOffset - 4;
			$decodedData['payload'] = substr($data, $payloadOffset);
		}
		return $decodedData;
	}
}
