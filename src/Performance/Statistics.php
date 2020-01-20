<?php
namespace Lite\Performance;
use Lite\Core\Application;
use Lite\Core\Hooker;
use Lite\DB\Driver\DBAbstract;
use function Lite\func\array_last;
use function Lite\func\format_size;
use function Lite\func\get_last_project_trace;
use function Lite\func\microtime_diff;
use function Lite\func\microtime_to_date;

/**
 * 统计类,提供基本统计方法
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
class Statistics {
	public $id;
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
	public static function instance($id = 'default'){
		static $instance_list;
		if(!$instance_list){
			$instance_list = [];
		}
		if(!$instance_list[$id]){
			$instance_list[$id] = new self($id);
		}
		return $instance_list[$id];
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
	 * @param null $file
	 * @param null $line
	 * @param string $callee
	 */
	public function mark($msg = '', $tag = null, $file = null, $line = null, $callee = ''){
		$now = microtime();
		$mem = memory_get_usage();
		$this->time_track_list[] = array(
			'msg'        => $msg,
			'tag'        => $tag,
			'time_point' => $now,
			'memory'     => $mem,
			'file'       => $file,
			'line'       => $line,
			'callee'     => $callee,
		);
	}

	/**
	 * 标记前位
	 * @param string $msg
	 * @param string $tag
	 * @param null $file
	 * @param null $line
	 * @param string $callee
	 */
	public function markBefore($msg='', $tag='', $file = null, $line = null, $callee = ''){
		$this->mark($msg, $tag, $file, $line, $callee);
	}

	/**
	 * 标记后位
	 * @param $msg
	 * @param $tag
	 * @param null $file
	 * @param null $line
	 * @param string $callee
	 */
	public function markAfter($msg, $tag, $file = null, $line = null, $callee = ''){
		$last_idx = count($this->time_track_list)-1;
		$last_item = $this->time_track_list[$last_idx];

		$this->mark($msg, $tag, $file, $line, $callee);
		$cur_item = array_pop($this->time_track_list);

		$last_item['time_used'] = microtime_diff($last_item['time_point'], $cur_item['time_point']);
		$last_item['mem_used'] = $cur_item['memory'] - $last_item['memory'];
		$last_item['tag'] .= " \n// ".$cur_item['tag'];
		$this->time_track_list[$last_idx]=  $last_item;
	}

	/**
	 * 自动收集统计信息，收集范围包括：数据库查询、应用关闭
	 * 注意，由于收集过程可能存在PHP进程已经输出页面信息，使用session存储需确保功能逻辑顺序正确。
	 * @param callable $finish_handler 完成收集处理函数
	 */
	public function autoCollect(callable $finish_handler){
		$db_stat = array(
			'DB_QUERY_COUNT'               => 0,
			'DB_QUERY_TIME'                => 0,
			'DB_QUERY_MEM'                 => 0,
			'DB_QUERY_DEDUPLICATION_COUNT' => 0,
		);

		Hooker::add(DBAbstract::EVENT_BEFORE_DB_QUERY, function($sql) use (&$db_stat){
			$db_stat['DB_QUERY_COUNT']++;
			$trace = get_last_project_trace();
			$callee = $trace['class'].$trace['type'].$trace['function'].'()';
			$this->mark($sql.'', 'BEFORE DB QUERY', $trace['file'], $trace['line'], $callee);
		});

		Hooker::add(DBAbstract::EVENT_AFTER_DB_QUERY, function ($sql) use (&$db_stat){
			$this->markAfter($sql.'', 'AFTER DB QUERY');
			$tmp = $this->getTimeTrackList();
			$tt = array_last($tmp);
			$db_stat['DB_QUERY_TIME'] += $tt['time_used'];
			$db_stat['DB_QUERY_MEM'] += $tt['mem_used'];
		});

		Hooker::add(DBAbstract::EVENT_ON_DB_QUERY_DISTINCT, function()use(&$db_stat){
			$db_stat['DB_QUERY_DEDUPLICATION_COUNT']++;
		});

		Hooker::add(Application::EVENT_AFTER_APP_SHUTDOWN, function() use (&$db_stat, $finish_handler){
			$pc = 0;
			if($db_stat['DB_QUERY_DEDUPLICATION_COUNT'] + $db_stat['DB_QUERY_COUNT']){
				$pc = number_format($db_stat['DB_QUERY_DEDUPLICATION_COUNT'] / ($db_stat['DB_QUERY_DEDUPLICATION_COUNT'] + $db_stat['DB_QUERY_COUNT'])*100, 2, null, '');
			}
			$msg = 'DB QUERY COUNT:'.$db_stat['DB_QUERY_COUNT'].
				"\t\t\tDB QUERY TIME:".$db_stat['DB_QUERY_TIME']."s\n".
				"DEDUPLICATION QUERY:".$db_stat['DB_QUERY_DEDUPLICATION_COUNT']."($pc%)".
				"\t\tDB QUERY COST MEM:".format_size($db_stat['DB_QUERY_MEM'])."\n\n";

			$msg .= "[PROCESS USED TIME] ".microtime_diff($_SERVER['REQUEST_TIME_FLOAT'])."s";
			$this->mark($msg, "APP SHUTDOWN");
			$finish_handler($this->time_track_list, $db_stat);
		});
	}

	/**
	 * 打印跟进事件清单
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
			$str .= "[MSG] ".$item['msg']."\t\t[time_point] ".microtime_to_date($item['time_point'])."\t\t[MEM] ".number_format($item['memory']/1024, 2, null, '')."KB\n\n";
			if($item['file']){
				$str .= "[Loc] ".$item['file'].' #'.$item['line'].' [Callee] '.$item['callee']."\n\n";
			}
			if(isset($item['time_used'])){
				$str .= "[USED TIME] ".$item['time_used']."s\t[USED MEM] ".number_format($item['mem_used']/1024, 2)."KB\n\n";
			}
		}
		if($return){
			return $str;
		} else {
			echo "<pre>\n".$str;
		}
		return null;
	}

	/**
	 * 输出标记内容
	 * @return string
	 */
	public function _toString(){
		return self::printTrackList($this->time_track_list, true);
	}
}
