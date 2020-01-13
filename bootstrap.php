<?php
use Lite\I18N\Lang;

//php version limiting
if(version_compare(PHP_VERSION, '5.5.0') < 0){
	throw new Exception("Required PHP 5.5 or above", 1);
}

$LITE_PATH = __DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;
$NAMESPACE = 'Lite';

//include function loader
require_once __DIR__.'/src/function/autoload.php';

$runtime_source = __DIR__.'.runtime.php';
if(is_file($runtime_source)){
	include_once $runtime_source;
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

//绑定LitePHP翻译
if(function_exists('gettext')){
	Lang::addDomain(Lang::DOMAIN_LITEPHP, $LITE_PATH.'/I18N/litephp_lang', ['en_US', 'zh_CN'], 'en_US');
}
