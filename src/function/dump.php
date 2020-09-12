<?php
namespace Lite\Func;

/**
 * 变量调试函数，并输出当前调试点所在位置
 * 用法：dump($var1, $var2, ..., 1)，当最后一个变量为1时，程序退出
 */

/**
 * 获取项目应用最后调用信息（去除LitePHP框架调用信息）
 * @param int $max_depth
 * @return array
 */
function get_last_project_trace($max_depth = 20){
	static $lite_root;
	if(!$lite_root){
		$lite_root = dirname(dirname(__DIR__));
	}
	$traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $max_depth);
	foreach($traces as $trace){
		if($trace['file'] && stripos($trace['file'], $lite_root) === false){
			return $trace;
		}
	}
	return [];
}
