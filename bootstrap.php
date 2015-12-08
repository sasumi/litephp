<?php
//php version check
use function Lite\func\dump;

if(version_compare(PHP_VERSION, '5.5.0') < 0){
	throw new Exception("Required PHP 5.5 or above", 1);
}

!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
!defined('LITE_PATH') && define('LITE_PATH', __DIR__.DS.'src'.DS);
!defined('LITE_NS') && define('LITE_NS', 'Lite');

//include loader
require_once __DIR__.'/src/function/autoload.php';
require_once __DIR__.'/src/Logger/autoload.php';
require_once __DIR__.'/src/vendor/autoload.php';
require_once __DIR__.'/src/junk/autoload.php';

//注册自动加载库文件
spl_autoload_register(function($className){
	if(strpos($className, LITE_NS.'\\') === 0){
		$file = str_replace(LITE_NS.'\\', LITE_PATH, $className);
		$file = str_replace('\\', DS, $file);
		$file = $file.'.php';
		if(is_file($file)){
			require_once $file;
		}
	}
});