<?php
if(PHP_SAPI != 'cli'){
	throw new Exception('script run in CLI mode only');
}

include '../../bootstrap.php';
$argv = $_SERVER['argv'];
list($script, $cmd) = $argv;
$argv = array_slice($argv,2);
$args = array();
$opts = array();
foreach($argv as $item){
	if($item[0] == '-'){
		for($i=1; $i<strlen($item); $i++){
			$opts[$item[$i]] = true;
		}
	} else {
		$args[] = $item;
	}
}

$usage = <<<EOT
[Usage]
generate model prototype from database table...
$script model table_name model_name [config] [-fc]
  table_name database table name
  model_name model name
  [config] use default config
  -f force overwrite exists model
  -c add comment from database comment

generate controller...
$script controller controller_name [-fc]
  -f force overwrite exists model
  -c add comment from database comment
EOT;

if(!$cmd){
	echo $usage;
	return;
}

$app_path = __DIR__.DIRECTORY_SEPARATOR;
$model_path = $app_path.'protected/Model/';

switch($cmd){
	case 'table':
		list($table, $model_name, $db_config) = $args;
		$model_file = $model_path.$model_name.'.php';
		if(is_file($model_file) && !$opts['f']){
			return;
		}

		$db_config = $db_config ?: 'db';
		$config = include $app_path."protected/config/{$db_config}.inc.php";
		if(!$config){
			throw new Exception('no db config found');
		}
		$conn = mysqli_connect($config['host'], $config['user'], $config['password']);
		mysqli_select_db($conn, $config['database']);
		if($config['charset']){
			mysqli_query($conn, 'SET NAMES '.$config['charset']);
		}
		$res = mysqli_query($conn, 'DESC user');
		$rows = array();
		while($tmp = mysqli_fetch_assoc($res)){
			$rows[] = $tmp;
		}
		$str = generate_table($table, $model_name, $rows);
		file_put_contents($model_file, $str);
		break;

	case 'controller':
		break;
}


function generate_table($table, $model, $fields){
	return '';
}

