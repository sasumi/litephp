<?php
$GLOBALS['__APP_HOOKS__'] = array();

/**
 * 添加hook
 * @param string $key
 * @param string||array $callback
 * @return boolean
**/
function add_hook($key, $callback){
	$GLOBALS['__APP_HOOKS__'][$key] = $GLOBALS['__APP_HOOKS__'][$key] ?: array();
	$GLOBALS['__APP_HOOKS__'][$key][] = $callback;
	return true;
}

/**
 * 删除hook
 * @param $key
 * @return boolean
**/
function del_hook($key, $callback){
	if($GLOBALS['__APP_HOOKS__'][$key]){
		$rst = array();
		$found = false;
		foreach($GLOBALS['__APP_HOOKS__'][$key] as $item){
			if($item != $callback){
				$rst[] = $item;
			} else {
				$found = true;
			}
		}
		if($found){
			$GLOBALS['__APP_HOOKS__'][$key] = $rst;
			return true;
		}
	}
	return false;
}

/**
 * 检测是否有hook
 * @param string $key
 * @return boolean
 **/
function has_hook($key){
	return $GLOBALS['__APP_HOOKS__'][$key];
}

/**
 * 触发hook
 * @param string $key
 * @return array
**/
function fire_hook($key/** , $param1, $param2 **/){
	$args = array_slice(func_get_args(), 1) ?: array();
	$rsts = array();
	if($GLOBALS['__APP_HOOKS__'][$key]){
		foreach($GLOBALS['__APP_HOOKS__'][$key] as $item){
			$rsts[] = call_user_func_array($item, $args);
		}
	}
	return $rsts;
}