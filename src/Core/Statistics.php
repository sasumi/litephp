<?php
namespace Lite\Core;
use function Lite\func\array_last;

/**
 * 统计类,提供基本统计方法
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
class Statistics {
	private static $instance_list = array();
	private $time_track_list = array();

	/**
	 * 只允许单例模式
	 */
	private function __construct(){

	}

	/**
	 * 单例模式
	 * @param string $key
	 * @return Statistics
	 */
	public static function instance($key='default'){
		if(!self::$instance_list[$key]){
			self::$instance_list[$key] = new self();
		}
		return self::$instance_list[$key];
	}

	/**
	 * tick性能统计
	 * @param int $step 跳跃步长
	 */
	public function startTickMark($step=10){
		eval("declare(ticks=$step);");
		$self = $this;
		register_tick_function(function()use($self){
			$debug_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$info = $debug_info[1];
			$self->mark($info['file']." [{$info['line']}] {$info['class']}{$info['type']}{$info['function']}");
		});
	}

	/**
	 * 获取时间标签清单
	 * @return array
	 */
	public function getTimeTrackList(){
		return $this->time_track_list;
	}

	/**
	 * 标记位置
	 * @param string $msg 位置信息
	 * @param null $tag 标签
	 */
	public function mark($msg='', $tag=null){
		$first = $this->time_track_list[0];
		$now = microtime(true);
		$mem = memory_get_usage();

		$this->time_track_list[] = array(
			'msg' => $msg,
			'tag' =>  $tag,
			'time_point' => $now,
			'time_offset' => $first ? $now - $first['time_point'] : 0,
			'memory' => $mem
		);
	}

	/**
	 * 标记前位
	 * @param string $msg
	 * @param $tag
	 */
	public function markBefore($msg='', $tag){
		$this->mark($msg, $tag);
	}

	/**
	 * 标记后位
	 * @param $msg
	 * @param $tag
	 */
	public function markAfter($msg, $tag){
		$last_idx = count($this->time_track_list)-1;
		$last_item = $this->time_track_list[$last_idx];

		$this->mark($msg, $tag);
		$cur_item = array_pop($this->time_track_list);

		$last_item['time_used'] = number_format(($cur_item['time_point'] - $last_item['time_point'])*1000, 2);
		$last_item['mem_used'] = $cur_item['memory'] - $last_item['memory'];
		$last_item['msg'] .= " // ".$cur_item['msg'];
		$this->time_track_list[$last_idx]=  $last_item;
	}

	/**
	 * 输出标记内容
	 * @return string
	 */
	public function _toString(){
		$str = "<pre>\n";
		foreach($this->time_track_list as $item){
			$str .= str_repeat('-', 120)."\n";
			$str .= $item['tag']."\n";
			$str .= "\n";
			$s = array_last(explode('.',$item['time_point'].''));
			$str .= "[MSG] ".$item['msg']."\t\t[time_point] ".date('H:i:s', $item['time_point']).' '.$s."\t\t[MEM] ".number_format($item['memory']/1024, 2, null, '')."KB\n\n";
			if(isset($item['time_used'])){
				$str .= "[USED TIME] ".$item['time_used']."ms\t[USED MEM] ".number_format($item['mem_used']/1023, 2)."KB\n\n";
			}
		}
		return $str;
	}
}