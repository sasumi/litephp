<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/5/5
 * Time: 9:11
 */
$map = array(
	'UpYun'        => 'UpYun/UpYun.php',
	'MemcacheSASL' => 'MemcacheSASL/MemcacheSASL.php',
	'Hashids'      => 'Hashids/Hashids.php',
	'Parsedown'    => 'Parsedown/Parsedown.php',
);
spl_autoload_register(function($className) use ($map){
	if($map[$className]){
		include $map[$className];
	}
});