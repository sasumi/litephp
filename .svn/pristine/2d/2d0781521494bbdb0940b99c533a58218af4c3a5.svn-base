<?php
namespace Lite\Logger\Message;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/16
 * Time: 11:10
 */
class ArrayMessage extends AbstractMessage {
	private $id = '';
	private $data = array();

	public function __construct($msg, $data=array()){
		$this->data = $data;
		$this->setMessage($msg);
		$this->setTime();
	}

	public function identify(){
		return '[ARR_MSG]';
	}

	public function serialize(){
		return json_encode($this->data);
	}

	public function unSerialize($string){
		return new self(json_decode($string));
	}

	public function setData($data){
		$this->data = $data;
	}

	public function addData($p1, $value=null){
		if(is_string($p1)){
			$this->data[$p1] = $value;
		} else {
			$this->data = array_merge($this->data, $p1);
		}
	}

	public function getData(){
		return $this->data;
	}

	public function setIdentify($id){
		$this->id = $id;
	}

	public function getIdentify(){
		return $this->id;
	}
}