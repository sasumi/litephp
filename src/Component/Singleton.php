<?php
namespace Lite\Component;
use ReflectionClass;

/**
 * User: Administrator
 * Date: 2019/03/17
 * Time: 23:34
 */
trait Singleton {
	private function __construct(){}

	public function instance(...$args){
		static $instance;
		if(!$instance){
			$ref = new ReflectionClass(self::class);
			$instance = $ref->newInstanceArgs($args);
		}
		return $instance;
	}
}