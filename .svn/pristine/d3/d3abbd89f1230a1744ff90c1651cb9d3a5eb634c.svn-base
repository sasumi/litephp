<?php
namespace Lite\Logger\Message;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/15
 * Time: 13:25
 */
Abstract class AbstractMessage implements InterfaceMessage{
	private $level;
	private $message = '';
	private $time;

	public function setLevel($level){
		$this->level = $level;
	}

	public function getLevel(){
		return $this->level;
	}

	public function getTime(){
		return $this->time;
	}

	public function setTime($time=null){
		$this->time = $time ?: time();
	}

	public function getMessage(){
		return $this->message;
	}

	public function setMessage($message){
		$this->message = $message;
	}
}