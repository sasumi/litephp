<?php
//php version limiting
if(version_compare(PHP_VERSION, '5.5.0')<0){
	throw new \Exception("Required PHP 5.5 or above", 1);
}

$LITE_PATH = __DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;
$NAMESPACE = 'Lite';

//include function loader
require_once __DIR__.'/src/function/autoload.php';

//dump function shortcut
if(!function_exists('dump')){
	function dump(){
		return call_user_func_array('Lite\func\dump', func_get_args());
	}
}

//注册自动加载库文件
spl_autoload_register(function($className) use ($LITE_PATH, $NAMESPACE){
	if(strpos($className, $NAMESPACE.'\\') === 0){
		$file = str_replace($NAMESPACE.'\\', $LITE_PATH, $className);
		$file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
		$file = $file.'.php';
		if(is_file($file)){
			require_once $file;
		}
	}
});