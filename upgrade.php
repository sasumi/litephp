<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/5/7
 * Time: 17:06
 */

define('DS', DIRECTORY_SEPARATOR);

$root = dirname($_SERVER['SCRIPT_FILENAME']);
$dir = $root.DS.'protected'.DS;
$home = $root.DS;
$ns = basename($root);


//index.php
$con = file_get_contents($home.'index.php');
$con = replace_common($con);
$con = str_replace('<?php', "<?php\nnamespace $ns;\n", $con);
$con = str_replace('litephp/boot.inc.php', 'litephp2/bootstrap.php', $con);
$con = preg_replace('/Application::init\(([^\)]*)\)/','Application::init(__NAMESPACE__)', $con);
file_put_contents($home.'index.php', $con);

//controller file
$fs = glob($dir.'controller'.DS.'*.class.php');
foreach($fs as $f){
	$con = file_get_contents($f);

	preg_match('/(\W)class\s+Controller_([\w]+)(\W)/i', $con, $matches);
	$ctrl = $matches[2];
	if(!$ctrl){
		continue;
	}

	//add ns
	$con = preg_replace('/\<\?php/', "<?php\nnamespace {$ns}\\Controller;\n", $con);
	$con = replace_common($con);

	$new_file = dirname($f).DS.$ctrl.'Controller.php';
	file_put_contents($new_file, $con);
	unlink($f);
}

//model file
$fs = glob($dir.'model'.DS.'*.class.php');
foreach($fs as $f){
	$con = file_get_contents($f);

	$model = '';
	preg_match('/(\W)class\s+Model_([\w]+)(\W)/i', $con, $matches);
	$model = $matches[2];
	if(!$model){
		continue;
	}

	//add ns
	$con = preg_replace('/\<\?php/', "<?php\nnamespace {$ns}\\Model;\n", $con);
	$con = replace_common($con);

	$new_file = dirname($f).DS.$model.'.php';
	file_put_contents($new_file, $con);
	unlink($f);
}

//rename fold
rename($dir.'controller', $dir.'Controller');
rename($dir.'model', $dir.'Model');


replace_common_dir($dir.'config'.DS);
replace_common_dir($dir.'template'.DS);

echo "\n---------- d o n e ----------";


function replace_common_file($file){
	if(is_file($file)){
		$con = file_get_contents($file);
		$con = replace_common($con);
		file_put_contents($file, $con);
	}
}

function replace_common_dir($dir){
	$fs = glob_recursive($dir.'*.php');
	foreach($fs as $f){
		replace_common_file($f);
	}
}

/**
 * 递归的glob
 * Does not support flag GLOB_BRACE
 * @param $pattern
 * @param int $flags
 * @return array
 */
function glob_recursive($pattern, $flags = 0) {
	$files = glob($pattern, $flags);
	foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
		$files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
	}

	//修正目录分隔符
	array_walk($files, function (&$file) {
		$file = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $file);
	});
	return $files;
}

function replace_common($con){
	$con = str_replace('LITE', 'Lite', $con);

	//replace incldue Controller name
	$con = preg_replace_callback('/(\W)Controller_([\w]+)(\W)/i', function($arg){
		$c = $arg[2];
		return $arg[1]."{$c}Controller".$arg[3];
	}, $con);

	//replace library ns;
	$con = str_replace(array(
		'Lite\\CRUD',
		'Lite\\DB_Query',
		'Lite\\DB_Model',
		'Lite\\DB_Record',
		'Lite\\Hooker',
		'Lite\\Paginate',
		'Lite\\Config',
		'Lite\\Result',
		'Lite\\Application',
		'Lite\\Router',
		'Lite\\Calendar',
		'Lite\\Request',
		'Lite\\View'
	), array(
		'Lite\\Core\\CRUD',
		'Lite\\DB\\Query',
		'Lite\\DB\\Model',
		'Lite\\DB\\Record',
		'Lite\\Core\\Hooker',
		'Lite\\Component\\Paginate',
		'Lite\\Core\\Config',
		'Lite\\Core\\Result',
		'Lite\\Core\\Application',
		'Lite\\Core\\Router',
		'Lite\\Component\\Calendar',
		'Lite\\Component\\Request',
		'Lite\\Core\\View'
	), $con);

	//replace library class name
	$con = str_replace(array(
		'DB_Query',
		'DB_Record',
		'DB_Model',
		'Model_'
	), array(
		'Query',
		'Record',
		'Model',
		'',
	), $con);
	return $con;
}

