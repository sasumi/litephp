<?php
namespace Lite\Logger;

abstract class Level{
	/**
	 * Detailed debug information
	 */
	const DEBUG = 'DEBUG';

	/**
	 * Interesting events
	 * Examples: User logs in, SQL logs.
	 */
	const INFO = 'INFO';

	/**
	 * Uncommon events
	 */
	const NOTICE = 'NOTICE';

	/**
	 * Exceptional occurrences that are not errors
	 * Examples: Use of deprecated APIs, poor use of an API,
	 * undesirable things that are not necessarily wrong.
	 */
	const WARNING = 'WARNING';

	/**
	 * Runtime errors
	 */
	const ERROR = 'ERROR';

	/**
	 * Critical conditions
	 * Example: Application component unavailable, unexpected exception.
	 */
	const CRITICAL = 'CRITICAL';

	/**
	 * Action must be taken immediately
	 * Example: Entire website down, database unavailable, etc.
	 * This should trigger the SMS alerts and wake you up.
	 */
	const ALERT = 'ALERT';

	/**
	 * Urgent alert.
	 */
	const EMERGENCY = 'EMERGENCY';

	/**
	 * Level priority order map
	 */
	const LEVEL_PRIORITY_ORDER = [
		self::DEBUG,
		self::INFO,
		self::NOTICE,
		self::WARNING,
		self::ERROR,
		self::CRITICAL,
		self::ALERT,
		self::EMERGENCY,
	];

	/**
	 * Logging levels from SysLog protocol defined in RFC 5424
	 * @var array $levels Logging levels
	 */
	public static $levels = array(
		self::DEBUG     => 'DEBUG',
		self::INFO      => 'INFO',
		self::NOTICE    => 'NOTICE',
		self::WARNING   => 'WARNING',
		self::ERROR     => 'ERROR',
		self::CRITICAL  => 'CRITICAL',
		self::ALERT     => 'ALERT',
		self::EMERGENCY => 'EMERGENCY',
	);

	/**
	 * Get level above specified level(includ current)
	 * @param $start_level
	 * @return array
	 */
	public static function getLevelsAbove($start_level){
		$ret = array();
		$found = false;
		foreach(self::LEVEL_PRIORITY_ORDER as $lv){
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
	 * Supported color set for web view
	 * @var array fore-color & background-color
	 */
	const LOG_COLOR_SET = [
		self::DEBUG     => ['#fafafa', 'gray'],
		self::INFO      => ['#aaa', 'white'],
		self::NOTICE    => ['#437cb0', 'white'],
		self::WARNING   => ['#ffbd69', 'white'],
		self::ERROR     => ['#ff9799', 'white'],
		self::CRITICAL  => ['#ff5500', 'white'],
		self::ALERT     => ['red', 'white'],
		self::EMERGENCY => ['black', 'white'],
	];

	/**
	 * PHP error code mapping to Logger level
	 */
	const PHP_ERROR_MAPS = array(
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
