<?php
use Lite\Core\Config;

if(PHP_SAPI != 'cli'){
	throw new Exception('script run in CLI mode only');
}

include '../../bootstrap.php';
$argv = $_SERVER['argv'];
list($script, $cmd, $model_name) = $argv;
$SPEC = str_repeat(' ', 4);

$usage = <<<EOT
[Usage]
generate model prototype from database table...
$script table [model] [config] [-fc]
  [model] if no specified model name, use table name instead
  [config] use default config
  -f force overwrite exists model
  -c add comment from database comment

generate controller...
$script controller controller_name [-fc]
  -f force overwrite exists model
  -c add comment from database comment
EOT;

if(!$cmd || !$model_name){
	echo $usage;
	return;
}

switch($cmd){
	case 'table':
		$db_config = Config::get('db');

		break;

	case 'controller':
		break;
}

