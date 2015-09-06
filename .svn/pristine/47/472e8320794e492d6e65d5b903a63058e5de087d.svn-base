<?php
namespace Lite\Logger\Message;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/14
 * Time: 17:20
 */

interface InterfaceMessage {
	public function setLevel($level);
	public function getLevel();
	public function setTime();
	public function getTime();
	public function getMessage();
	public function setMessage($message);

	/**
	 * set identify
	 * @param string $id
	 * @return mixed
	 */
	public function setIdentify($id);
	public function getIdentify();
	public function serialize();
	public function unSerialize($string);
}