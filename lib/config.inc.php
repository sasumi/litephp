<?php
/**
 * lite php default configurations
 */
if(!defined('DS')){
	define('DS', DIRECTORY_SEPARATOR);
}

//application path
if(!defined('APP_PATH')){
	define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']).DS);
}

//page module file path
if(!defined('PAGE_PATH')){
	define('PAGE_PATH', APP_PATH);
}

//template path
if(!defined('TPL_PATH')){
	define('TPL_PATH', APP_PATH.'template'.DS);
}

//config path
if(!defined('CONFIG_PATH')){
	define('CONFIG_PATH', APP_PATH.'config'.DS);
}

//include path
if(!defined('INCLUDE_PATH')){
	define('INCLUDE_PATH', APP_PATH.'include'.DS);
}

//library path(litephp path)
if(!defined('LIB_PATH')){
	define('LIB_PATH', __DIR__.DS);
}

//资源URL初始化
if(!defined('APP_URL')){
	define('APP_URL', '/');
}
if(!defined('STATIC_URL')){
	define('STATIC_URL', APP_URL.'static/');
}
if(!defined('JS_URL')){
	define('JS_URL', STATIC_URL.'js/');
}
if(!defined('IMG_URL')){
	define('IMG_URL', STATIC_URL.'img/');
}
if(!defined('CSS_URL')){
	define('CSS_URL', STATIC_URL.'css/');
}
if(!defined('FLASH_URL')){
	define('FLASH_URL', STATIC_URL.'flash/');
}

//路由配置初始化
if(!defined('ROUTE_MODE')){
	define('ROUTE_MODE', 'PATH');
}
if(!defined('ROUTE_ACTION_KEY')){
	define('ROUTE_ACTION_KEY', 'act');
}
if(!defined('ROUTE_DEFAULT_PAGE')){
	define('ROUTE_DEFAULT_PAGE', 'index');
}
if(!defined('ROUTE_DEFAUL_ACTION')){
	define('ROUTE_DEFAUL_ACTION', 'index');
}