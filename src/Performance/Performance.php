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
use function Lite\func\session_start_once;
use function Lite\func\session_write_once;

class Performance{
	private $saver_handler;

	public static $ignore_filter;

	const LEVEL_IGNORE = 'IGNORE';
	const LEVEL_NORMAL = 'NORMAL';
	const LEVEL_WARNING = 'WARNING';
	const LEVEL_ERROR = 'ERROR';
	const LEVEL_CRITICAL = 'CRITICAL';
	
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
		self::LEVEL_IGNORE   => '#aaa',
		self::LEVEL_NORMAL   => '#000',
		self::LEVEL_WARNING  => '#a76ece',
		self::LEVEL_ERROR    => '#ffa500',
		self::LEVEL_CRITICAL => '#ff0000',
	];

	protected static function saver($data = null){
		$cache_data_key = 'PFM_STAT_DATA';
		$cache_adapter = CacheFile::instance();
		if($data !== null){
			$cache_adapter->set($cache_data_key, $data,300);
			return true;
		} else {
			return $cache_adapter->get($cache_data_key);
		}
	}

	private function __construct(){
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
	 * 设置数据缓存处理器
	 * @param callable $saver_handler
	 */
	public function setSaverHandler($saver_handler){
		$this->saver_handler = $saver_handler;
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
	public static function getPageTimeLevel($tm_ms){
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
		if(self::$ignore_filter && is_callable(self::$ignore_filter) && call_user_func(self::$ignore_filter) === false){
			//忽略过滤器命中
			return;
		}
		$start_mem_usage = memory_get_usage(true);
		session_start_once();
		Statistics::instance()->autoCollect(function($time_list, $db_stat) use ($start_mem_usage){
			$data = [
				'time_list' => $time_list,
				'database_stat' => $db_stat,
				'page_summary' => [
					'visit_page_url' => Router::getCurrentPageUrl(),
					'visit_page_method' => $_SERVER['REQUEST_METHOD'],
					'request_time_float' => $_SERVER['REQUEST_TIME_FLOAT'],
					'app_init_time' => Application::$init_microtime,
					'server_start_memory_usage' => $start_mem_usage,
					'server_ending_memory_usage' => memory_get_usage(true),
					'statistics_ending_time' => microtime(),
					'cookie' => $_COOKIE,
					'session' => $_SESSION,
				],
			];
			call_user_func($this->saver_handler ?: 'self::saver', $data);
		});
		session_write_once();
	}
	
	/**
	 * 显示收集结果
	 * @param $type
	 * @param $ignore_rules
	 */
	public function display($type, $ignore_rules = []){
		$static_data = call_user_func($this->saver_handler ?: 'self::saver');
		include __DIR__.DIRECTORY_SEPARATOR.'display.php';
	}

	public static function auto(){
		session_start();
		$SS_KEY = '__PFM_STATUS__';
		$SS_RULES_KEY = '__PFM_RULES__';
		$status = isset($_SESSION[$SS_KEY]) ? !!$_SESSION[$SS_KEY] : null;

		$fpm_stat = isset($_GET['PFM_STAT']) ? $_GET['PFM_STAT'] : null;

		//在统计页面
		if(isset($fpm_stat)){
			$pfm = self::instance();
			if($fpm_stat == 'open' || $status === null){
				$_SESSION[$SS_KEY] = true;
				$pfm->display('open');
			}
			else if($fpm_stat == 'close'){
				$_SESSION[$SS_KEY] = false;
				$pfm->display('close');
			}
			else {
				if($_SERVER['REQUEST_METHOD'] == 'POST'){
					$_SESSION[$SS_RULES_KEY] = $_POST['ignore_rules'];
				}
				$ignore_rules = $_SESSION[$SS_RULES_KEY];
				$pfm->display('result', $ignore_rules);
			}
			exit;
		}

		//正常代码逻辑中（开启统计）
		else if($status){
			Performance::$ignore_filter = function()use($SS_RULES_KEY){
				$rules = $_SESSION[$SS_RULES_KEY] ? explode("\n", $_SESSION[$SS_RULES_KEY]) : [];
				foreach($rules as $rule){
					if(stripos($_SERVER['REQUEST_URI'], $rule) !== false){
						return false;
					}
				}
				return true;
			};
			$pfm = self::instance();
			$pfm->startCollect();
		}
	}
}
