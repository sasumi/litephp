<?php
/**
 * Lite杂项操作函数
 */
namespace Lite\func;

use Closure;
use Lite\Component\Net\Client;
use Lite\Core\Application;
use Lite\Core\Hooker;
use Lite\Core\Router;
use Lite\DB\Driver\DBAbstract;

//dump函数控制标记
$GLOBALS['DUMP_ENABLE_FLAG'] = true;

//dump函数trace偏移量，默认为1（表示忽略dump函数自身trace信息）
$GLOBALS['DUMP_ENTRANCE_LEVEL'] = 0;

//dump函数是否同时输出trace信息，默认仅输出文件定位
$GLOBALS['DUMP_WITH_TRACE'] = false;

/**
 * 变量调试函数，并输出当前调试点所在位置
 * 用法：dump($var1, $var2, ..., 1)，当最后一个变量为1时，程序退出
 */
function dump(){
	if(!$GLOBALS['DUMP_ENABLE_FLAG']){
		return;
	}
	$params = func_get_args();
	$cli = Client::inCli();
	$exit = false;
	echo !$cli ? PHP_EOL.'<pre style="color:green;">'.PHP_EOL : PHP_EOL;

	if(count($params)){
		$tmp = $params;
		$exit = array_pop($tmp) === 1;
		$params = $exit ? array_slice($params, 0, -1) : $params;
		$comma = '';
		foreach($params as $var){
			echo $comma;
			var_dump($var);
			$comma = str_repeat('-',80).PHP_EOL;
		}
	}

	//remove closure calling & print out location.
	$trace = debug_backtrace();
	$trace = array_slice($trace, dump_trace_entrance_offset());
	if($GLOBALS['DUMP_WITH_TRACE']){
		echo "[trace]",PHP_EOL;
		print_trace($trace, true, true);
	} else {
		print_trace([$trace[0]]);
	}
	echo str_repeat('=', 80), PHP_EOL, (!$cli ? '</pre>' : '');
	$exit && exit();
}

/**
 * dump函数trace取值偏移
 * @param null $level 偏移量
 * @return null
 */
function dump_trace_entrance_offset($level = null){
	if(isset($level)){
		$GLOBALS['DUMP_ENTRANCE_LEVEL'] = $level;
	}
	return $GLOBALS['DUMP_ENTRANCE_LEVEL'];
}

/**
 * dump并输出trace信息
 * @return mixed
 */
function dump_with_trace(){
	$GLOBALS['DUMP_WITH_TRACE'] = true;
	dump_trace_entrance_offset(2);
	return call_user_func_array('\Lite\func\dump', func_get_args());
}

/**
 * 禁用dump函数
 */
function dump_disable(){
	$GLOBALS['DUMP_ENABLE_FLAG'] = false;
}

/**
 * 启用dump函数
 */
function dump_enable(){
	$GLOBALS['DUMP_ENABLE_FLAG'] = true;
}

/**
 * 输出最后调用堆栈
 */
function dump_last_exit_trace(){
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
 * 步进方式调试
 * @param int $step 步长
 * @param string $fun 调试函数，默认使用dump
 */
function tick_dump($step = 1, $fun = '\Lite\func\dump'){
	register_tick_function($fun);
	eval("declare(ticks = $step);");
}

/**
 * 打印trace信息
 * @param $trace
 * @param bool $with_callee
 * @param bool $with_index
 */
function print_trace($trace, $with_callee = false, $with_index = false){
	$ct = count($trace);
	foreach($trace as $k=>$item){
		$callee = '';
		if($with_callee){
			$vs = [];
			foreach($item['args'] as $arg){
				$vs[] = var_export_min($arg, true);
			}
			$arg_statement = join(',', $vs);
			$arg_statement = substr(str_replace("\n", '', $arg_statement), 0, 50);
			$callee = $item['class'] ? "\t{$item['class']}{$item['type']}{$item['function']}($arg_statement)" : "\t{$item['function']}($arg_statement)";
		}
		if($with_index){
			echo "[", ($ct - $k), "] ";
		}
		$loc = $item['file'] ? "{$item['file']} #{$item['line']} " : '';
		echo "{$loc}{$callee}", PHP_EOL;
	}
}

/**
 * 打印系统错误及trace跟踪信息
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
 * 时间打点标记，用于性能调试
 * @param string $tag
 * @param null $data
 * @param array $trace
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

/**
 * 打印页面
 * @param $qc_list
 * @param array $config
 */
function lite_print_performance_mark($qc_list, $config = array()){
	$config = array_merge(array(
		'time_threshold' => 0.1,  //单步时间告警阀值
		'memory_threshold' => 1024*1024*5, //单步内存告警阀值
		'max_query' => 50, //数据库查询条数告警阀值
		'page_time_threshold' => 3, //页面执行时间告警阀值
		'page_memory_threshold' => 1024*1024*50 //页面内存告警阀值50M
	), $config);
	
	$st = $qc_list[0][0];
	$mt = $qc_list[0][1];
	
	echo '<style>',
	'* {font-size:12px; font-family:helvetica, Microsoft Yahei, serif; line-height:1.8}',
	'table {border-collapse:collapse; width:98%; margin:0 auto; background-color:white; border:1px solid #bbb;}',
	'ul {padding:0 2em}',
	'caption * {text-align:left; font-size:110%;}',
	'td, th{padding:0.25em 0.5em; border-bottom:1px solid #ddd;}',
	'th {white-space:nowrap; text-transform:capitalize; background-color:#ddd; padding:0.5em; text-align:left;}',
	'tr:nth-child(even) {background-color:#efefef;}',
	'tr:hover {background-color:#dedede;}',
	'.cls_fun {color:#bbb; display:block;}',
	'.pb, .ms {color:#eee}',
	'.pbo, .mso {display:block; background-color:white; position:relative}',
	'.tq-warn, .pbo-warn, .mso-warn {color:red}',
	'.mso-warn {font-weight:bold;}',
	'.loc {white-space:nowrap}',
	'.brk {word-break:break-all;}',
	'</style>';
	
	$total_query = 0;
	array_walk($qc_list, function($item)use(&$total_query){
		$total_query += $item[3] == DBAbstract::EVENT_BEFORE_DB_QUERY ? 1 : 0;
	});
	
	echo '<table>',
	"<caption><ul>",
	"<li>Total DB Query: <b class=\"",($total_query > $config['max_query'] ? 'tq-warn':''),"\">$total_query</b></li>",
	"<li>Time Cost: <b class=\"",(array_last($qc_list)[0] - $st > $config['page_time_threshold'] ? 'pbo-warn':''),"\">", round((array_last($qc_list)[0] - $st)*1000, 2), "ms</b></li>",
	"<li>Mem Cost: <b class=\"",(array_last($qc_list)[1] - $mt > $config['page_memory_threshold'] ? 'mso-warn':''),"\">",format_size(array_last($qc_list)[1] - $mt),"</b></li>",
	"</ul></caption>",
	'<thead><tr>',
		'<th>'.join('</th><th>', ['IDX', 'tag / event', 'file call', 'data', 'pass by', 'mem stat']).'</th>',
	'</tr></thead>';
	
	$lst = $st;
	$lms = $mt;
	foreach($qc_list as $k=>$item){
		list($pass_by, $mem_stat, $trace, $tag, $data) = $item;
		$pass_by_offset = $pass_by - $lst;
		$mem_stat_offset = $mem_stat - $lms;
		echo '<tr class="',($pass_by_offset > $config['time_threshold'] ? 'pbo-warn' : ''),($mem_stat_offset > $config['memory_threshold'] ? ' mso-warn' : ''),'">',
		"<td>$k</td>",
		"<td>$tag</td>",
		"<td>",
		"<span class=\"loc\">{$trace['file']} #{$trace['line']}</span>",
		"<span class=\"cls_fun\">{$trace['class']}{$trace['type']}{$trace['function']}()</span>",
		"</td>",
		"<td class=\"brk\">$data</td>",
		"<td>",
		"<span class=\"pbo\">",round($pass_by_offset*1000, 2),"ms</span>",
		"<span class=\"pb\">",round(($pass_by - $st)*1000, 2),"ms</span>",
		"</td>",
		"<td>",
		"<span class=\"mso\">",format_size($mem_stat_offset),"</span>",
		"<span class=\"ms\">",format_size($mem_stat),"</span>",
		"</td>";
		
		$lst = $pass_by;
		$lms = $mem_stat;
	}
	echo '</table>';
}

function lite_auto_performance_mark(){
	//app init
	Hooker::add(Application::EVENT_BEFORE_APP_INIT, function(){
		performance_mark(Application::EVENT_BEFORE_APP_INIT, null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
	});
	Hooker::add(Application::EVENT_AFTER_APP_INIT, function(){
		performance_mark(Application::EVENT_AFTER_APP_INIT, null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
	});
	
	//router init
	Hooker::add(Router::EVENT_BEFORE_ROUTER_INIT, function(){
		performance_mark(Router::EVENT_BEFORE_ROUTER_INIT, null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
	});
	Hooker::add(Router::EVENT_AFTER_ROUTER_INIT, function(){
		performance_mark(Router::EVENT_AFTER_ROUTER_INIT, null, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]);
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
	Hooker::add(DBAbstract::EVENT_BEFORE_DB_QUERY, function()use($filter_trace){
		performance_mark('EVENT_BEFORE_DB_QUERY', '', $filter_trace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15)));
	});
	Hooker::add(DBAbstract::EVENT_AFTER_DB_QUERY, function($query)use($filter_trace){
		performance_mark('EVENT_AFTER_DB_QUERY', $query.'', $filter_trace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15)));
	});
}

/**
 * check is function
 * @param mixed $f
 * @return boolean
 */
function is_function($f){
	return (is_string($f) && function_exists($f)) || (is_object($f) && ($f instanceof Closure));
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

/**
 * var_export in minimal format
 * @param $var
 * @param bool $return
 * @return mixed|string
 */
function var_export_min($var, $return = false) {
	if (is_array($var)) {
		$toImplode = array();
		foreach ($var as $key => $value) {
			$toImplode[] = var_export($key, true).'=>'.var_export_min($value, true);
		}
		$code = 'array('.implode(',', $toImplode).')';
		if ($return){
			return $code;
		}
		else echo $code;
	} else {
		return var_export($var, $return);
	}
}