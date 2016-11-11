<?php
namespace Lite\Core;
use Lite\DB\Driver\DBAbstract;
use function Lite\func\array_last;
use function Lite\func\format_size;

/**
 * 统计类,提供基本统计方法
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
class Statistics {
	public $id;
	private static $instance_list = array();
	private $time_track_list = array();

	/**
	 * 只允许单例模式
	 * @param $id
	 */
	private function __construct($id){
		$this->id = $id;
	}

	/**
	 * 单例模式
	 * @param string $id
	 * @return Statistics
	 */
	public static function instance($id='default'){
		if(!self::$instance_list[$id]){
			self::$instance_list[$id] = new self($id);
		}
		return self::$instance_list[$id];
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

	public function autoCollect(callable $finish_handler){
		$data = array(
			'DB_QUERY_COUNT'               => 0,
			'DB_QUERY_TIME'                => 0,
			'DB_QUERY_MEM'                 => 0,
			'DB_QUERY_DEDUPLICATION_COUNT' => 0,
		);

		Hooker::add(DBAbstract::EVENT_BEFORE_DB_QUERY, function($sql) use (&$data){
			$data['DB_QUERY_COUNT']++;
			$this->mark('BEFORE DB QUERY', $sql);
		});

		Hooker::add(DBAbstract::EVENT_AFTER_DB_QUERY, function ($sql) use (&$data){
			$this->markAfter('AFTER DB QUERY', $sql);
			$tmp = $this->getTimeTrackList();
			$tt = array_last($tmp);
			$data['DB_QUERY_TIME'] += $tt['time_used'];
			$data['DB_QUERY_MEM'] += $tt['mem_used'];
		});

		Hooker::add(DBAbstract::EVENT_ON_DB_QUERY_DISTINCT, function()use(&$data){
			$data['DB_QUERY_DEDUPLICATION_COUNT']++;
		});

		Hooker::add(Application::EVENT_AFTER_APP_SHUTDOWN, function() use (&$data, $finish_handler){
			$pc = 0;
			if($data['DB_QUERY_DEDUPLICATION_COUNT'] + $data['DB_QUERY_COUNT']){
				$pc = number_format($data['DB_QUERY_DEDUPLICATION_COUNT'] / ($data['DB_QUERY_DEDUPLICATION_COUNT'] + $data['DB_QUERY_COUNT'])*100, 2, null, '');
			}
			$msg = 'DB QUERY COUNT:'.$data['DB_QUERY_COUNT'].
				"\t\t\tDB QUERY TIME:".$data['DB_QUERY_TIME']."ms\n".
				"DEDUPLICATION QUERY:".$data['DB_QUERY_DEDUPLICATION_COUNT']."($pc%)".
				"\t\tDB QUERY COST MEM:".format_size($data['DB_QUERY_MEM'])."\n\n";

			$msg .= "[PROCESS USED TIME] ".number_format((microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'])*1000, 1)."ms";
			$this->mark($msg, str_repeat('=', 120)."\nAPP SHUTDOWN");
			$finish_handler($this->time_track_list);
		});
	}

	/**
	 * @param $time_track_list
	 * @param bool $return
	 * @return string
	 */
	public static function printTrackList($time_track_list, $return=false){
		$str = '';
		foreach($time_track_list?:array() as $item){
			$str .= str_repeat('-', 120)."\n";
			$str .= $item['tag']."\n";
			$str .= "\n";
			$s = array_last(explode('.',$item['time_point'].''));
			$str .= "[MSG] ".$item['msg']."\t\t[time_point] ".date('H:i:s', $item['time_point']).' '.$s."\t\t[MEM] ".number_format($item['memory']/1024, 2, null, '')."KB\n\n";
			if(isset($item['time_used'])){
				$str .= "[USED TIME] ".$item['time_used']."ms\t[USED MEM] ".number_format($item['mem_used']/1023, 2)."KB\n\n";
			}
		}
		if($return){
			return $str;
		} else {
			echo "<pre>\n".$str;
		}
	}

	/**
	 * 输出标记内容
	 * @return string
	 */
	public function _toString(){
		self::printTrackList($this->time_track_list);
	}
}