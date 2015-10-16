<?php
namespace Lite\Cli;
use \PDO;

if(PHP_SAPI != 'cli'){
	throw new \Exception('script run in CLI mode only');
}

define('PROJECT_ROOT', get_project_dir());
define('PROJECT_PROTECTED_DIR', PROJECT_ROOT.'/protected');

class CodeGenerator {
	public static function load(){

	}
}

$args = $_SERVER['argv'];
$script_file = array_shift($args);
$cmd = array_shift($args);
$overwrite = false;

if(stripos($cmd, '-') === 0){
	$overwrite = stripos($cmd, 'o') !== false;
	$cmd = array_shift($args);
}

$help = <<<EOT
=======================================================
<<STATEMENT LIST>>
option: -o overwrite file

generate model file
php $script_file [-o] model table_name model_name

generate all model file
php $script_file [-o] model allmodel

generate specified database table:
php $script_file [-o] table table_name

generate all tables:
php $script_file [-o] alltable
=======================================================
EOT;

switch($cmd){
	case 'model':
		generate_model($args[0], $args[1], $overwrite);
		break;

	case 'allmodel':
		$tables = get_all_table();
		foreach($tables as $item){
			$table = array_pop($item);
			generate_model($table, null, $overwrite);
		}
		echo "ALL MODEL GENERATED\n";
		break;

	case 'table':
		$table = $args[0];
		generate_table($table, $overwrite);
		break;

	case 'alltable':
		$tables = get_all_table();
		foreach($tables as $item){
			$table = array_pop($item);
			generate_table($table, $overwrite);
		}
		echo "ALL TABLE GENERATED\n";
		break;

	default:
		echo $help;
}

function generate_model($table_name, $model_name='', $overwrite){
	generate_table($table_name, $overwrite);
	$model_name = $model_name ?: convert_class_name($table_name);
	$table_model = 'Table'.convert_class_name($table_name);

	$ns = get_ns();
	$fold = PROJECT_PROTECTED_DIR.'/model/';
	$file = $fold.$model_name.'.php';

	if(!$overwrite && is_file($file)){
		echo "[IGNORE] model file exists: $file\n";
		return;
	}

	$str = parser_tpl(file_get_contents(__DIR__.'/model.tpl'), array(
		'namespace' => $ns."\\model",
		'generate_date' => date('Y-m-d'),
		'generate_time' => date('H:i:s'),
		'table_model' => $table_model,
		'model_name' => $model_name,
	));

	$update = is_file($file);
	file_put_contents($file, $str);
	echo "[DONE] model ".($update ? 'updated':'created')." >> $file -- $model_name\n";
}

function get_all_table(){
	$conn = get_db_conn();
	$st = $conn->prepare('SHOW TABLES');
	$st->execute();
	$ret = array();
	while($r = $st->fetch(PDO::FETCH_ASSOC)){
		$ret[] = $r;
	}
	return $ret;
}

function generate_table($table, $overwrite){
	$class_name = 'Table'.convert_class_name($table);
	$fold = PROJECT_PROTECTED_DIR.'/model/table/';
	if(!is_dir($fold)){
		mkdir($fold);
	}
	$file = $fold.$class_name.'.php';
	if(!$overwrite && is_file($file)){
		echo "[IGNORE] table file exists: $file\n";
		return;
	}

	$meta_list = get_table_meta($table);
	$filter_rules = get_row_filter_rule($meta_list);
	$pk = get_pk($meta_list);
	$comment = get_class_comment($class_name, $meta_list);
	$ns = get_ns();

	$str = parser_tpl(file_get_contents(__DIR__.'/table.tpl'), array(
		'namespace' => $ns."\\model\\table",
		'generate_date' => date('Y-m-d'),
		'generate_time' => date('H:i:s'),
		'table_name' => $table,
		'class_comment' => $comment,
		'class_name' => $class_name,
		'primary_key' => $pk,
		'filter_rules' => $filter_rules
	));
	$update = is_file($file);
	file_put_contents($file, $str);
	echo "[DONE] table ".($update ? 'updated':'created')." >> $file -- $table\n";
}

function get_project_dir(){
	$stack = debug_backtrace();
	$f = $stack[count($stack) - 1];
	$project_dir = dirname(dirname(dirname($f['file'])));
	return $project_dir;
}

function get_pk($meta_list){
	foreach($meta_list as $meta){
		if($meta['Key'] == 'PRI'){
			return $meta['Field'];
		}
	}
	return '';
}

function get_class_comment($class_name, $meta_list){
	$r = '';
	foreach($meta_list as $meta){
		$read_only = false;
		if($meta['Extra'] == 'auto_increment'){
			$read_only = true;
		}
		$type = convert_type($meta['Type']);
		$r .= '* '.($read_only ? '@property-read' : '@property').' '.$type.' $'.$meta['Field']." {$meta['Comment']}\n";
	}
	$str = <<<EOT
/**
* Class {$class_name}
{$r}*/
EOT;
	return $str;
}

function convert_type($meta_type){
	$type = 'mixed';
	if(stripos($meta_type, 'char') !== false ||
		stripos($meta_type, 'date') !== false){
		$type = 'string';
	} else if(stripos($meta_type, 'int') !== false ||
		stripos($meta_type, 'timestamp') !== false){
		$type = 'int';
	} else if(stripos($meta_type, 'float') !== false){
		$type = 'float';
	} else if(stripos($meta_type, 'double') !== false){
		$type = 'double';
	}
	return $type;
}

function get_row_filter_rule($meta_list){
	$t = "\t\t\t";
	$str = "{$t}array(\n";
	foreach($meta_list as $meta){
		if($meta['Key'] == 'PRI'){
			continue;
		}
		$str .= "{$t}\t//{$meta['Comment']}\n";
		$str .= "{$t}\t'{$meta['Field']}' => array(\n";

		//非空处理
		if($meta['Null'] == 'NO' && $meta['Default'] === Null){
			$f = $meta['Comment'] ?: $meta['Field'];
			$msg = $f.'不能为空';
			if(stripos($meta['Type'], 'tinyint') !== false ||
				stripos($meta['Type'], 'enum') !== false
			){
				$msg = "请选择{$f}";
			} else if(stripos($meta['Type'], 'char') !== false ||
				stripos($meta['Type'], 'int') !== false ||
				stripos($meta['Type'], 'float') !== false ||
				stripos($meta['Type'], 'double') !== false
			){
				$msg = "请输入{$f}";
			}
			$str .= "{$t}\t\t'REQUIRE' => '".addslashes($msg)."',\n";
		}

		//缺省处理
		if($meta['Null'] == 'YES' && $meta['Default'] !== Null){
			$str .= "{$t}\t\t'DEFAULT' => ".(is_string($meta['Default']) ? "''" : $meta['Default']).",\n";
		}

		//处理长度
		$type = convert_type($meta['Type']);
		if($type == 'string' || $type == 'int'){
			$len = intval(preg_replace('/\D/', '', $meta['Type']));
			if($len){
				$f = $meta['Comment'] ?: $meta['Field'];
				$msg = $f."最大长度为 {$len} 个字符";
				$str .= "{$t}\t\t'MAXLEN' => array('{$msg}',{$len}),\n";
			}
		}

		$str .= "{$t}\t),\n";
	}
	$str .= "{$t})";
	return $str;
}

function convert_class_name($table_name){
	$s = explode('_', $table_name);
	array_walk($s, function(&$item){
		$item = ucfirst($item);
	});
	return join('', $s);
}

function parser_tpl($content, $vars){
	foreach($vars as $k=>$val){
		$content = str_replace('{$'.$k.'}', $val, $content);
	}
	return $content;
}

function get_ns(){
	$str = file_get_contents(PROJECT_ROOT.'/index.php');
	preg_match('/namespace\s*([^;]*);/', $str, $matches);
	return trim($matches[1]);
}

function get_table_meta($table){
	$conn = get_db_conn();
	$st = $conn->prepare('SHOW FULL COLUMNS FROM '.$table);
	$st->execute();
	$ret = array();
	while($r = $st->fetch(PDO::FETCH_ASSOC)){
		$r['Comment'] = trim($r['Comment']);
		$ret[] = $r;
	}
	return $ret;
}

function get_db_conn(){
	$config = include PROJECT_PROTECTED_DIR.'/config/db.inc.php';
	$config['driver'] = $config['driver'] ?: 'mysql';
	$config['charset'] = $config['charset'] ?: 'utf8';
	if($config['dns']){
		$dns = $config['dns'];
	}
	else if($config['driver'] == 'sqlite'){
		$dns = 'sqlite:' . $config['host'];
	}else{
		$dns = "{$config['driver']}:dbname={$config['database']};host={$config['host']}";
		if($config['port']){
			$dns .= ";port={$config['port']}";
		}
	}

	$opt  = array();
	if($config['pconnect']){
		$opt[PDO::ATTR_PERSISTENT] = true;
	}

	$conn = new PDO($dns, $config['user'], $config['password'], $opt);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	if($config['charset']){
		$conn->exec("SET NAMES '".$config['charset']."'");
	}
	return $conn;
}