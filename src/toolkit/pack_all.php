<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/06/20
 * Time: 11:32
 */

define('DS', DIRECTORY_SEPARATOR);
$root = dirname(dirname(__DIR__));
$source_path = $root.DS.'src';
$runtime = $root.DS.'.runtime.php';
include $root.DS.'bootstrap.php';

//reset runtime file
file_put_contents($runtime, '');

$files = \LFPhp\Func\glob_recursive($source_path.DS.'*.php');
foreach($files as $file){
	$tail = str_replace($source_path.DS, '', $file);
	if(stripos($tail, 'toolkit') === 0){
		//ignore
	} else {
		$content = trim(file_get_contents($file));
		if(substr($content, -2, 2) != '?>'){
			$content .= ' ?>';
		}
		echo 'Appending file:'.$file."...\n";
		file_put_contents($runtime, $content, FILE_APPEND);
	}
}
//dump($files, 1);
