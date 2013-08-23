<?php

function pdog($fun, $handler){
	declare(ticks = 1);
	register_tick_function(function()use($fun, $handler){
		$debug_list = debug_backtrace();
		foreach($debug_list as $info){
			if($info['function'] == $fun){
				call_user_func($handler, $info['args']);
			}
		}
	});
}

function hello($a){
	echo $a;
}

pdog('hello', function($args){
	echo "<PRE>"; var_dump($args);
});

hello('a');
hello('b');
