<?php
/**
 * Lite杂项操作函数
 */
namespace Lite\func;

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
