<?php
/**
 * Lite杂项操作函数
 */
namespace Lite\func {

	/**
	 * 测试
	 **/
	function dump() {
		if(!headers_sent()) {
			header('Content-Type: text/html; charset=utf-8');
		}
		echo "\r\n\r\n" . '<pre style="background-color:#ddd; font-size:12px">' . "\r\n";
		$args = func_get_args();
		$last = array_slice($args, -1, 1);
		$die = $last[0] === 1;
		if($die) {
			$args = array_slice($args, 0, -1);
		}
		if($args) {
			foreach ($args as $arg) {
				var_dump($arg);
				echo str_repeat('-', 50) . "\n";
			}
		}
		$info = debug_backtrace();
		echo $info[0]['file'] . ' [' . $info[0]['line'] . "] \r\n</pre>";
		if($die) {
			die;
		}
	}

	function get_last_exit_trace() {
		declare(ticks = 1);
		$GLOBALS['___LAST_RUN___'] = null;
		register_tick_function(function () {
			$GLOBALS['___LAST_RUN___'] = debug_backtrace();
		});
		register_shutdown_function(function () {
			dump($GLOBALS['___LAST_RUN___'], 1);
		});
	}

	/**
	 * 时间打点标记，用于性能调试
	 * @param bool $return 是否返回结果
	 * @return string
	 */
	function time_mark($return = false) {
		$str = '';
		$tm = microtime(true);
		if(!$GLOBALS['__last_time_mark_time__']) {
			$GLOBALS['__init_time_mark_time__'] = $tm;
			$GLOBALS['__last_time_mark_time__'] = $tm;
			$str = "<PRE>";
		}

		list($info) = debug_backtrace();
		$offset = str_pad(number_format(($tm - $GLOBALS['__last_time_mark_time__']) * 1000, 2), 7, ' ', STR_PAD_LEFT);
		$start_offset = str_pad(number_format(($tm - $GLOBALS['__init_time_mark_time__']) * 1000, 2), 7, ' ', STR_PAD_LEFT);
		$GLOBALS['__last_time_mark_time__'] = $tm;
		$tm = str_pad($tm, 15, ' ', STR_PAD_RIGHT);
		$str .= "\n[{$offset}ms -{$start_offset}ms] $tm {$info['file']} #{$info['line']}\n";
		if($return) {
			return $str;
		}
		echo $str;
		return null;
	}

	/**
	 * check is function, string is excluded
	 * @param mixed $fun
	 * @return boolean
	 */
	function is_function($fun) {
		return is_callable($fun) && getType($fun) == 'object';
	}

	/**
	 * get ip
	 * @deprecated 请使用 Client::getIp()
	 * @return string
	 */
	function get_ip() {
		if(getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if(getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if(getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
			$ip = getenv("REMOTE_ADDR");
		else if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
			$ip = $_SERVER['REMOTE_ADDR'];
		else
			$ip = "unknown";
		return ($ip);
	}

	/**
	 * tick debug
	 * @param int $step_offset
	 * @param string $fun
	 */
	function tick_dump($step_offset = 1, $fun = 'dump') {
		$step_offset = (string)$step_offset;
		if(strstr($step_offset, ',') !== false) {
			list($start, $step) = array_map('intval', explode(',', $step_offset));
		} else {
			$step = intval($step_offset);
		}
		register_tick_function($fun);
		eval("declare(ticks = $step);");
	}

	/**
	 * pdog
	 * @param $fun
	 * @param $handler
	 */
	function pdog($fun, $handler) {
		declare(ticks = 1);
		register_tick_function(function () use ($fun, $handler) {
			$debug_list = debug_backtrace();
			foreach ($debug_list as $info) {
				if($info['function'] == $fun) {
					call_user_func($handler, $info['args']);
				}
			}
		});
	}

	/**
	 * get GUID
	 * @return mixed
	 */
	$GLOBALS['__guid__'] = 1;
	function guid() {
		return $GLOBALS['__guid__']++;
	}
}