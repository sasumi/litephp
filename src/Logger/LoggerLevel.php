<?php
namespace Lite\Logger;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/14
 * Time: 17:51
 */
abstract class LoggerLevel {
	/**
	 * Detailed debug information
	 */
	const DEBUG = 100;

	/**
	 * Interesting events
	 *
	 * Examples: User logs in, SQL logs.
	 */
	const INFO = 200;

	/**
	 * Uncommon events
	 */
	const NOTICE = 250;

	/**
	 * Exceptional occurrences that are not errors
	 *
	 * Examples: Use of deprecated APIs, poor use of an API,
	 * undesirable things that are not necessarily wrong.
	 */
	const WARNING = 300;

	/**
	 * Runtime errors
	 */
	const ERROR = 400;

	/**
	 * Critical conditions
	 *
	 * Example: Application component unavailable, unexpected exception.
	 */
	const CRITICAL = 500;

	/**
	 * Action must be taken immediately
	 *
	 * Example: Entire website down, database unavailable, etc.
	 * This should trigger the SMS alerts and wake you up.
	 */
	const ALERT = 550;

	/**
	 * Urgent alert.
	 */
	const EMERGENCY = 600;

	/**
	 * Logging levels from SysLog protocol defined in RFC 5424
	 *
	 * @var array $levels Logging levels
	 */
	public static $levels = array(
		self::DEBUG => 'DEBUG',
		self::INFO => 'INFO',
		self::NOTICE => 'NOTICE',
		self::WARNING => 'WARNING',
		self::ERROR => 'ERROR',
		self::CRITICAL => 'CRITICAL',
		self::ALERT => 'ALERT',
		self::EMERGENCY => 'EMERGENCY',
	);

	/**
	 * get level above specified level(included)
	 * @param $start_level
	 * @return array
	 */
	public static function getLevelAbove($start_level){
		$ret = array();
		$found = false;
		foreach(self::$levels as $lv => $_){
			if($lv == $start_level){
				$found = true;
			}
			if($found){
				$ret[] = $lv;
			}
		}
		return $ret;
	}

	/**
	 * supported color set for web view
	 * @var array fore-color & background-color
	 */
	public static $log_color_set = array(
		self::DEBUG => array('#fafafa', 'gray'),
		self::INFO => array('#aaa', 'white'),
		self::NOTICE => array('#437cb0', 'white'),
		self::WARNING => array('#ffbd69', 'white'),
		self::ERROR => array('#ff9799', 'white'),
		self::CRITICAL => array('#ff5500', 'white'),
		self::ALERT => array('red', 'white'),
		self::EMERGENCY => array('black', 'white'),
	);

	/**
	 * php error description map
	 * @var array
	 */
	public static $php_error_code = array(
		E_ERROR =>'E_ERROR',
		E_WARNING =>'E_WARNING',
		E_PARSE =>'E_PARSE',
		E_NOTICE =>'E_NOTICE',
		E_CORE_ERROR =>'E_CORE_ERROR',
		E_CORE_WARNING =>'E_CORE_WARNING',
		E_COMPILE_ERROR =>'E_COMPILE_ERROR',
		E_COMPILE_WARNING =>'E_COMPILE_WARNING',
		E_USER_ERROR =>'E_USER_ERROR',
		E_USER_WARNING =>'E_USER_WARNING',
		E_USER_NOTICE =>'E_USER_NOTICE',
		E_STRICT =>'E_STRICT',
		E_RECOVERABLE_ERROR =>'E_RECOVERABLE_ERROR',
		E_DEPRECATED =>'E_DEPRECATED',
		E_USER_DEPRECATED =>'E_USER_DEPRECATED',
	);

	/**
	 * php error code map
	 * @var array
	 */
	public static $php_error_map = array(
        E_ERROR             => self::CRITICAL,
        E_WARNING           => self::WARNING,
        E_PARSE             => self::ALERT,
        E_NOTICE            => self::NOTICE,
        E_CORE_ERROR        => self::CRITICAL,
        E_CORE_WARNING      => self::WARNING,
        E_COMPILE_ERROR     => self::ALERT,
        E_COMPILE_WARNING   => self::WARNING,
        E_USER_ERROR        => self::ERROR,
        E_USER_WARNING      => self::WARNING,
        E_USER_NOTICE       => self::NOTICE,
        E_STRICT            => self::NOTICE,
        E_RECOVERABLE_ERROR => self::ERROR,
        E_DEPRECATED        => self::NOTICE,
        E_USER_DEPRECATED   => self::NOTICE,
	);
}