<?php
/**
 * Lite杂项操作函数
 */
namespace Lite\func {
	use Exception;

	//CLI前景色
	const CLI_FORE_COLOR_BLACK = '0;30';
	const CLI_FORE_COLOR_DARK_GRAY = '1;30';
	const CLI_FORE_COLOR_BLUE = '0;34';
	const CLI_FORE_COLOR_LIGHT_BLUE = '1;34';
	const CLI_FORE_COLOR_GREEN = '0;32';
	const CLI_FORE_COLOR_LIGHT_GREEN = '1;32';
	const CLI_FORE_COLOR_CYAN = '0;36';
	const CLI_FORE_COLOR_LIGHT_CYAN = '1;36';
	const CLI_FORE_COLOR_RED = '0;31';
	const CLI_FORE_COLOR_LIGHT_RED = '1;31';
	const CLI_FORE_COLOR_PURPLE = '0;35';
	const CLI_FORE_COLOR_LIGHT_PURPLE = '1;35';
	const CLI_FORE_COLOR_BROWN = '0;33';
	const CLI_FORE_COLOR_YELLOW = '1;33';
	const CLI_FORE_COLOR_LIGHT_GRAY = '0;37';
	const CLI_FORE_COLOR_WHITE = '1;37';

	//CLI背景色
	const CLI_BACK_COLOR_BLACK = '40';
	const CLI_BACK_COLOR_RED = '41';
	const CLI_BACK_COLOR_GREEN = '42';
	const CLI_BACK_COLOR_YELLOW = '43';
	const CLI_BACK_COLOR_BLUE = '44';
	const CLI_BACK_COLOR_MAGENTA = '45';
	const CLI_BACK_COLOR_CYAN = '46';
	const CLI_BACK_COLOR_LIGHT_GRAY = '47';

	/**
	 * 测试
	 **/
	function dump() {
		if(!headers_sent()) {
			header('Content-Type: text/html; charset=utf-8');
		}

		$params = func_get_args();
		$tmp = $params;
		$cli = PHP_SAPI == 'cli';

		//normal debug
		if(count($params)>0){
			$act = array_pop($tmp) === 1;
			$params = $act ? array_slice($params, 0, -1) : $params;
			echo $cli ? "\n" : '<pre style="font-size:12px; background-color:#eee; color:green; margin:0 0 10px 0; padding:0.5em; border-bottom:1px solid gray; width:100%; left:0; top:0">'."\n";
			$comma = '';
			foreach($params as $var){
				echo $comma;
				var_dump($var);
				$trace = debug_backtrace();
				echo "File:".($cli ? '' : '<b style="color:gray">').$trace[0]['file'].($cli ? '' : '</b><br/>')." Line: ".($cli ? '' : '<b>').$trace[0]['line'].($cli ? "\n" : '"</b><br/>"');
				$comma = $cli ? "\n" : '<div style="height:0px; line-height:1px; font-size:1px; border-bottom:1px solid white; border-top:1px solid #ccc; margin:10px 0"></div>';
			}
			echo $cli ? '' : '</pre>';
			if($act){
				die();
			}
		} //for tick debug
		else{
			if(++$GLOBALS['ONLY_FOR_DEBUG_INDEX']>=$GLOBALS['TICK_DEBUG_START_INDEX']){
				$trace = debug_backtrace();
				echo '<pre style="display:block; font-size:12px; color:green; padding:2px 0; border-bottom:1px solid #ddd; clear:both;">'.'['.($GLOBALS['ONLY_FOR_DEBUG_INDEX']).'] <b>'.$trace[0]['file'].'</b> line:'.$trace[0]['line'].'</pre>';
			}
		}
	}

	/**
	 * get cli console color output string
	 * @param $str
	 * @param null $foreground_color
	 * @param null $background_color
	 * @return string
	 */
	function getCliColorOutput($str, $foreground_color=null, $background_color=null){
		$color_str = '';
		if($foreground_color){
			$color_str .= "\033[".$foreground_color."m";
		}
		if($background_color){
			$color_str .= "\033[".$background_color."m";
		}
		if($color_str){
			return $str."\033[0m";
		}
		return $str;
	}

	/**
	 * error code to string
	 * @param $value
	 * @return string
	 */
	function error2string($value){
		$level_names = array(
			E_ERROR           => 'E_ERROR',
			E_WARNING         => 'E_WARNING',
			E_PARSE           => 'E_PARSE',
			E_NOTICE          => 'E_NOTICE',
			E_CORE_ERROR      => 'E_CORE_ERROR',
			E_CORE_WARNING    => 'E_CORE_WARNING',
			E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR      => 'E_USER_ERROR',
			E_USER_WARNING    => 'E_USER_WARNING',
			E_USER_NOTICE     => 'E_USER_NOTICE'
		);
		if(defined('E_STRICT')){
			$level_names[E_STRICT] = 'E_STRICT';
		}
		$levels = array();
		if(($value & E_ALL) == E_ALL){
			$levels[] = 'E_ALL';
			$value &= ~E_ALL;
		}
		foreach($level_names as $level => $name){
			if(($value & $level) == $level){
				$levels[] = $name;
			}
		}
		return implode(' | ', $levels);
	}

	/**
	 * string to error code
	 * @param $string
	 * @return int
	 */
	function string2error($string){
		$level_names = array(
			'E_ERROR',
			'E_WARNING',
			'E_PARSE',
			'E_NOTICE',
			'E_CORE_ERROR',
			'E_CORE_WARNING',
			'E_COMPILE_ERROR',
			'E_COMPILE_WARNING',
			'E_USER_ERROR',
			'E_USER_WARNING',
			'E_USER_NOTICE',
			'E_ALL'
		);
		if(defined('E_STRICT')){
			$level_names[] = 'E_STRICT';
		}
		$value = 0;
		$levels = explode('|', $string);

		foreach($levels as $level){
			$level = trim($level);
			if(defined($level)){
				$value |= (int)constant($level);
			}
		}
		return $value;
	}

	/**
	 * print system error & debug info
	 * @param $code
	 * @param $msg
	 * @param $file
	 * @param $line
	 * @param string $trace_string
	 */
	function print_sys_error($code, $msg, $file=null, $line=null, $trace_string=''){
		echo "<pre>";
		$code = error2string($code);
		echo "[$code] $msg\n\n";
		echo "* $file #$line\n\n";

		if(!$trace_string){
			$bs = debug_backtrace();
			array_shift($bs);
			foreach($bs as $k=>$b){
				echo count($bs)-$k." {$b['class']}{$b['type']}{$b['function']}\n";
				echo "  {$b['file']}  #{$b['line']} \n\n";
			}
		} else {
			echo $trace_string;
		}
		die;
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
	 * print exception
	 * @param Exception $ex
	 */
	function print_exception(Exception $ex){
		$data_str = '';
		if($ex instanceof \Lite\Exception\Exception){
			$data_str = json_encode($ex->data);
		}
		print_sys_error($ex->getCode(), $ex->getMessage()."\ndata:".$data_str, $ex->getFile(), $ex->getLine(), $ex->getTraceAsString());
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