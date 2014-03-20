<?php
abstract class Hooker {
	private static $HOOKS = array();
	private function __construct(){}

	/**
	 * 添加hook
	 * @param string $key
	 * @param string||array $callback
	 * @return boolean
	 **/
	public static function add($key, $callback){
		self::$HOOKS[$key] = self::$HOOKS[$key] ?: array();
		self::$HOOKS[$key][] = $callback;
		return true;
	}

	/**
	 * 删除hook
	 * @param $key
	 * @return boolean
	 **/
	public static function delete($key, $callback){
		if(self::$HOOKS[$key]){
			$rst = array();
			$found = false;
			foreach(self::$HOOKS[$key] as $item){
				if($item != $callback){
					$rst[] = $item;
				} else {
					$found = true;
				}
			}
			if($found){
				self::$HOOKS[$key] = $rst;
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
	public static function exists($key){
		return self::$HOOKS[$key] ? count(self::$HOOKS[$key]) : false;
	}

	/**
	 * 触发hook
	 * @param string $key
	 * @return array
	 **/
	public static function fire($key/** , $param1, $param2 **/){
		$args = array_slice(func_get_args(), 1) ?: array();
		$rsts = array();
		if(self::$HOOKS[$key]){
			foreach(self::$HOOKS[$key] as $item){
				$rsts[] = call_user_func_array($item, $args);
			}
		}
		return $rsts;
	}
}