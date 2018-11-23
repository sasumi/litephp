<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/16
 * Time: 15:55
 */
namespace Lite\Logger\Handler;
use Lite\Logger\Message\AbstractMessage;

if(session_status() === PHP_SESSION_NONE){
	session_start();
}

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/16
 * Time: 10:15
 */

class DebugHandler extends AbstractHandler{
	private static $_SESSION_GUID = 'LOGGER_DEBUG_HANDLER_SESSION_GUID';

	public function write(array $messages){
		if(!$_SESSION[self::$_SESSION_GUID]){
			$_SESSION[self::$_SESSION_GUID] = array();
		}

		$data = array();
		/** @var AbstractMessage $msg */
		foreach($messages as $msg){
			$data[] = $msg->serialize();
		}
		$_SESSION[self::$_SESSION_GUID] = array_merge($_SESSION[self::$_SESSION_GUID], $data);
		session_write_close();
	}

	public function read($start, $count){
		$data = array_slice($_SESSION[self::$_SESSION_GUID], $start, $count);
		return $data;
	}

	public static function reset(){
		unset($_SESSION[self::$_SESSION_GUID]);
		session_write_close();
	}

	public static function output(){
		$data_list = $_SESSION[self::$_SESSION_GUID] ?: array();
		foreach($data_list as $msg){
			$data = json_decode($msg, true);
			print_r($data);
			echo str_repeat('-', 80)."\n";
		}
	}
}