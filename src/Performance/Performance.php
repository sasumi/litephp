<?php

namespace Lite\Performance;

/**
 * User: sasumi
 * Date: 2018-10-26
 * Time: 10:21
 */
use Lite\Cache\CacheFile;
use Lite\Core\Application;
use Lite\Core\Router;
use Lite\Core\Statistics;
use function Lite\func\format_size;
use function Lite\func\h;
use function Lite\func\microtime_diff;
use function Lite\func\microtime_to_date;
use function Lite\func\session_start_once;
use function Lite\func\session_write_once;

class Performance{
	private $saver;
	
	const LEVEL_IGNORE = 'ignore';
	const LEVEL_NORMAL = 'normal';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR = 'error';
	const LEVEL_CRITICAL = 'critical';
	
	const LEVEL_MAP = [
		self::LEVEL_IGNORE   => '正常',
		self::LEVEL_NORMAL   => '普通',
		self::LEVEL_WARNING  => '告警',
		self::LEVEL_ERROR    => '错误',
		self::LEVEL_CRITICAL => '致命',
	];
	
	/**
	 * 查询时间阈值
	 * @var array
	 */
	public static $QUERY_TIME_THRESHOLD = [
		self::LEVEL_CRITICAL => 2000,
		self::LEVEL_ERROR    => 1500,
		self::LEVEL_WARNING  => 500,
		self::LEVEL_NORMAL   => 100,
		self::LEVEL_IGNORE   => 0,
	];
	
	/**
	 * 页面时间阈值
	 * @var array
	 */
	public static $PAGE_TIME_THRESHOLD = [
		'2000' => self::LEVEL_CRITICAL,
		'1500' => self::LEVEL_ERROR,
		'500'  => self::LEVEL_WARNING,
		'100'  => self::LEVEL_NORMAL,
		'0'    => self::LEVEL_IGNORE,
	];
	
	/**
	 * 颜色配置
	 * @var array
	 */
	public static $COLOR_MAP = [
		self::LEVEL_IGNORE   => 'color:#aaa',
		self::LEVEL_NORMAL   => 'color:black',
		self::LEVEL_WARNING  => 'color:#a76ece',
		self::LEVEL_ERROR    => 'color:orange',
		self::LEVEL_CRITICAL => 'color:red',
	];
	
	private function __construct(){
		if(!$this->saver){
			$this->saver = function($data){
				$cache_data_key = 'PFM_STAT_DATA';
				$cache_adapter = CacheFile::instance();
				if($data !== null){
					$cache_adapter->set($cache_data_key, $data,300);
					return true;
				} else {
					return $cache_adapter->get($cache_data_key);
				}
			};
		}
	}
	
	/**
	 * 实例化
	 * @return \Lite\Performance\Performance
	 */
	public static function instance(){
		static $instance;
		if(!$instance){
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * 获取查询时间等级
	 * @param $tm_ms
	 * @return int|string
	 */
	public static function getQueryTimeLevel($tm_ms){
		foreach(self::$QUERY_TIME_THRESHOLD as $level => $t){
			if($tm_ms>$t){
				return $level;
			}
		}
		return self::LEVEL_IGNORE;
	}
	
	/**
	 * 获取页面时间等级
	 * @param $tm_ms
	 * @return mixed|string
	 */
	private static function getPageTimeLevel($tm_ms){
		foreach(self::$PAGE_TIME_THRESHOLD as $t => $level){
			if($tm_ms>$t){
				return $level;
			}
		}
		return self::LEVEL_IGNORE;
	}
	
	/**
	 * 开始收集信息
	 */
	public function startCollect(){
		$start_mem_usage = memory_get_usage(true);
		session_start_once();
		Statistics::instance()->autoCollect(function($time_list, $stat_data) use ($start_mem_usage){
			$pt = microtime_diff(Application::$init_microtime)*1000;
			$page_sum = [
				'访问路径'    => '<a href="'.Router::getCurrentPageUrl().'" target="_blank">'.Router::getCurrentPageUrl().'</a> ['.$_SERVER['REQUEST_METHOD'].']',
				'开始时间'    => microtime_to_date(Application::$init_microtime, 'H:i:s'),
				'结束时间'    => date('H:i:s'),
				'服务器总耗时'  => '<span class="level-'.self::getPageTimeLevel($pt).'">'.number_format($pt, 2, null, '').'ms</span>',
				'请求消耗内存'  => format_size(memory_get_usage(true)-$start_mem_usage),
				'COOKIE'  => '<textarea readonly>'.h(json_encode($_COOKIE)).'</textarea>',
				'SESSION' => '<textarea readonly>'.h(json_encode($_SESSION)).'</textarea>',
			];
			call_user_func($this->saver, [$time_list, $stat_data, $page_sum]);
		});
		session_write_once();
	}
	
	/**
	 * 显示收集结果
	 */
	public function display(){
		$data = call_user_func($this->saver);
		include __DIR__.DIRECTORY_SEPARATOR.'display.php';
	}
	
	public static function auto(){
	
	}
}