<?php
namespace Lite\Core;
use Iterator as Iterator;

/**
 * Lite框架IO保护基础类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
abstract class IOGuard {
	const SP_HTML = 0x001;
	const SP_JS_VAR = 0x002;

	/**
	 * 保护变量HTML输出
	 * @param null $var
	 * @param $type
	 * @return array|object|string
	 */
	final public static function protect($var=null, $type){
		if(is_object($var)){
			return self::protectObject($var, $type);
		} else if(is_array($var)){
			return self::protectArray($var, $type);
		} else {
			return self::protectScalar($var, $type);
		}
	}

	/**
	 * 保护标量
	 * @param string $str
	 * @param int $type
	 * @return string
	 */
	final public static function protectScalar($str='', $type){
		if(!is_scalar($str)){
			return $str;
		}

		//deal
		return $str;
	}

	/**
	 * 保护数组
	 * @param array $arr
	 * @param int $type
	 * @return array
	 */
	final public static function protectArray(array $arr = array(), $type){
		$tmp = array();
		foreach($arr as $key=>$val){
			if(is_array($val)){
				$tmp[$key] = self::protectArray($val, $type);
			} else {
				$tmp[$key] = self::protectScalar($val, $type);
			}
		}
		return $tmp;
	}

	/**
	 * 保护对象
	 * @param string $obj
	 * @param int $type
	 * @return object
	 */
	final public static function protectObject($obj=null, $type){
		if(!($obj instanceof Iterator)){
			return $obj;
		}
		$tmp = clone($obj);
		foreach($tmp as $key=>$val){
			if(is_object($val)){
				$tmp[$key] = self::protectObject($val, $type);
			} else {
				$tmp[$key] = self::protect($val, $type);
			}
		}
		return $tmp;
	}
}