<?php
namespace Lite\Component\Cli;

use Lite\Component\Net\Client;
use function LFPhp\Func\array_first;
use function LFPhp\Func\console_color;
use function LFPhp\Func\time_range_v;

abstract class Console {
	const REQUIRED = 'required';
	const OPTIONAL = 'optional';

	/**
	 * get options
	 * @param $param
	 * <pre>
	 * get_options(
	 *      array(
	 *          '-s,-site-id' => array(self::OPTIONAL, 'require site id', 'def-site-id')
	 *      ),
	 *      'set site information'
	 * );
	 * </pre>
	 * @param string $description
	 * @param bool $support_cgi
	 * @return mixed
	 * @throws \Exception
	 */
	public static function getOptions($param, $description = '', $support_cgi = true){
		if(!Client::inCli() && !$support_cgi){
			die('script only run in CLI mode');
		}

		//check short options error
		foreach($_SERVER['argv'] ?: array() as $opt){
			if(strpos($opt, '--') === false && strpos($opt, '-') === 0){
				list($k, $v) = explode('=', $opt);
				if(preg_match('/\w\w+/', $v, $matches)){
					throw new \Exception("option value transited is ambiguity: [$k=$v]. Please use long option type");
				}
			}
		}

		$opt_str = array();
		$long_opts = array();
		foreach($param as $ks => $define){
			list($required) = $define;
			foreach(explode(',', $ks) as $k){
				if(stripos($k, '--') === 0){
					$long_opts[] = substr($k, 2).($required == self::REQUIRED ? ':' : '::');
				} else if(stripos($k, '-') === 0){
					$opt_str[] = substr($k, 1).($required == self::REQUIRED ? ':' : '::');
				} else{
					$opt_str[] = $k.($required == self::REQUIRED ? ':' : '::');
				}
			}
		}

		$opt_str = join('', $opt_str);

		//get options
		$opts = array_merge($_GET, getopt($opt_str, $long_opts) ?: array());

		$error = array();
		foreach($param as $ks => $define){
			list($required, $desc, $default) = $define;

			//found option value
			$found = false;
			$found_val = null;
			foreach(explode(',', $ks) as $k){
				$k = preg_replace('/^\-*/', '', $k);
				if(isset($opts[$k])){
					$found = true;
					$found_val = $opts[$k];
					break;
				}
			}

			//set other keys
			if($found){
				foreach(explode(',', $ks) as $k){
					$k = preg_replace('/^\-*/', '', $k);
					$opts[$k] = $found_val;
				}
			}

			//no found
			if(!$found){
				if($required == self::REQUIRED){
					$error[] = "$ks require $desc";
				} //set default
				else if(isset($default)){
					foreach(explode(',', $ks) as $k){
						$k = preg_replace('/^\-*/', '', $k);
						$opts[$k] = $default;
					}
				}
			}
		}

		//handle error
		if($error){
			echo "\n[ERROR]:\n", join("\n", $error), "\n";
			echo "\n[ALL PARAMETERS]:\n";
			foreach($param as $k => $define){
				list($required, $desc) = $define;
				echo "$k\t[$required] $desc\n";
			}

			if($description){
				echo "\n[DESCRIPTION]:\n";
				$call = debug_backtrace(null, 1);
				$f = basename($call[0]['file']);
				echo "$f $description\n";
			}
			echo "\n[DEBUG]\n";
			debug_print_backtrace();
			exit;
		}

		//rebuild array
		foreach($opts as $k => $val){
			if(preg_match_all('/\[([^\]+])\]/', $k, $matches)){
				unset($opts[$k]);
				parse_str("$k=$val", $tmp);
				$opts = array_merge_recursive($opts, $tmp);
			}
		}
		return $opts;
	}

	/**
	 * 获取任务进度描述文本，格式为：
	 * 当前函数为独占函数
	 * @param number $current_index 当前处理序号
	 * @param number $total 总数
	 * @param string $format 格式表达
	 * @return string
	 */
	public static function getTasksProgressText($current_index, $total, $format = "\n%NOW_DATE %NOW_TIME [PG:%PROGRESS RT:%REMAINING_TIME]"){
		static $start_time;
		if(!$start_time){
			$start_time = time();
		}

		$now_date = date('Y/m/d');
		$now_time = date('H:i:s');
		$progress = "$current_index/$total";

		$remaining_time = '-';
		if($current_index){
			$rt = (time() - $start_time)*($total - $current_index)/$current_index;
			$remaining_time = time_range_v($rt);
		}
		return str_replace([
			'%NOW_DATE',
			'%NOW_TIME',
			'%PROGRESS',
			'%REMAINING_TIME',
		], [
			$now_date,
			$now_time,
			$progress,
			$remaining_time,
		], $format);
	}

	/**
	 * 在控制台中打印表格
	 * @todo
	 * @param $data
	 * @param array $headers
	 */
	public static function printTable($data, $headers = []){
		if(!$headers && $data){
			$all_fields = array_keys(array_first($data));
			$headers = array_combine($all_fields, $all_fields);
		}

		$new_data = [];
		$row_length = [];
		foreach($data as $item){
			$row = [];
			foreach($headers as $field=>$n){
				$row[$field] = $item[$field];
				$row_length[$field] = max($row_length[$field], strlen($item[$field]));
			}
			$new_data[] = $row;
		}
	}

	/**
	 *
	 * @param $cmd_line
	 * @param array $param
	 * @return string
	 */
	public static function buildCommand($cmd_line, array $param = []){
		foreach($param as $k => $val){
			if(is_array($val)){
				foreach($val as $i => $vi){
					$vi = escapeshellarg($vi);
					$cmd_line .= " --{$k}[{$i}]={$vi}";
				}
			} else if(strlen($k)>0){
				$val = escapeshellarg($val);
				$cmd_line .= " --$k=$val";
			} else{
				$val = escapeshellarg($val);
				$cmd_line .= " -$k=$val";
			}
		}
		return $cmd_line;
	}
	
	/**
	 * 运行命令，并获取命令输出（直至进程结束）
	 * @param $command
	 * @param array $param
	 * @return null|string
	 * @throws \Exception
	 */
	public static function runCommand($command, array $param=[]){
		$descriptors_pec = array(
			0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
			2 => array("pipe", "w")    // stderr is a pipe that the child will write to
		);
		flush();

		//WINDOWS环境：必须传递 $_SERVER给子进程，否则子进程内数据库连接可能出错 ？？
		$process = proc_open(static::buildCommand($command, $param), $descriptors_pec, $pipes, realpath('./'), $_SERVER);
		if($process === false || $process === null){
			throw new \Exception('Process create fail:'.$command);
		}
		if(is_resource($process)){
			$result_str = $error_str = '';
			while($s = fgets($pipes[1])){
				$result_str .= $s;
			}
			$has_error = false;
			while($e = fgets($pipes[2])){
				$has_error = true;
				$error_str .= $e;;
			}
			return $has_error ? $error_str : $result_str;
		}
		proc_close($process);
		return null;
	}

	public static function debug(){
		if(PHP_SAPI !== 'cli'){
			static $fst;
			if(!$fst++){
				echo '<pre>';
			}
		}

		$args = func_get_args();
		echo "\n".date("H:i:s ");
		foreach($args as $arg){
			if(is_string($arg) || is_numeric($arg)){
				echo $arg;
			} else if(is_bool($arg)){
				echo $arg ? '[true]' : '[false]';
			} else if(is_null($arg)){
				echo '[null]';
			} else{
				echo preg_replace('/\s*\\n\s*/', '', var_export($arg, true));
			}
			echo "\t";
		}
		ob_flush();
		flush();
	}

	public static function log($string, $time_preset='Y-m-d H:i:s'){
		echo "\n", ($time_preset ? date($time_preset)."\t " : '')."$string";
	}

	public static function error($string, $time_preset='Y-m-d H:i:s'){
		echo "\n", ($time_preset ? date($time_preset)."\t " : '').console_color("[ERROR] $string", 'red');
	}
}
