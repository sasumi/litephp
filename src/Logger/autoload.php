<?php
$flag = '__logger_load_flag__';
if($GLOBALS[$flag]){
	//return;
}
$GLOBALS[$flag] = true;

if(!defined('DS')){
	define('DS', DIRECTORY_SEPARATOR);
}

$NS = 'Lite\\Logger';

/**
 * 导入logger库文件
 * 根据命名空间规则，定义目录。
 * @param $class_path
 */
spl_autoload_register(function($class)use($NS){
	$class = strtolower($class);
	if(stripos($class, $NS.'\\') === 0){
		$file = str_ireplace($NS.'\\', '', $class);
		$file = str_ireplace('\\', DS, $file);
		$file = __DIR__.DS.$file.'.php';
		if(is_file($file)){
			include_once $file;
		}
	}
});