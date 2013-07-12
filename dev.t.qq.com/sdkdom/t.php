<?php
define('CURRENT_PATH',dirname(__FILE__));
define('TW_PATH', CURRENT_PATH.'/zh_tw/');
define('EN_PATH', CURRENT_PATH.'/en/');
define('CN_PATH', CURRENT_PATH.'/zh_cn/');

/*
 * Init Language Package
 */
$langs = getlanguage();
function init_language(){
	switch($langs){
		case 'zh':
			$dirpath = CN_PATH;
			break;
		case 'tw':
			$dirpath = TW_PATH;
			break;
		default:
			$dirpath = CN_PATH;
			break;
	}
	$filesDirs = scandir($dirpath);
	$GLOBALS['language_resources'] = array();
	foreach ($filesDirs as $file) {
		$extend = pathinfo($file);
		$extend = strtolower($extend["extension"]);
		if($file == '.' || $file == '..' || $extend != 'php'){
			continue;
		}
		include_once($dirpath.$file);
	}
}

function getlanguage(){
	if($_SERVER['HTTP_ACCEPT_LANGUAGE']){
		$langs =  $_SERVER['HTTP_ACCEPT_LANGUAGE'];	
		$l = substr($langs,0,2);
		$tl = substr($langs,3,2);
		if($l != 'zh'){
			$l = 'zh';
		}
		if($tl == 'tw' || $tl == 'hk' || $tl =='TW' || $tl =='HK' || $tl == 'MO' || $tl == 'mo' || $tl == 'Hant'){
			$l = 'tw';	
		}
		return $l;
	}else if($_SERVER['HTTP_USER_AGENT']){
		$agent = explode(';',$_SERVER['HTTP_USER_AGENT']);	
		foreach($agent as $b){
			$t = explode('-',$b);
			if(count($t) == 2){
				$l = $t[0];
				$tl = substr($t[1],0,2);
				if($l != 'zh'){
					$l = 'zh';
				}
				if($tl == 'tw' || $tl == 'hk' || $tl =='TW' || $tl =='HK' || $tl == 'MO' || $tl == 'mo' || $tl == 'Hant'){
					$l = 'tw';	
				}
				return $l;
			}
		}
	}else{
		return 'zh';
	}
}

/*
 * Language Translation
 */
function __($key){
	return $GLOBALS['language_resources'][$key] ? $GLOBALS['language_resources'][$key] : $key;
}
?>
