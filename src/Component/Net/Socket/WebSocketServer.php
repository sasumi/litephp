<?php

namespace Lite\Component\Net\Socket;

use Lite\Component\Misc\Event;

/**
 * Simple WebSocket server implementation in PHP.
 * $server = new WebSocketServer();
 * $server->listen($server::EVENT_ON_MESSAGE, function($message, $connection){
 *      echo $message;
 * });
 * $server->run();
 * $server->close();
 */
class WebSocketServer {
	use Event;
	const EVENT_ON_MESSAGE = 'ON_MESSAGE';

	/** @var resource $socket_server */
	private $socket_server;

	/** @var Connection[] $connections */
	private $connections = [];

	/**
	 * @param string $host
	 * @param int $port
	 */
	public function __construct($host = SocketHelper::LOCAL_IP, $port = 8000){
		ob_implicit_flush(1);
		$this->socket_server = SocketHelper::createServer($host, $port);
	}

	/**
	 * Main server loop.
	 * Listens for connections, handles connectes/disconnectes, e.g.
	 * @return void
	 */
	public function run(){
		while(true){
			$all_sockets = array_merge(Connection::getHandles($this->connections), [$this->socket_server]);
			@stream_select($all_sockets, $write = null, $except = null, 0, 5000);
			foreach($all_sockets as $connection_id => $socket_handle){
				if($socket_handle == $this->socket_server){
					if(($client_handle = stream_socket_accept($this->socket_server)) === false){
						$this->log('Socket error: '.socket_strerror(socket_last_error($client_handle)));
						continue;
					}//new client connected
					else{
						$connection = new Connection($this->socket_server, $client_handle);
						$this->connections[$connection->uuid] = $connection;
					}
				}else{
					$connection = $this->connections[$connection_id];
					if(!$connection->isHandShacked()){
						$connection->shakeHand();
					}else{
						$data = SocketHelper::readBuffer($socket_handle);
						$message = $connection->decode($data);
						$this->fireEvent(self::EVENT_ON_MESSAGE, $message, $connection);
					}
				}
			}
		}
	}

	/**
	 * Echos a message to standard output.
	 * @param string $message Message to display.
	 * @param string $type Type of message.
	 * @return void
	 */
	public function log($message, $type = 'info'){
		echo date('Y-m-d H:i:s').' ['.($type ? $type : 'error').'] '.$message.PHP_EOL;
	}
}
