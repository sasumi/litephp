<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/9/2
 * Time: 11:53
 */
namespace Lite\DB\Meta;

class Field {
	const TYPE_STRING = 1;
	const TYPE_INT = 2;
	const TYPE_FLOAT = 3;
	const TYPE_DOUBLE = 4;
	const TYPE_BOOL = 5;
	const TYPE_ENUM = 6;
	const TYPE_DATE = 7;
	const TYPE_DATETIME = 8;
	const TYPE_TIME = 9;
	const TYPE_TIMESTAMP = 10;

	public $name;
	public $alias;
	public $type;
	public $length;
	public $default;

	public function __construct($definitions){
		foreach($definitions as $k=>$def){
			if(isset($this[$k])){
				$this[$k] = $def;
			}
		}
	}
}