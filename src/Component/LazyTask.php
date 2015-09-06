<?php
namespace Lite\Component;

class LazyTask {
	private static $instance_list;

	private function __construct($store_type){

	}

	public static function instance($store_type=null){
		if(!self::$instance_list[$store_type]){
			self::$instance_list[$store_type] = new self($store_type);
		}
		return self::$instance_list[$store_type];
	}

	public function addTask($callback, $timeout){

	}

	public function clear(){

	}
}