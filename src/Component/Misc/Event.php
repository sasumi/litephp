<?php

namespace Lite\Component\Misc;

trait Event {
	private $event_hooks = [];
	private static $global_event_hooks = [];

	public function bindEvent($event, $handler){
		$this->event_hooks[$event][] = $handler;
		return true;
	}

	public static function __callStatic($method, $args){
		if($method === 'bindEvent'){
			self::$global_event_hooks[$args[0]] = $args[1];
		} else {
			throw new \Exception('Method no exists:'.$method);
		}
	}

	/**
	 * @param $event
	 * @param mixed ...$args
	 * @return bool|void
	 */
	public function fireEvent($event, ...$args){
		foreach($this->event_hooks[$event] ?: [] as $handler){
			if(call_user_func_array($handler, $args) === false){
				return false;
			}
		}
		foreach(self::$global_event_hooks[$event] ?: [] as $handler){
			if(call_user_func_array($handler, $args) === false){
				return false;
			}
		}
		return;
	}
}
