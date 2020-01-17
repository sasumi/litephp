<?php

namespace Lite\Component\Net\Socket;

use Lite\Component\Net\Http;

class Connection {
	private $server;
	private $socket_handle;
	private $client_ip;
	private $client_port;
	public $hand_shook = false;

	public $uuid;

	public function __construct($server, $socket_handle){
		$this->server = $server;
		$this->socket_handle = $socket_handle;
		list($this->client_ip, $this->client_port) = SocketHelper::getSocketClientIpPort($socket_handle);
		$this->uuid = md5($this->client_ip.$this->client_port.spl_object_hash($this));
	}

	/**
	 * @param self[] $connections
	 * @return resource[]
	 */
	public static function getHandles(array $connections){
		$handles = [];
		foreach($connections as $connection){
			$handles[$connection->id] = $connection->socket_handle;
		}
		return $handles;
	}

	public function isHandShacked(){
		return $this->hand_shook;
	}

	public function shakeHand(){
		$header_str = SocketHelper::readBuffer($this->socket_handle);
		$bytes = strlen($header_str);
		if($bytes === 0){
			throw new \Exception('hand shake error, empty data received');
		}

		$lines = preg_split("/\r\n/", $header_str);

		// check for valid http-header:
		if(!preg_match('/\AGET (\S+) HTTP\/1.1\z/', $lines[0], $matches)){
			throw new \Exception('hand shake error, Invalid request: '.$lines[0]);
		}

		// check for valid application:
		$path = $matches[1];

		// generate headers array:
		$headers = [];
		foreach($lines as $line){
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$response = "HTTP/1.1 101 Switching Protocols\r\n";
		$response .= "Upgrade: websocket\r\n";
		$response .= "Connection: Upgrade\r\n";
		$response .= "Sec-WebSocket-Accept: ".$secAccept."\r\n";
		if(isset($headers['Sec-WebSocket-Protocol']) && !empty($headers['Sec-WebSocket-Protocol'])){
			$response .= "Sec-WebSocket-Protocol: ".substr($path, 1)."\r\n";
		}
		$response .= "\r\n";
		try{
			return SocketHelper::writeBuffer($this->socket_handle, $response);
		}catch(\RuntimeException $e){
			return false;
		}
	}

	/**
	 * @param string $payload
	 * @param string $type
	 * @param bool $masked
	 * @return string
	 */
	public function encode($payload, $type, $masked = true){
		$frameHead = [];
		$payloadLength = strlen($payload);

		$frame_mask = SocketHelper::WEB_SOCKET_FRAME_TYPE_MAP[$type];
		if(!isset($frame_mask)){
			throw new \Exception('Frame type error:'.$type);
		}
		$frameHead[0] = $frame_mask;

		// set mask and payload length (using 1, 3 or 9 bytes)
		if($payloadLength > 65535){
			$payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
			$frameHead[1] = ($masked === true) ? 255 : 127;
			for($i = 0; $i < 8; $i++){
				$frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
			}
			// most significant bit MUST be 0 (close connection if frame too big)
			if($frameHead[2] > 127){
				$this->close(1004);
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
			$mask = [];
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

	public function decode($data){
		$unmaskedPayload = '';
		$decodedData = [];

		// estimate frame type:
		$firstByteBinary = sprintf('%08b', ord($data[0]));
		$secondByteBinary = sprintf('%08b', ord($data[1]));
		$op_code = bindec(substr($firstByteBinary, 4, 4));
		$isMasked = ($secondByteBinary[0] == '1') ? true : false;
		$payloadLength = ord($data[1])&127;

		// close connection if unmasked frame is received:
		if($isMasked === false){
			$this->close(1002);
		}

		$type = SocketHelper::WEB_SOCKET_FRAME_TYPE_MAP[$op_code];
		if(!$type){
			$this->close(1003);
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

		/**
		 * We have to check for large frames here. socket_recv cuts at 1024 bytes
		 * so if websocket-frame is > 1024 bytes we have to wait until whole
		 * data is transfered.
		 */
		if(strlen($data) < $dataLength){
			return [];
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

	/**
	 * Sends data to a client.
	 * @param string $payload
	 * @param int $type
	 * @param bool $masked
	 * @return bool
	 */
	public function send($payload, $type = SocketHelper::WEB_SOCKET_FRAME_TYPE_TEXT, $masked = false){
		$encodedData = $this->encode($payload, $type, $masked);
		return !!SocketHelper::writeBuffer($this->socket_handle, $encodedData);
	}

	/**
	 * close connection
	 * @param int $statusCode
	 */
	public function close($statusCode = 1000){
		$payload = str_split(sprintf('%016b', $statusCode), 8);
		$payload[0] = chr(bindec($payload[0]));
		$payload[1] = chr(bindec($payload[1]));
		$payload = implode('', $payload);

		switch($statusCode){
			case 1000:
				$payload .= 'normal closure';
				break;
			case 1001:
				$payload .= 'going away';
				break;
			case 1002:
				$payload .= 'protocol error';
				break;
			case 1003:
				$payload .= 'unknown data (opcode)';
				break;
			case 1004:
				$payload .= 'frame too large';
				break;
			case 1007:
				$payload .= 'utf8 expected';
				break;
			case 1008:
				$payload .= 'message violates server policy';
				break;
		}
		if($this->send($payload, 'close', false) === false){
			return;
		}
		Http::sendHttpStatus(400);
		stream_socket_shutdown($this->socket_handle, STREAM_SHUT_RDWR);
	}
}
