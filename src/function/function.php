<?php
/**
 * Lite杂项操作函数
 */
namespace Lite\func;

use Exception;
use Lite\Component\Client;
use Lite\Core\Application;
use Lite\Core\Hooker;
use Lite\Core\Router;
use Lite\DB\Driver\DBAbstract;
use Lite\Exception\Exception as LException;

//CLI前景色
const CLI_FORE_COLOR_BLACK = '0;30';
const CLI_FORE_COLOR_DARK_GRAY = '1;30';
const CLI_FORE_COLOR_BLUE = '0;34';
const CLI_FORE_COLOR_LIGHT_BLUE = '1;34';
const CLI_FORE_COLOR_GREEN = '0;32';
const CLI_FORE_COLOR_LIGHT_GREEN = '1;32';
const CLI_FORE_COLOR_CYAN = '0;36';
const CLI_FORE_COLOR_LIGHT_CYAN = '1;36';
const CLI_FORE_COLOR_RED = '0;31';
const CLI_FORE_COLOR_LIGHT_RED = '1;31';
const CLI_FORE_COLOR_PURPLE = '0;35';
const CLI_FORE_COLOR_LIGHT_PURPLE = '1;35';
const CLI_FORE_COLOR_BROWN = '0;33';
const CLI_FORE_COLOR_YELLOW = '1;33';
const CLI_FORE_COLOR_LIGHT_GRAY = '0;37';
const CLI_FORE_COLOR_WHITE = '1;37';

//CLI背景色
const CLI_BACK_COLOR_BLACK = '40';
const CLI_BACK_COLOR_RED = '41';
const CLI_BACK_COLOR_GREEN = '42';
const CLI_BACK_COLOR_YELLOW = '43';
const CLI_BACK_COLOR_BLUE = '44';
const CLI_BACK_COLOR_MAGENTA = '45';
const CLI_BACK_COLOR_CYAN = '46';
const CLI_BACK_COLOR_LIGHT_GRAY = '47';

$GLOBALS['_CLI_WEB_FORE_COLOR_MAP_'] = array(
	CLI_FORE_COLOR_BLACK        => 'black',
	CLI_FORE_COLOR_DARK_GRAY    => '#636363',
	CLI_FORE_COLOR_BLUE         => 'blue',
	CLI_FORE_COLOR_LIGHT_BLUE   => '#6d6dff',
	CLI_FORE_COLOR_GREEN        => 'green',
	CLI_FORE_COLOR_LIGHT_GREEN  => '#30ce30',
	CLI_FORE_COLOR_CYAN         => 'cyan',
	CLI_FORE_COLOR_LIGHT_CYAN   => '#7dffff',
	CLI_FORE_COLOR_RED          => 'red',
	CLI_FORE_COLOR_LIGHT_RED    => '#ff7f7f',
	CLI_FORE_COLOR_PURPLE       => 'purple',
	CLI_FORE_COLOR_LIGHT_PURPLE => '#d842d8',
	CLI_FORE_COLOR_BROWN        => 'BROWN',
	CLI_FORE_COLOR_YELLOW       => 'yellow',
	CLI_FORE_COLOR_LIGHT_GRAY   => '#aaa',
	CLI_FORE_COLOR_WHITE        => '#fff',
);

$GLOBALS['_CLI_WEB_BACK_COLOR_MAP_'] = array(
	CLI_BACK_COLOR_BLACK      => 'black',
	CLI_BACK_COLOR_RED        => 'red',
	CLI_BACK_COLOR_GREEN      => 'green',
	CLI_BACK_COLOR_YELLOW     => 'yellow',
	CLI_BACK_COLOR_BLUE       => 'blue',
	CLI_BACK_COLOR_MAGENTA    => 'MAGENTA',
	CLI_BACK_COLOR_CYAN       => 'cyan',
	CLI_BACK_COLOR_LIGHT_GRAY => '#aaa',
);

//options
const OPTION_REQUIRED = 'required';
const OPTION_OPTIONAL = 'optional';

/**
 * get options
 * @param $param
 * <pre>
 * get_options(
 *      array(
 *          '-s,-site-id' => array(OPTION_OPTIONAL, 'require site id', 'def-site-id')
 *      ),
 *      'set site information'
 * );
 * </pre>
 * @param string $description
 * @param bool $support_cgi
 * @return mixed
 * @throws \Exception
 */
function get_options($param, $description = '', $support_cgi = true){
	if(!Client::inCli() && !$support_cgi){
		die('script only run in CLI mode');
	}

	//check short options error
	foreach($_SERVER['argv'] ?: array() as $opt){
		if(strpos($opt, '--') === false && strpos($opt, '-') === 0){
			list($k, $v) = explode('=', $opt);
			if(preg_match('/\w\w+/', $v, $matches)){
				throw new Exception("option value transited is ambiguity: [$k=$v]. Please use long option type");
			}
		}
	}

	$opt_str = array();
	$long_opts = array();
	foreach($param as $ks => $define){
		list($required) = $define;
		foreach(explode(',', $ks) as $k){
			if(stripos($k, '--') === 0){
				$long_opts[] = substr($k, 2).($required == OPTION_REQUIRED ? ':' : '::');
			} else if(stripos($k, '-') === 0){
				$opt_str[] = substr($k, 1).($required == OPTION_REQUIRED ? ':' : '::');
			} else{
				$opt_str[] = $k.($required == OPTION_REQUIRED ? ':' : '::');
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
			if($required == OPTION_REQUIRED){
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
		echo "<PRE>";
		echo "\n[ERROR]:\n", join("\n", $error), "\n";
		echo "\n[Parameter]:\n";
		foreach($param as $k => $define){
			list($required, $desc) = $define;
			echo "$k [$required] $desc\n";
		}

		if($description){
			echo "\n[Usage]:\n";
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
 * 转化exception到数组
 * @param $e
 * @return array
 */
function convert_exception(\Exception $e){
	$data = '';
	if($e instanceof LException){
		$data = $e->getData();
	}
	$ret = array(
		'message'      => $e->getMessage(),
		'data'         => $data,
		'file'         => $e->getFile(),
		'code'         => $e->getCode(),
		'line'         => $e->getLine(),
		'trace_string' => $e->getTraceAsString(),
	);
	return $ret;
}

/**
 * get cli console color output string
 * @param $str
 * @param null $foreground_color
 * @param null $background_color
 * @return string
 */
function get_cli_color_string($str, $foreground_color = null, $background_color = null){
	if(PHP_SAPI != 'cli'){
		$style = array();
		if($foreground_color){
			$style[] = "color:".$GLOBALS['_CLI_WEB_FORE_COLOR_MAP_'][$foreground_color];
		}
		if($background_color){
			$style[] = "background-color:".$GLOBALS['_CLI_WEB_BACK_COLOR_MAP_'][$background_color];
		}
		if($style){
			return '<span style="'.join(';', $style).'">'.$str.'</span>';
		}
		return $str;
	}

	//windows console no support ansi color mode
	if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
		return $str;
	}

	//linux cli
	$color_str = '';
	if($foreground_color){
		$color_str .= "\033[".$foreground_color."m";
	}
	if($background_color){
		$color_str .= "\033[".$background_color."m";
	}
	if($color_str){
		return $color_str.$str."\033[0m";
	}
	return $str;
}

/**
 * 计算时间差到文本
 * @param $start
 * @param $end
 * @return string
 */
function time_range($start, $end){
	return time_range_v(strtotime($end)-strtotime($start));
}

/**
 * 转化时间长度到字符串
 * <pre>
 * $str = time_range_v(3601);
 * //1H 0M 1S
 * @param $time
 * @return string
 */
function time_range_v($time){
	$d = floor($time/86400);
	$time = $time-$d*86400;
	$h = floor($time/3600);
	$time = $time-$h*3600;
	$m = floor($time/60);
	$time = $time-$m*60;
	$s = (int)$time;
	$str = '';
	$str .= $d ? $d.'d' : '';
	$str .= $h ? $h.'h' : ($str ? '0h' : '');
	$str .= $m ? $m.'m' : ($str ? '0m' : '');
	$str .= $s ? $s.'s' : ($str ? '0s' : '');
	$str = $str ?: '0';
	return $str;
}

function mk_utc($timestamp = null, $short = false){
	$timestamp = $timestamp ?: time();
	if(!$short){
		$str = date('Y-m-d H:i:s', $timestamp);
		$str = str_replace(' ', 'T', $str).'.000Z';
	} else{
		$str = date('Y-m-d H:i', $timestamp);
		$str = str_replace(' ', 'T', $str);
	}
	return $str;
}

/**
 * 打印异常
 * @param \Exception|array $e
 * @param bool $return
 * @return string
 */
function print_exception($e, $return = false){
	if($e instanceof \Exception){
		$e = convert_exception($e);
	}
	$html = Client::inCli() ? "\n\n" : "<PRE>\n";
	$html .= get_cli_color_string("[MSG] \t".$e['message']."\n", CLI_FORE_COLOR_RED);
	$html .= get_cli_color_string("[LOC] \t".$e['file'].' ['.$e['line']."]\n", CLI_FORE_COLOR_LIGHT_PURPLE);
	$html .= "[DATA] \t".var_export($e['data'], true)."\n";
	$html .= "[TRACE]\n".$e['trace_string'];
	if($return){
		return $html;
	}
	echo $html;
	return null;
}

/**
 * 测试
 **/
function dump(){
	$params = func_get_args();
	$tmp = $params;
	$cli = Client::inCli();

	//normal debug
	if(count($params)>0){
		$act = array_pop($tmp) === 1;
		$params = $act ? array_slice($params, 0, -1) : $params;
		echo $cli ? "\n" : '<pre style="font-size:12px; background-color:#eee; color:green; margin:0 0 10px 0; padding:0.5em; border-bottom:1px solid gray; width:100%; left:0; top:0; text-transform: none;">'."\n";
		$comma = '';
		foreach($params as $var){
			echo $comma;
			var_dump($var);
			$trace = debug_backtrace();
			echo "File:".($cli ? '' : '<b style="color:gray">').$trace[0]['file'].($cli ? '' : '</b><br/>')." Line: ".($cli ? '' : '<b>').$trace[0]['line'].($cli ? "\n" : '"</b><br/>"');
			$comma = $cli ? "\n" : '<div style="height:0; line-height:1px; font-size:1px; border-bottom:1px solid white; border-top:1px solid #ccc; margin:10px 0"></div>';
		}
		if(!$cli && $act){
			echo "\n";
		}
		echo $cli ? '' : '</pre>';
		if($act){
			die();
		}
	} //for tick debug
	else{
		if(++$GLOBALS['ONLY_FOR_DEBUG_INDEX']>=$GLOBALS['TICK_DEBUG_START_INDEX']){
			$trace = debug_backtrace();
			echo '<pre style="display:block; font-size:12px; color:green; padding:2px 0; border-bottom:1px solid #ddd; clear:both;">'.'['.($GLOBALS['ONLY_FOR_DEBUG_INDEX']).'] <b>'.$trace[0]['file'].'</b> line:'.$trace[0]['line'].'</pre>';
		}
	}
}

/**
 * error code to string
 * @param $value
 * @return string
 */
function error2string($value){
	$level_names = array(
		E_ERROR           => 'E_ERROR',
		E_WARNING         => 'E_WARNING',
		E_PARSE           => 'E_PARSE',
		E_NOTICE          => 'E_NOTICE',
		E_CORE_ERROR      => 'E_CORE_ERROR',
		E_CORE_WARNING    => 'E_CORE_WARNING',
		E_COMPILE_ERROR   => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING => 'E_COMPILE_WARNING',
		E_USER_ERROR      => 'E_USER_ERROR',
		E_USER_WARNING    => 'E_USER_WARNING',
		E_USER_NOTICE     => 'E_USER_NOTICE'
	);
	if(defined('E_STRICT')){
		$level_names[E_STRICT] = 'E_STRICT';
	}
	$levels = array();
	if(($value&E_ALL) == E_ALL){
		$levels[] = 'E_ALL';
		$value &= ~E_ALL;
	}
	foreach($level_names as $level => $name){
		if(($value&$level) == $level){
			$levels[] = $name;
		}
	}
	return implode(' | ', $levels);
}

/**
 * string to error code
 * @param $string
 * @return int
 */
function string2error($string){
	$level_names = array(
		'E_ERROR',
		'E_WARNING',
		'E_PARSE',
		'E_NOTICE',
		'E_CORE_ERROR',
		'E_CORE_WARNING',
		'E_COMPILE_ERROR',
		'E_COMPILE_WARNING',
		'E_USER_ERROR',
		'E_USER_WARNING',
		'E_USER_NOTICE',
		'E_ALL'
	);
	if(defined('E_STRICT')){
		$level_names[] = 'E_STRICT';
	}
	$value = 0;
	$levels = explode('|', $string);

	foreach($levels as $level){
		$level = trim($level);
		if(defined($level)){
			$value |= (int)constant($level);
		}
	}
	return $value;
}

/**
 * print system error & debug info
 * @param $code
 * @param $msg
 * @param $file
 * @param $line
 * @param string $trace_string
 */
function print_sys_error($code, $msg, $file = null, $line = null, $trace_string = ''){
	echo "<pre>";
	$code = error2string($code);
	echo "[$code] $msg\n\n";
	echo "* $file #$line\n\n";

	if(!$trace_string){
		$bs = debug_backtrace();
		array_shift($bs);
		foreach($bs as $k => $b){
			echo count($bs)-$k." {$b['class']}{$b['type']}{$b['function']}\n";
			echo "  {$b['file']}  #{$b['line']} \n\n";
		}
	} else{
		echo $trace_string;
	}
	die;
}

function get_last_exit_trace(){
	declare(ticks = 1);
	$GLOBALS['___LAST_RUN___'] = null;
	register_tick_function(function(){
		$GLOBALS['___LAST_RUN___'] = debug_backtrace();
	});
	register_shutdown_function(function(){
		dump($GLOBALS['___LAST_RUN___'], 1);
	});
}

/**
 * 时间打点标记，用于性能调试
 * @param array $tags
 * @return array
 */
function performance_mark($tag = '', $data = null, $trace = array()){
	$tm = microtime(true);
	$mem = memory_get_usage(true);
	$trace = $trace ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

	global $c6trpVZUNR7G;
	$c6trpVZUNR7G[] = array($tm, $mem, $trace, $tag, $data);
	return $c6trpVZUNR7G;
}

function lite_auto_performance_mark(){
	//app init
	Hooker::add(Application::EVENT_BEFORE_APP_INIT, function(){
		performance_mark('EVENT_BEFORE_APP_INIT', null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
	});
	Hooker::add(Application::EVENT_AFTER_APP_INIT, function(){
		performance_mark('EVENT_AFTER_APP_INIT', null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
	});

	//router init
	Hooker::add(Router::EVENT_BEFORE_ROUTER_INIT, function(){
		performance_mark('EVENT_BEFORE_ROUTER_INIT', null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
	});
	Hooker::add(Router::EVENT_AFTER_ROUTER_INIT, function(){
		performance_mark('EVENT_AFTER_ROUTER_INIT', null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
	});

	$filter_trace = function($traces){
		$traces = array_filter($traces, function($item){
			if(!$item['file'] || stripos($item['file'], 'Litephp') !== false){
				return false;
			}
			return true;
		});
		return array_first($traces);
	};

	//db query
	Hooker::add(DBAbstract::EVENT_BEFORE_DB_QUERY, function($query)use($filter_trace){
		performance_mark('EVENT_BEFORE_DB_QUERY', '', $filter_trace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15)));
	});
	Hooker::add(DBAbstract::EVENT_AFTER_DB_QUERY, function($query)use($filter_trace){
		performance_mark('EVENT_AFTER_DB_QUERY', $query.'', $filter_trace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15)));
	});
}

/**
 * check is function, string is excluded
 * @param mixed $fun
 * @return boolean
 */
function is_function($fun){
	return is_callable($fun) && gettype($fun) == 'object';
}

/**
 * tick debug
 * @param int $step_offset
 * @param string $fun
 */
function tick_dump($step_offset = 1, $fun = 'dump'){
	$step_offset = (string)$step_offset;
	if(strstr($step_offset, ',') !== false){
		list($start, $step) = array_map('intval', explode(',', $step_offset));
	} else{
		$step = intval($step_offset);
	}
	register_tick_function($fun);
	eval("declare(ticks = $step);");
}

/**
 * pdog
 * @param $fun
 * @param $handler
 */
function pdog($fun, $handler){
	declare(ticks = 1);
	register_tick_function(function() use ($fun, $handler){
		$debug_list = debug_backtrace();
		foreach($debug_list as $info){
			if($info['function'] == $fun){
				call_user_func($handler, $info['args']);
			}
		}
	});
}

/**
 * get GUID
 * @return mixed
 */
function guid(){
	global $__guid__;
	return $__guid__++;
}