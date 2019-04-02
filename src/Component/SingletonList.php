<?php
namespace Lite\Component;
use ReflectionClass;

/**
 * User: Administrator
 * Date: 2019/03/17
 * Time: 23:34
 */
trait SingletonList {
	private function __construct(){}

	public function instance(...$args){
		static $instance_list;
		$key = serialize($args);
		if(!isset($instance_list[$key])){
			$ref = new ReflectionClass(self::class);
			$instance_list[$key] = $ref->newInstanceArgs($args);
		}
		return $instance_list[$key];
	}
}