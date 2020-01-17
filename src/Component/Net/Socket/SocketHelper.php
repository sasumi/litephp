<?php
namespace Lite\Component\Net\Socket;

class SocketHelper {
	const LOCAL_IP = '127.0.0.1';

	CONST WEB_SOCKET_FRAME_TYPE_TEXT = 129;
	CONST WEB_SOCKET_FRAME_TYPE_BINARY = 2;
	CONST WEB_SOCKET_FRAME_TYPE_CLOSE = 136;
	CONST WEB_SOCKET_FRAME_TYPE_PING = 137;
	CONST WEB_SOCKET_FRAME_TYPE_PONG = 138;

	const WEB_SOCKET_FRAME_TYPE_MAP = [
		self::WEB_SOCKET_FRAME_TYPE_TEXT   => 'TEXT',
		self::WEB_SOCKET_FRAME_TYPE_BINARY => 'BINARY',
		self::WEB_SOCKET_FRAME_TYPE_CLOSE  => 'CLOSE',
		self::WEB_SOCKET_FRAME_TYPE_PING   => 'PING',
		self::WEB_SOCKET_FRAME_TYPE_PONG   => 'PONG',
	];

	/**
	 * Create a socket on given host/port
	 * @param string $host The host/bind address to use
	 * @param int $port The actual port to bind on
	 * @return false|resource
	 * @throws \RuntimeException
	 */
	public static function createServer($host, $port){
		$protocol = 'tcp://';
		$url = $protocol.$host.':'.$port;
		$context = stream_context_create();
		$socket_server = stream_socket_server($url, $errno, $err, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);
		if($socket_server === false){
			throw new \RuntimeException('Error creating socket: '.$err);
		}
		return $socket_server;
	}

	/**
	 * get socket client ip and port info
	 * @param $socket_handle
	 * @return array
	 */
	public static function getSocketClientIpPort($socket_handle){
		$socket_name = stream_socket_get_name($socket_handle, true);
		list($ip, $port) = explode(':', $socket_name);
		return [$ip, intval($port)];
	}

	/**
	 * Reads from stream.
	 * @param resource $socket_handler
	 * @return string
	 * @throws \RuntimeException
	 */
	public static function readBuffer($socket_handler){
		$buffer = '';
		$buff_size = 8192;
		$metadata['unread_bytes'] = 0;
		do{
			if(feof($socket_handler)){
				throw new \RuntimeException('Could not read from stream.');
			}
			$result = fread($socket_handler, $buff_size);
			if($result === false || feof($socket_handler)){
				throw new \RuntimeException('Could not read from stream.');
			}
			$buffer .= $result;
			$metadata = stream_get_meta_data($socket_handler);
			$buff_size = ($metadata['unread_bytes'] > $buff_size) ? $buff_size : $metadata['unread_bytes'];
		} while($metadata['unread_bytes'] > 0);
		return $buffer;
	}

	public static function resolveDomain($domain){
		$domain = str_replace('http://', '', $domain);
		$domain = str_replace('https://', '', $domain);
		$domain = str_replace('www.', '', $domain);
		$domain = str_replace('/', '', $domain);
		return $domain;
	}

	/**
	 * Write to stream.
	 * @param $socket_handler
	 * @param string $str
	 * @return int
	 */
	public static function writeBuffer($str, $socket_handler){
		$str_len = strlen($str);
		if($str_len === 0){
			return 0;
		}
		for($written = 0; $written < $str_len; $written += $write_len){
			$write_len = @fwrite($socket_handler, substr($str, $written));
			if($write_len === false){
				throw new \RuntimeException('Could not write to stream.');
			}
			if($write_len === 0){
				throw new \RuntimeException('Could not write content to stream.');
			}
		}
		return $written;
	}
}
