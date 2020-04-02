<?php
namespace Lite\toolkit;

use Lite\Core\Application;
use Lite\Core\Config;
use ReflectionMethod;
use function LFPhp\Func\glob_recursive;

ini_set('include_path', dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR);
include_once 'ttlib/Litephp/bootstrap.php';
include_once 'ttlib/temtop/autoload.php';

$namespace = '';
$file = Config::get('app/root').'config/access_auth.inc.php';

Application::init($namespace, dirname(__DIR__).'/', Application::MODE_CLI);

$path = str_replace('\\', '/', Config::get('app/path'));

$rule = $path.'controller/*.php';
$files = glob_recursive($rule);
$data = [];
const AUTH_KEY = '@auth';
foreach($files as $f){
	$f = str_replace('\\', '/', $f);
	$sub_class = preg_replace('/\.[^\.]*$/', '', str_ireplace($path, '', $f));
	$class = $namespace.'\\'.str_replace('/', '\\', $sub_class);
	
	include_once $f;
	$rc = new \ReflectionClass($class);
	$comment = $rc->getDocComment();
	$class_title = __parse_comment_title__($comment);
	$pub_methods = $rc->getMethods(ReflectionMethod::IS_PUBLIC);
	
	echo "\n\n\nProcessing Controller：$class";
	
	$controller = str_replace('controller/', '', $sub_class);
	$controller = str_replace('Controller', '', $controller);
	foreach($pub_methods as $pm){
		$mcm = $pm->getDocComment();
		$method_title = __parse_comment_title__($mcm);
		$method_title = preg_replace('/\(.*?$/', '', $method_title);
		$uri = $controller.'/'.$pm->name;
		echo "\nProcessing action: $pm->name, Comment:".($method_title ?: '-');
		if($method_title){
			$k = ($class_title ? $class_title.'/' : '').$method_title;
			echo "\nBuilding access key:$k";
			$data[$k][] = $uri;
		}
	}
}

$max_length = 0;
foreach($data as $key => $item){
	foreach($item as $k => $v){
		strlen($v)>$max_length and $max_length = strlen($v);
	}
}

$str = <<<EOF
<?php
return array(
EOF;

$access_list = include_once($file);

$nav_all_key_list = [];
foreach($data as $key => &$access_item){
	foreach($access_item as $k => $v){
		$v = lcfirst($v);
		$nav_all_key_list[] = $v;
		if(isset($access_list[$v])){
			unset($access_item[$k]);
		}
	}
}

ksort($access_list, SORT_ASC);

foreach($access_list as $i => $j){
	if(in_array($i, $nav_all_key_list)){
		$str .= "\t".'"'.lcfirst($i).'"'.str_pad('', $max_length-strlen($i), " ").' => "'.$j.'",'.PHP_EOL;
	} else{
		
		$str .= "\t".'//todo:已删除,请确认"'.lcfirst($i).str_pad('', $max_length-strlen($i), " ").'"'.' => "'.$j.'",'.PHP_EOL;
	}
}

$str .= PHP_EOL."\t/*******以下为新增(".date('Ymd H:i:s').")******/".PHP_EOL;

foreach($data as $key => $item){
	foreach($item as $k => $v){
		$v = lcfirst($v);
		if(isset($access_list[$v])){
			unset($item[$k]);
		}
		$str .= "\t".'"'.lcfirst($v).'"'.str_pad('', $max_length-strlen($v), " ").' => "'.$key.'",'.PHP_EOL;
	}
}
$str .= ');';
file_put_contents($file, $str);
echo "\n[DONE] !";

function __parse_comment_title__($comment){
	$reg = "/".preg_quote(AUTH_KEY).'([^\n]+)\n/';
	if(preg_match($reg, $comment, $matches)){
		return trim($matches[1]);
	}
	return null;
}
