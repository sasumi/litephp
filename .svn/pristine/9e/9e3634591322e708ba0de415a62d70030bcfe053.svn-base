<?php
namespace Lite\Logger\Message;
use Lite\Logger\Logger;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/16
 * Time: 10:16
 */
class CommonMessage extends ArrayMessage {
	private $callee = array();
	private $ua = '';
	private $client_ip = '';
	private $request_host = '';
	private $request_uri = '';

	/**
	 * @return array
	 */
	public function getCallee(){
		return $this->callee;
	}

	/**
	 * @return string
	 */
	public function getClientIp(){
		return $this->client_ip;
	}

	/**
	 * @return string
	 */
	public function getRequestHost(){
		return $this->request_host;
	}

	/**
	 * @return string
	 */
	public function getRequestUri(){
		return $this->request_uri;
	}

	/**
	 * @return string
	 */
	public function getUa(){
		return $this->ua;
	}

	public function __construct($message, $data=null){
		parent::__construct($message, $data);
		$this->ua = $_SERVER['HTTP_USER_AGENT'];
		$this->client_ip = Logger::getIp();
		$this->request_host = $_SERVER['HTTP_HOST'];
		$this->request_uri = $_SERVER['REQUEST_URI'];
		$this->callee = Logger::getCallee(2);
	}
}