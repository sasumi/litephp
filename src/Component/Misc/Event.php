<?php

namespace Lite\Component\Misc;

trait Event {
	private $event_hooks = [];
	private static $global_event_hooks = [];

	public function bindEvent($event, $handler){
		$this->event_hooks[$event][] = $handler;
		return true;
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
		return;
	}

	public static function bindEventGlobal($event, $handler){
		self::$global_event_hooks[$event][] = $handler;
		return true;
	}

	public static function fireEventGlobal($event, ...$args){
		foreach(self::$global_event_hooks[$event] ?: [] as $handler){
			if(call_user_func_array($handler, $args) === false){
				return false;
			}
		}
	}
}
