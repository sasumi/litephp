<?php
namespace Lite\Cli;
use PDO;

!defined('PROJECT_ROOT') && define('PROJECT_ROOT', get_project_dir());
define('PROJECT_PROTECTED_DIR', PROJECT_ROOT.'/protected');
class CodeGenerator {
	public static function load(){}
}

if(PHP_SAPI == 'cli'){
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

		case 'crud':
			$table = $args[0];
			$model = $args[1];
			$controller = $args[2];
			generate_crud($table, $model, $controller, $overwrite);
			break;

		default:
			echo $help;
	}
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

function generate_crud_model($table_name, $model_name='', $overwrite){
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

	$str = parser_tpl(file_get_contents(__DIR__.'/crud_model.tpl'), array(
		'namespace' => $ns."\\model",
		'generate_date' => date('Y-m-d'),
		'generate_time' => date('H:i:s'),
		'table_model' => $table_model,
		'model_name' => $model_name,
	));

	$update = is_file($file);
	file_put_contents($file, $str);
	echo "[DONE] CRUD model ".($update ? 'updated':'created')." >> $file -- $model_name\n";
}

function generate_crud($table_name, $model_name='', $controller_name='', $overwrite=false){
	$model_name = $model_name ?: convert_class_name($table_name);
	generate_crud_model($table_name, $model_name, $overwrite);
	$controller_name = $controller_name ?: convert_class_name($table_name);
	$controller_name = $controller_name.'Controller';
	$ns = get_ns();

	if(is_file(PROJECT_PROTECTED_DIR.'/controller/BaseController.php')){
		$extend_controller = 'BaseController';
	} else {
		$extend_controller = 'Controller';
	}

	$fold = PROJECT_PROTECTED_DIR.'/controller/';
	$file = $fold.$controller_name.'.php';

	if(!$overwrite && is_file($file)){
		echo "[IGNORE] model file exists: $file\n";
		return;
	}

	$str = parser_tpl(file_get_contents(__DIR__.'/crud_controller.tpl'), array(
		'namespace' => $ns."\\controller",
		'generate_date' => date('Y-m-d'),
		'generate_time' => date('H:i:s'),
		'extend_controller' => $extend_controller,
		'model_name' => $model_name,
		'controller_name' => $controller_name,
	));

	$update = is_file($file);
	file_put_contents($file, $str);
	echo "[DONE] CRUD Controller ".($update ? 'updated':'created')." >> $file -- $controller_name\n";
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
	$properties_defines = get_properties_defines($meta_list);
	$pk = get_pk($meta_list);
	$comment = get_class_comment($class_name, $meta_list);
	$model_desc = get_table_desc($table);
	$ns = get_ns();

	$str = parser_tpl(file_get_contents(__DIR__.'/table.tpl'), array(
		'namespace' => $ns."\\model\\table",
		'generate_date' => date('Y-m-d'),
		'generate_time' => date('H:i:s'),
		'table_name' => $table,
		'class_comment' => $comment,
		'class_name' => $class_name,
		'primary_key' => $pk,
		'model_desc' => $model_desc,
		'filter_rules' => $filter_rules,
		'properties_defines' => $properties_defines
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
		$r .= "\n * ".($read_only ? '@property-read' : '@property').' '.$type.' $'.$meta['Field']." {$meta['Comment']}";
	}
	return $r;
}

function get_table_desc($table){
	$conn = get_db_conn();
	$st = $conn->prepare('SHOW CREATE table '.$table);
	$st->execute();
	$ret = array();
	while($r = $st->fetch(PDO::FETCH_ASSOC)){
		$script = $r['Create Table'];
		if(preg_match('/\s+comment=\'([^\']+)\'/i', $script, $matches)){
			return $matches[1];
		}
	}
	return $table;
}

function get_db_model_public_methods($class_name){
	$f = dirname(__DIR__).'/DB/Model.php';
	$str = file_get_contents($f);

	//去除final方法
	$str = preg_replace('/\n\s*final\s+.*?\n/', '', $str);

	if(preg_match_all('/\s+public\s+([^{]+)/', $str, $matches)){
		$matches = array_filter($matches[1], function($item){
			if(strpos($item, '__') === false &&
				strpos($item, 'getTableName') === false){
				return true;
			}
			return false;
		});

		foreach($matches as $k=>$m){
			$m = str_replace('function ', '', $m);
			$m = str_replace('self', $class_name, $m);
			if(stripos($m, 'static') !== false){
				$m = str_replace('static ', 'static '.$class_name.' ', $m);
			} else {
				$m = $class_name.' '.$m;
			}
			$matches[$k] = $m;


			//* @method static TableUser findOneByPk($val, $as_array=false)
		}

		return $matches;
	}
	return array();
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

function get_field_description($meta){
	$type = get_field_type($meta);
	if($type != 'set' && $type != 'enum' && $meta['Comment'] && preg_match('/\((.*)\)$/', $meta['Comment'], $matches)){
		return $matches[1];
	}
	return '';
}

function get_field_alias($meta){
	if($meta['Comment']){
		$meta['Comment'] = preg_replace("/\\(.*$/", '', $meta['Comment']);
	}
	return $meta['Comment'] ?: $meta['Field'];
}

function get_field_precision($meta){
	$type = get_field_type($meta);
	if(in_array($type, array('float','double'))){
		if(preg_match('/,(\d+)\)/', $meta['Type'], $matches)){
			return (int)$matches[1];
		}
	}
	return 0;
}

function get_field_type($meta){
	$a = array(
		'char',
		'int',
		'float',
		'double',
		'bool',
		'enum',
		'set',
		'timestamp',
		'datetime',
		'date',
		'time',
	);

	$t = $meta['Type'];

	//文本判断
	switch($t){

		case 'tinytext':
			return 'text';

		case 'text':
			return 'simple_rich_text';

		case 'mediumtext':
			return 'rich_text';

		case 'longtext':
			return 'rich_text';
	}
	foreach($a as $k){
		if(stripos($t, $k) !== false){
			if($k == 'char'){
				return 'string';
			}
			return $k;
		}
	}

	var_dump($meta);
	throw new \Exception('data type detected fail');
}

function get_properties_defines($meta_list){
	$t = "\t\t\t";
	$str = '';
	foreach($meta_list as $meta){
		$pk = $meta['Key'] == 'PRI';
		$auto_increment = $meta['Extra'] == 'auto_increment';

		//缺省自动更新时间字段设置为只读
		$auto_update_timestamp = $meta['Extra'] == 'on update CURRENT_TIMESTAMP';

		//缺省填充时间字段设置为只读
		$auto_fill_default_timestamp = $meta['Null'] == 'NO' && $meta['Default'] == 'CURRENT_TIMESTAMP';

		$readonly = ($pk && $auto_increment) || $auto_update_timestamp || $auto_fill_default_timestamp;
		$unsigned = stripos($meta['Type'], 'unsigned') !== false;
		$alias = addslashes(get_field_alias($meta));
		$description = addslashes(get_field_description($meta));
		$type = get_field_type($meta);
		$precision = get_field_precision($meta);

		$len = null;
		if($type != 'enum' && $type != 'set'){
			$len = intval(preg_replace('/\D/', '',  preg_replace('/\..*?/', '', preg_replace('/,.*$/', '',$meta['Type']))));
		}

		$required = $meta['Null'] == 'NO' && $meta['Default'] === null;

		if($meta['Field'] == 'create_time'){
			//var_dump($required, $meta['Default'], $meta);die;
		}

		$str .= "\n{$t}'{$meta['Field']}' => array(\n";
		$str .= "{$t}\t'alias' => '{$alias}',\n";
		$str .= $type ? "{$t}\t'type' => '{$type}',\n" : '';
		$str .= isset($len) ? "{$t}\t'length' => {$len},\n" : '';
		$str .= $pk ? "{$t}\t'primary' => true,\n" :'';
		$str .= $required ? "{$t}\t'required' => true,\n" : '';
		$str .= $readonly ? "{$t}\t'readonly' => true,\n" : '';
		$str .= $precision ? "{$t}\t'precision' => $precision,\n" : '';
		$str .= $unsigned ? "{$t}\t'min' => 0,\n" : '';
		$str .= $description ? "{$t}\t'description' => '$description',\n" : '';

		if($meta['Default'] !== null){
			$def = addslashes($meta['Default']);
			if($type == 'timestamp' && $def == 'CURRENT_TIMESTAMP'){
				$str .= "{$t}\t'default' => time(),\n";
			}
			else if(in_array($type, array('string','date','datetime','time','timestamp'))){
				$str .= "{$t}\t'default' => '$def',\n";
			} else {
				$str .= "{$t}\t'default' => $def,\n";
			}
		}
		if($type == 'enum' || $type=='set'){
			$opts = get_field_options($meta);
			$str .= "{$t}\t'options' => {$opts},\n";
		}
		$str .= "{$t}\t'entity' => true\n";
		$str .= "{$t}),";
	}
	return $str;
}

function get_row_filter_rule($meta_list){
	$t = "\t\t\t";
	$str = "{$t}array(\n";
	foreach($meta_list as $meta){
		if($meta['Key'] == 'PRI'){
			continue;
		}

		$alias = get_field_alias($meta);
		$str .= "{$t}\t//{$alias}\n";
		$str .= "{$t}\t'{$meta['Field']}' => array(\n";

		//非空处理
		if($meta['Null'] == 'NO' && $meta['Default'] === Null){
			$msg = $alias.'不能为空';
			if(stripos($meta['Type'], 'tinyint') !== false ||
				stripos($meta['Type'], 'enum') !== false
			){
				$msg = "请选择{$alias}";
			} else if(stripos($meta['Type'], 'char') !== false ||
				stripos($meta['Type'], 'int') !== false ||
				stripos($meta['Type'], 'float') !== false ||
				stripos($meta['Type'], 'double') !== false
			){
				$msg = "请输入{$alias}";
			}
			$str .= "{$t}\t\t'REQUIRED' => '".addslashes($msg)."',\n";
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
				$msg = $alias."最大长度为 {$len} 个字符";
				$str .= "{$t}\t\t'MAXLEN' => array('{$msg}',{$len}),\n";
			}
		}

		$str .= "{$t}\t),\n";
	}
	$str .= "{$t})";
	return $str;
}

function get_field_options($meta){
	$ns = explode(',', preg_replace(array('/.*?\(/', '/\).*/'), array('',''), $meta['Comment']));
	$ks = explode(',', preg_replace(array('/.*?\(/', '/\).*/'), array('',''), $meta['Type']));

	$opts = 'array(';
	$comma = '';
	foreach($ks as $idx=>$k){
		$v = $ns[$idx];
		$opts .= $comma."$k=>'$v'";
		$comma = ', ';
	}
	$opts .= ')';
	return $opts;
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