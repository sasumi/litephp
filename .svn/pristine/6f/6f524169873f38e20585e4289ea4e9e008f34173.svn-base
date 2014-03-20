<?php
class CResult {
	private $success;
	private $message;
	private $data;

	public function __construct($message, $success=false, $data=null){
		$this->data = $data;
		$this->success = $success;
		$this->message = $message;
	}

	/**
	 * check is success
	 * @return boolean
	 */
	public function isSuccess(){
		return (bool)$this->success;
	}

	/**
	 * get message
	 * @return string
	 */
	public function getMessage(){
		return $this->message;
	}

	/**
	 * get data
	 * @return mix
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * convert to standard hash map
	 * @return array
	 */
	public function toObject(){
		return array(
			'type' => $this->success,
			'message' => $this->message,
			'data' => $this->data
		);
	}

	/**
	 * auto transit response format
	 * @return string
	 */
	public function __toString(){
		if(Router::isPost()){
			return $this->toIframeResp();
		}
		return $this->toJsonp();
	}

	/**
	 * transform to jsonp
	 * @param string $callback
	 * @return string
	 */
	public function toJsonp($callback=null){
		$data = $this->toObject();
		return View::getJsonp($data, $callback);
	}

	/**
	 * transform to iframe call mode response
	 * @param string $callback
	 * @return string
	 */
	public function toIframeResp($callback=null){
		$data = $this->toObject();
		return View::getIframeResponse($data);
	}
}

?>