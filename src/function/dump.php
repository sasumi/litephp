<?php
use function Lite\func\print_trace;

/**
 * 变量调试函数，并输出当前调试点所在位置
 * 用法：dump($var1, $var2, ..., 1)，当最后一个变量为1时，程序退出
 */
if(!function_exists('dump')){
	function dump(){
		$params = func_get_args();
		$cli = PHP_SAPI === 'cli';
		$exit = false;
		echo !$cli ? PHP_EOL.'<pre style="color:green;">'.PHP_EOL : PHP_EOL;

		if(count($params)){
			$tmp = $params;
			$exit = array_pop($tmp) === 1;
			$params = $exit ? array_slice($params, 0, -1) : $params;
			$comma = '';
			foreach($params as $var){
				echo $comma;
				var_dump($var);
				$comma = str_repeat('-',80).PHP_EOL;
			}
		}

		//remove closure calling & print out location.
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if($GLOBALS['DUMP_WITH_TRACE']){
			echo "[trace]",PHP_EOL;
			print_trace($trace, true, true);
		} else {
			print_trace([$trace[0]]);
		}
		echo str_repeat('=', 80), PHP_EOL, (!$cli ? '</pre>' : '');
		$exit && exit();
	}
}
