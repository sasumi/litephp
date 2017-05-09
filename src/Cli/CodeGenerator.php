<?php
namespace Lite\Cli;

use Lite\Component\Server;
use PDO;
use function Lite\func\dump;

abstract class CodeGenerator{
	protected static function getPath($key){
		$app_root = static::getAppRoot();
		$project_root = static::getProjectRoot();
		$map = array(
			'model'      => $app_root . 'model/',
			'controller' => $app_root . 'controller/',
			'table'      => $project_root . 'database/'.str_replace("\\", '/', static::getTableNameSpace()).'/',
		);
		return $map[$key];
	}

	protected static function getNameSpace(){
		$str = file_get_contents(static::getProjectRoot() . '/public/index.php');
		preg_match('/namespace\s*([^;]*);/', $str, $matches);
		$ns = trim($matches[1]);
		return $ns;
	}

	protected static function getTableNameSpace(){
		return static::getNameSpace()."\\db_definition";
	}

	protected static function getControllerNameSpace(){
		return static::getNameSpace()."\\controller";
	}

	protected static function getModelNameSpace(){
		return static::getNameSpace()."\\model";
	}

	protected static function generateModel($table_name, $model_name = '', $overwrite){
		static::generateTable($table_name, $overwrite);
		$model_name = $model_name ?: static::convertClassName($table_name);
		$table_model = 'Table' . static::convertClassName($table_name);

		$ns = static::getNameSpace();
		$fold = static::getPath('model');
		$file = $fold . $model_name . '.php';

		if(!$overwrite && is_file($file)){
			echo "[IGNORE] model file exists: $file\n";
			return;
		}

		$str = static::parserTpl(file_get_contents(__DIR__ . '/model.tpl'), array(
			'namespace'       => static::getModelNameSpace(),
			'table_namespace' => static::getTableNameSpace(). "\\{$table_model}",
			'generate_date'   => date('Y-m-d'),
			'generate_time'   => date('H:i:s'),
			'table_model'     => $table_model,
			'model_name'      => $model_name,
		));

		$update = is_file($file);
		file_put_contents($file, $str);
		echo "[DONE] model " . ($update ? 'updated' : 'created') . " >> $file -- $model_name\n";
	}

	protected static function generateCrudModel($table_name, $model_name = '', $overwrite){
		static::generateTable($table_name, $overwrite);
		$model_name = $model_name ?: static::convertClassName($table_name);
		$table_model = 'Table' . static::convertClassName($table_name);

		$file = static::getPath('model') . $model_name . '.php';

		if(!$overwrite && is_file($file)){
			echo "[IGNORE] model file exists: $file\n";
			return;
		}

		$str = static::parserTpl(file_get_contents(__DIR__ . '/crud_model.tpl'), array(
			'namespace'       => static::getModelNameSpace(),
			'table_namespace' => static::getTableNameSpace(),
			'generate_date'   => date('Y-m-d'),
			'generate_time'   => date('H:i:s'),
			'table_model'     => $table_model,
			'model_name'      => $model_name,
		));

		$update = is_file($file);
		file_put_contents($file, $str);
		echo "[DONE] CRUD model " . ($update ? 'updated' : 'created') . " >> $file -- $model_name\n";
	}

	protected static function generateCrud($table_name, $model_name = '', $controller_name = '', $overwrite = false){
		$model_name = $model_name ?: static::convertClassName($table_name);
		static::generateCrudModel($table_name, $model_name, $overwrite);
		$controller_name = $controller_name ?: static::convertClassName($table_name);
		$controller_name = $controller_name . 'Controller';
		$ns = static::getNameSpace();
		$controller_root = static::getPath('controller');

		if(is_file($controller_root . 'BaseController.php')){
			$extend_controller = 'BaseController';
		} else{
			$extend_controller = 'Controller';
		}

		$file = $controller_root . $controller_name . '.php';

		if(!$overwrite && is_file($file)){
			echo "[IGNORE] model file exists: $file\n";
			return;
		}

		$str = static::parserTpl(file_get_contents(__DIR__ . '/crud_controller.tpl'), array(
			'namespace'         => static::getControllerNameSpace(),
			'model_namespace'   => static::getModelNameSpace(),
			'generate_date'     => date('Y-m-d'),
			'generate_time'     => date('H:i:s'),
			'extend_controller' => $extend_controller,
			'model_name'        => $model_name,
			'controller_name'   => $controller_name,
		));

		$update = is_file($file);
		file_put_contents($file, $str);
		echo "[DONE] CRUD Controller " . ($update ? 'updated' : 'created') . " >> $file -- $controller_name\n";
	}

	protected static function getAllTable(){
		$conn = static::getDbConn();
		$st = $conn->prepare('SHOW TABLES');
		$st->execute();
		$ret = array();
		while($r = $st->fetch(PDO::FETCH_ASSOC)){
			$ret[] = $r;
		}
		return $ret;
	}

	protected static function generateTable($table, $overwrite){
		$class_name = 'Table' . static::convertClassName($table);

		$fold = static::getPath('table');
		if(!is_dir($fold)){
			mkdir($fold, null, true);
		}

		$file = $fold . $class_name . '.php';
		if(!$overwrite && is_file($file)){
			echo "[IGNORE] table file exists: $file\n";
			return;
		}

		$meta_list = static::getTableMeta($table);

		$properties_defines = static::getPropertiesDefines($meta_list);
		$pk = static::get_pk($meta_list);
		$comment = static::getClassComment($class_name, $meta_list);
		$model_desc = static::getTableDesc($table);
		$ns = static::getNameSpace();

		$class_const_string = static::getConstString($meta_list);

		$str = static::parserTpl(file_get_contents(__DIR__ . '/table.tpl'), array(
			'namespace'          => static::getTableNameSpace(),
			'generate_date'      => date('Y-m-d'),
			'generate_time'      => date('H:i:s'),
			'table_name'         => $table,
			'class_comment'      => $comment,
			'class_name'         => $class_name,
			'primary_key'        => $pk,
			'model_desc'         => $model_desc,
			'class_const_string' => $class_const_string,
			'properties_defines' => $properties_defines
		));
		$update = is_file($file);
		file_put_contents($file, $str);
		echo "[DONE] table " . ($update ? 'updated' : 'created') . " >> $file -- $table\n";
	}

	protected static function getProjectRoot(){
		$stack = debug_backtrace();
		$f = $stack[count($stack)-1];
		$project_dir = dirname(dirname($f['file']));
		return $project_dir.'/';
	}

	protected static function getAppRoot(){
		return static::getProjectRoot() . 'app/';
	}

	protected static function get_pk($meta_list){
		foreach($meta_list as $meta){
			if($meta['Key'] == 'PRI'){
				return $meta['Field'];
			}
		}
		return '';
	}

	protected static function getClassComment($class_name, $meta_list){
		$r = '';
		foreach($meta_list as $meta){
			$read_only = false;
			if($meta['Extra'] == 'auto_increment'){
				$read_only = true;
			}
			$type = static::convertType($meta['Type']);
			$r .= "\n * " . ($read_only ? '@property-read' : '@property') . ' ' . $type . ' $' . $meta['Field'] . " {$meta['Comment']}";
		}
		return $r;
	}

	protected static function getTableDesc($table){
		$conn = static::getDbConn();
		$st = $conn->prepare('SHOW CREATE table ' . $table);
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

	protected static function getDBModelPubMethods($class_name){
		$f = dirname(__DIR__) . '/DB/Model.php';
		$str = file_get_contents($f);

		//去除final方法
		$str = preg_replace('/\n\s*final\s+.*?\n/', '', $str);

		if(preg_match_all('/\s+public\s+([^{]+)/', $str, $matches)){
			$matches = array_filter($matches[1], function ($item){
				if(strpos($item, '__') === false && strpos($item, 'getTableName') === false){
					return true;
				}
				return false;
			});

			foreach($matches as $k => $m){
				$m = str_replace('function ', '', $m);
				$m = str_replace('self', $class_name, $m);
				if(stripos($m, 'static') !== false){
					$m = str_replace('static ', 'static ' . $class_name . ' ', $m);
				} else{
					$m = $class_name . ' ' . $m;
				}
				$matches[$k] = $m;
			}

			return $matches;
		}
		return array();
	}

	protected static function convertType($meta_type){
		$type = 'mixed';
		if(stripos($meta_type, 'char') !== false || stripos($meta_type, 'date') !== false){
			$type = 'string';
		} else if(stripos($meta_type, 'int') !== false || stripos($meta_type, 'timestamp') !== false){
			$type = 'int';
		} else if(stripos($meta_type, 'float') !== false){
			$type = 'float';
		} else if(stripos($meta_type, 'double') !== false){
			$type = 'double';
		}
		return $type;
	}

	protected static function getFieldDescription($meta){
		$type = static::getFieldType($meta);
		if($type != 'set' && $type != 'enum' && $meta['Comment'] && preg_match('/\((.*)\)$/', $meta['Comment'], $matches)){
			return $matches[1];
		}
		return '';
	}

	protected static function getFieldAlias($meta){
		if($meta['Comment']){
			$meta['Comment'] = preg_replace("/\\(.*$/", '', $meta['Comment']);
		}
		return $meta['Comment'] ?: $meta['Field'];
	}

	protected static function getFieldPrecision($meta){
		$type = static::getFieldType($meta);
		if(in_array($type, array('float', 'double'))){
			if(preg_match('/,(\d+)\)/', $meta['Type'], $matches)){
				return (int)$matches[1];
			}
		}
		return 0;
	}

	protected static function getFieldType($meta){
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
			'blob'
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
		throw new \Exception('data type detected fail:'.var_export($meta, true));
	}

	protected static function getPropertiesDefines($meta_list){
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
			$alias = addslashes(static::getFieldAlias($meta));
			$description = addslashes(static::getFieldDescription($meta));
			$type = static::getFieldType($meta);
			$precision = static::getFieldPrecision($meta);
			$unique = $meta['Key'] == 'UNI';

			$len = null;
			if($type != 'enum' && $type != 'set'){
				$len = intval(preg_replace('/\D/', '', preg_replace('/\..*?/', '', preg_replace('/,.*$/', '', $meta['Type']))));
			}
			$required = $meta['Null'] == 'NO' && $meta['Default'] === null;

			$str .= "\n{$t}'{$meta['Field']}' => array(\n";
			$str .= "{$t}\t'alias' => '{$alias}',\n";
			$str .= $type ? "{$t}\t'type' => '{$type}',\n" : '';
			$str .= isset($len) ? "{$t}\t'length' => {$len},\n" : '';
			$str .= $pk ? "{$t}\t'primary' => true,\n" : '';
			$str .= $required ? "{$t}\t'required' => true,\n" : '';
			$str .= $readonly ? "{$t}\t'readonly' => true,\n" : '';
			$str .= $precision ? "{$t}\t'precision' => $precision,\n" : '';
			$str .= $unsigned ? "{$t}\t'min' => 0,\n" : '';
			$str .= $description ? "{$t}\t'description' => '$description',\n" : '';
			$str .= $unique ? "{$t}\t'unique' => true,\n" : '';

			if($meta['Default'] !== null){
				$def = addslashes($meta['Default']);
				if($def == 'CURRENT_TIMESTAMP' && in_array($type, ['timestamp', 'datetime'])){
					$str .= "{$t}\t'default' => date('Y-m-d H:i:s'),\n";
				} else if(in_array($type, array('string', 'date', 'datetime', 'time', 'timestamp', 'enum'))){
					$str .= "{$t}\t'default' => '$def',\n";
				} else{
					$str .= "{$t}\t'default' => $def,\n";
				}
			} else if($meta['Null'] != 'NO'){
				$str .= "{$t}\t'default' => null,\n";
			}

			if($type == 'enum' || $type == 'set'){
				$opts = static::getFieldOptions($meta);
				$str .= "{$t}\t'options' => {$opts},\n";
			}
			$str .= "{$t}\t'entity' => true\n";
			$str .= "{$t}),";
		}
		return $str;
	}

	protected static function getConstString($meta_list){
		$t = "\t";
		$str = '';

		foreach($meta_list as $meta){
			$type = static::getFieldType($meta);
			if($type == 'enum'){
				$ks = explode(',', preg_replace(array('/.*?\(/', '/\).*/'), array('', ''), $meta['Type']));
				array_walk($ks, function (&$item){
					$item = trim($item, "'");
				});

				//忽略掉enum不是字符串情况
				$tmp = '';
				foreach($ks as $idx => $k){
					if(is_numeric($k)){
						continue 2;
					}
					$const_key = strtoupper($meta['Field'] . '_' . $k);
					$tmp .= "{$t}const $const_key = '$k';\n";
				}
				$str .= $tmp;

				$ns = explode(',', preg_replace(array('/.*?\(/', '/\).*/'), array('', ''), $meta['Comment']));
				if(count($ns)>1){
					$const_map_key = '$' . strtolower($meta['Field'] . '_map');
					$str .= "\n";
					$str .= "{$t}public static $const_map_key = array(\n";
					foreach($ks as $idx => $k){
						$const_key = strtoupper($meta['Field'] . '_' . $k);
						$n = $ns[$idx];
						$str .= "{$t}\tself::$const_key => '$n',\n";
					}
					$str .= "{$t});\n\n";
				}
			}
		}
		return $str;
	}

	protected static function getFieldOptions($meta){
		$ns = explode(',', preg_replace(array('/.*?\(/', '/\).*/'), array('', ''), $meta['Comment']));
		$ks = explode(',', preg_replace(array('/.*?\(/', '/\).*/'), array('', ''), $meta['Type']));

		$opts = 'array(';
		$comma = '';
		foreach($ks as $idx => $k){
			$v = $ns[$idx];
			$opts .= $comma . "$k=>'$v'";
			$comma = ', ';
		}
		$opts .= ')';
		return $opts;
	}

	protected static function convertClassName($table_name){
		$s = explode('_', $table_name);
		array_walk($s, function (&$item){
			$item = ucfirst($item);
		});
		return join('', $s);
	}

	protected static function parserTpl($content, $vars){
		foreach($vars as $k => $val){
			$content = str_replace('{$' . $k . '}', $val, $content);
		}
		return $content;
	}

	protected static function getTableMeta($table){
		$conn = static::getDbConn();
		$st = $conn->prepare('SHOW FULL COLUMNS FROM ' . $table);
		$st->execute();
		$ret = array();
		while($r = $st->fetch(PDO::FETCH_ASSOC)){
			$r['Comment'] = trim($r['Comment']);
			$ret[] = $r;
		}
		return $ret;
	}

	protected static function getDBConfig(){
		$ns = static::getNameSpace();
		return include static::getProjectRoot() . "/database/$ns/db.inc.php";
	}

	protected static function getDbConn(){
		$config = static::getDBConfig();
		$config['type'] = $config['type'] ?: 'mysql';
		$config['charset'] = $config['charset'] ?: 'utf8';
		if($config['dns']){
			$dns = $config['dns'];
		} else if($config['type'] == 'sqlite'){
			$dns = 'sqlite:' . $config['host'];
		} else{
			$dns = "{$config['type']}:dbname={$config['database']};host={$config['host']}";
			if($config['port']){
				$dns .= ";port={$config['port']}";
			}
		}

		$opt = array();
		if($config['pconnect']){
			$opt[PDO::ATTR_PERSISTENT] = true;
		}

		$conn = new PDO($dns, $config['user'], $config['password'], $opt);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		if($config['charset']){
			$conn->exec("SET NAMES '" . $config['charset'] . "'");
		}
		return $conn;
	}

	public static function init(){
		if(PHP_SAPI != 'cli'){
			throw new \Exception('script run only in cli mode');
		}
		$args = $_SERVER['argv'];
		$script_file = array_shift($args);
		$args = static::getArgs($args);
		$overwrite = $args['overwrite'];
		$cmd = array_shift($args);

		$help = <<<EOT
=======================================================
<<STATEMENT LIST>>
option: -o overwrite file

generate model file
php $script_file model [-o] -t table_name -m model_name

generate all model file
php $script_file allmodel [-o]

generate specified database table:
php $script_file table [-o] -t table_name

generate all tables:
php $script_file alltable [-o]

generate specified crud use table:
php $script_file crud [-o] -t table_name -m model_name -c controller_name

generate all crud module:
php $script_file allcrud [-o]

generate field infos:
php $script_file field -t table_name
=======================================================
EOT;

		switch($cmd){
			case 'model':
				static::generateModel($args['t'], $args['m'], $overwrite);
				break;

			case 'allmodel':
				$tables = static::getAllTable();
				foreach($tables as $item){
					$table = array_pop($item);
					static::generateModel($table, null, $overwrite);
				}
				echo "ALL MODEL GENERATED\n";
				break;

			case 'table':
				$table = $args['t'];
				static::generateTable($table, $overwrite);
				break;

			case 'alltable':
				$tables = static::getAllTable();
				foreach($tables as $item){
					$table = array_pop($item);
					static::generateTable($table, $overwrite);
				}
				echo "ALL TABLE GENERATED\n";
				break;

			case 'crud':
				$table = $args['t'];
				$model = $args['m'];
				$controller = $args['c'];
				static::generateCrud($table, $model, $controller, $overwrite);
				break;

			case 'allcrud':
				$tables = static::getAllTable();
				foreach($tables as $item){
					$table = array_pop($item);
					static::generateCrud($table, null, null, $overwrite);
				}
				echo "ALL CRUD GENERATED\n";
				break;

			case 'field':
				$meta_list = static::getTableMeta($args['t']);
				$names = array_column($meta_list, 'Field');
				$comments = array();

				echo "----- Alias -----\n";
				foreach($meta_list as $k=>$m){
					if(Server::inWindows()){
						echo iconv('utf-8', 'gb2312', static::getFieldAlias($m));
					} else {
						echo static::getFieldAlias($m);
					}
					echo "\n";
				}

				echo "----- Keys -----\n";
				foreach($names as $n){
					echo $n."\n";
				}
				break;

			default:
				echo $help;
		}
	}

	protected static function getArgs(array $args){
		$ret = array();
		for($i = 0; $i<count($args); $i++){
			if($args[$i] == '-o'){
				$ret['overwrite'] = true;
			} else{
				if(substr($args[$i], 0, 1) == '-'){
					$ret[substr($args[$i], 1)] = $args[$i+1];
					$i++;
				} else{
					$ret[] = $args[$i];
				}
			}
		}
		return $ret;
	}
}