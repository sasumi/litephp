<?php
namespace Lite\Core;

/**
 * Lite框架事件触发器
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class Hooker {
	private static $HOOKS = array();
	private function __construct(){}

	/**
	 * 添加触发器
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
	 * 删除触发器
	 * @param $key
	 * @param $callback
	 * @return bool
	 */
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
	 * 检测触发器是否存在
	 * @param string $key
	 * @return boolean
	 **/
	public static function exists($key){
		return self::$HOOKS[$key] ? count(self::$HOOKS[$key]) : false;
	}

	/**
	 * 触发事件
	 * @param string $key
	 * @return array|false
	 **/
	public static function fire($key/** , $param1, $param2 **/){
		$args = array_slice(func_get_args(), 1) ?: array();
		$returns = array();
		if(self::$HOOKS[$key]){
			foreach(self::$HOOKS[$key] as $item){
				$result = call_user_func_array($item, $args);
				if($result === false){
					return false;
				}
				$returns[] = $result;
			}
		}
		return $returns;
	}
}