<?php
namespace Lite\Logger\Message;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/15
 * Time: 9:20
 */
class StringMessage extends AbstractMessage{
	private $id = '';
	private $string;

	public function __construct($string){
		$this->string = $string;
	}

	public function identify(){
		return '[s]';
	}

	public function serialize(){
		return $this->string;
	}

	public function unSerialize($string){
		return new self($string);
	}

	public function setIdentify($id){
		$this->id = $id;
	}

	public function getIdentify(){
		return $this->id;
	}
}