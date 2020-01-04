<?php
namespace Lite\Logger;

class Logger{
	const DEFAULT_ID = 'DEFAULT';

	private $id;

	/**
	 * Log list, format: [[level, message, data], ...]
	 * @var array
	 */
	private $logs = [];

	private $handlers = [];

	/** @var self[] **/
	private static $instances = [];
	private static $global_handlers = [];

	public function __construct($id){
		$this->id = $id;
	}

	/**
	 * Singleton
	 * @param string $id
	 * @return Logger
	 */
	public static function instance($id){
		if(!self::$instances[$id]){
			self::$instances[$id] = new self($id);
		}
		return self::$instances[$id];
	}

	/**
	 * 获取ID
	 * @return string
	 */
	public function getIdentify(){
		return $this->id;
	}

	/**
	 * 添加log事件处理命令
	 * @param callable $handler
	 */
	public function onLogging(callable $handler){
		$this->handlers[] = $handler;
	}

	/**
	 * add Handler to all Logger instance
	 * @param callable $handler
	 */
	public static function onLoggingGlobal(callable $handler){
		self::$global_handlers[] = $handler;
	}

	public function getLogs(){
		return $this->logs;
	}

	public static function getLogsGlobal(){
		$logs = [];
		foreach(self::$instances as $logger){
			$logs[$logger->getIdentify()] = $logger->getLogs();
		}
		return $logs;
	}

	/**
	 * Get logs by level(or above)
	 * @param $check_level
	 * @param $logs
	 * @return array
	 */
	public static function getLogsByLevel($check_level, $logs){
		$levels_above = Level::getLevelsAbove($check_level);
		$rst = [];
		foreach($logs as $log){
			list($level) = $log;
			if(in_array($level, $levels_above)){
				$rst[] = $log;
			}
		}
		return $rst;
	}

	/**
	 * Fire handlers
	 * 1.last pushed handler first,
	 * 2.private handler at the fore
	 * @param $level
	 * @param $message
	 * @param $data
	 */
	private function fireHandlers($level, $message, $data){
		if($this->handlers){
			$handlers_reverse = array_reverse($this->handlers);
			foreach($handlers_reverse as $handler){
				$ret = call_user_func($handler, $level, $message, $data, $this);
				if($ret === false){
					return;
				}
			}
		}
		if(self::$global_handlers){
			$global_handlers_reverse = array_reverse(self::$global_handlers);
			foreach($global_handlers_reverse as $handler){
				$ret = call_user_func($handler, $level, $message, $data, $this);
				if($ret === false){
					return;
				}
			}
		}
	}

	/**
	 * 记录一个message日志
	 * @param $level
	 * @param $message
	 * @param array $data
	 * @return int log number
	 */
	public function log($level, $message, $data = []){
		$this->logs[] = [$level, $message, $data];
		$this->fireHandlers($level, $message, $data);
		return count($this->logs);
	}

	/**
	 * info
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function info($message, $data = []){
		return $this->log(Level::INFO, $message, $data);
	}

	/**
	 * debug
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function debug($message, $data = []){
		return $this->log(Level::DEBUG, $message, $data);
	}

	/**
	 * notice
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function notice($message, $data = []){
		return $this->log(Level::NOTICE, $message, $data);
	}

	/**
	 * warn
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function warn($message, $data = []){
		return $this->log(Level::WARNING, $message, $data);
	}

	/**
	 * error
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function error($message, $data = []){
		return $this->log(Level::ERROR, $message, $data);
	}

	/**
	 * critic
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function critic($message, $data = []){
		return $this->log(Level::CRITICAL, $message, $data);
	}

	/**
	 * alert
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function alert($message, $data = []){
		return $this->log(Level::ALERT, $message, $data);
	}

	/**
	 * emergency
	 * @param $message
	 * @param array $data
	 * @return null
	 */
	public function emergency($message, $data = []){
		return $this->log(Level::EMERGENCY, $message, $data);
	}

	/**
	 * Support call Logger::info() as default logger func
	 * @param $method
	 * @param $arguments
	 * @return mixed
	 */
	public static function __callStatic($method, $arguments){
		$default_logger = self::instance(self::DEFAULT_ID);
		return call_user_func_array([$default_logger, $method], $arguments);
	}
}
