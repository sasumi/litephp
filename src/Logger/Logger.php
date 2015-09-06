<?php
namespace Lite\Logger;
use Lite\Logger\Handler\InterfaceHandler;
use Lite\Logger\Message\AbstractMessage;
use Lite\Logger\Message\InterfaceMessage;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/14
 * Time: 17:16
 */
class Logger{
	private static $instances = array();
	private static $_default_id = 'DEFAULT';

	private $id;
	private $haystack = array();
	private $triggers = array();
	private static $global_triggers = array();

	/**
	 * 实例化
	 * @param $id
	 */
	public function __construct($id){
		$this->id = $id;
	}

	/**
	 * get instance
	 * @param $id
	 * @return \Lite\Logger\Logger
	 */
	public static function getInstance($id){
		/** @var self $ins */
		foreach(self::$instances as $ins){
			if($ins->getIdentify() == $id){
				return $ins;
			}
		}
		return array();
	}

	/**
	 * 实例化
	 * @param string $id
	 * @return Logger
	 */
	public static function instance($id){
		$id = $id ?: self::$_default_id;
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
	 * 获取调用堆栈
	 * @param int $backtrace_index
	 * @return string
	 */
	public static function getCallee($backtrace_index=1){
		$item = debug_backtrace(null, $backtrace_index);
		$item = $item[count($item)-1];
		return $item;
	}

	/**
	 * 获取客户端IP
	 * @return string
	 */
	public static function getIp() {
		$ip = '';
		if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')){
			$ip = getenv('HTTP_CLIENT_IP');
		}
		elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		}
		elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$ip = getenv('REMOTE_ADDR');
		}
		elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';
	}

	/**
	 * 添加log事件处理命令
	 * @param callable $handler
	 */
	public function addTrigger(callable $handler){
		$this->triggers[] = $handler;
	}

	/**
	 * add trigger to all Logger instance
	 * @param callable $handler
	 */
	public static function addTriggerAll(callable $handler){
		self::$global_triggers[] = $handler;
	}

	/**
	 * 收集指定level、指定ID的log信息
	 * 缺省参数将收集所有log信息
	 * @param array|null $id
	 * @param int $level
	 * @param callable $collector
	 * @internal param callable $handler
	 */
	public static function collect(callable $collector, $id = array(), $level = LoggerLevel::DEBUG){
		$id = $id ?: array();
		$id_arr = is_array($id) ? $id : array($id);
		register_shutdown_function(function () use ($id_arr, $level, $collector){
			$levels = LoggerLevel::getLevelAbove($level);
			$messages = array();
			/** @var self $ins */
			foreach(self::$instances as $ins){
				if(!$id_arr || in_array($ins->getIdentify(), $id_arr)){
					$messages = array_merge($messages, $ins->filterMessages($levels));
				}
			}

			if($messages){
				$handler = call_user_func($collector, $messages);
				if($handler instanceof InterfaceHandler){
					$handler->write($messages);
				}
			}
		});
	}

	/**
	 * 在指定级别log存在时收集信息
	 * @param $level
	 * @param callable $collector
	 */
	public static function collectOn($level, callable $collector){
		self::collect(function($messages) use ($level, $collector){
			$do_collect = false;
			$collect_if = LoggerLevel::getLevelAbove($level);

			/** @var InterfaceMessage $msg */
			foreach($messages as $msg){
				if(in_array($msg->getLevel(), $collect_if)){
					$do_collect = true;
				}
			}
			if($do_collect && $messages){
				call_user_func($collector, $messages);
			}
		});
	}

	/**
	 * 过滤message
	 * @param array $levels
	 * @return array
	 */
	public function filterMessages(array $levels){
		$ret = array();
		/** @var AbstractMessage $msg */
		foreach($this->haystack as $msg){
			if(in_array($msg->getLevel(), $levels)){
				$ret[] = $msg;
			}
		}
		return $ret;
	}

	/**
	 * 记录一个message日志
	 * @param $level
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function log($level, InterfaceMessage $message){
		$message->setIdentify($this->id);
		$message->setLevel($level);
		foreach($this->triggers as $handler){
			if(call_user_func($handler, $this, $message) === false){
				break;
			}
		}
		foreach(self::$global_triggers as $handler){
			if(call_user_func($handler, $this, $message) === false){
				break;
			}
		}
		$this->haystack[] = clone($message);
	}

	/**
	 * info
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function info(InterfaceMessage $message){
		return $this->log(LoggerLevel::INFO, $message);
	}

	/**
	 * debug
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function debug(InterfaceMessage $message){
		return $this->log(LoggerLevel::DEBUG, $message);
	}

	/**
	 * notice
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function notice(InterfaceMessage $message){
		return $this->log(LoggerLevel::NOTICE, $message);
	}

	/**
	 * warn
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function warn(InterfaceMessage $message){
		return $this->log(LoggerLevel::WARNING, $message);
	}

	/**
	 * error
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function error($message){
		return $this->log(LoggerLevel::ERROR, $message);
	}

	/**
	 * critic
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function critic(InterfaceMessage $message){
		return $this->log(LoggerLevel::CRITICAL, $message);
	}

	/**
	 * alert
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function alert(InterfaceMessage $message){
		return $this->log(LoggerLevel::ALERT, $message);
	}

	/**
	 * emergency
	 * @param InterfaceMessage $message
	 * @return null
	 */
	public function emergency(InterfaceMessage $message){
		return $this->log(LoggerLevel::EMERGENCY, $message);
	}
}