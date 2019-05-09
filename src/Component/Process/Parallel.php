<?php
namespace Lite\Component\Process;

use Lite\Component\Cli\Console;
use Lite\Exception\Exception;

/**
 * 并发进程管理
 * 通过按照最大并发数量，拆分输入的参数集合，实现并发任务动态调度。
 */
class Parallel{
	//中断标记 - 中断剩余任务执行
	const PS_INT_CLEAN_LEFT_FLAG = 0x20160928123001;

	//中断标记 - 强制清理当前运行进程，并中断剩余任务执行
	const PS_INT_TERMINAL_FLAG = 0x20190509001;

	//状态
	const STATE_INIT = 'INIT';
	const STATE_RUNNING = 'RUNNING';
	const STATE_ALL_DONE = 'ALL_DONE';

	//事件
	const EVENT_ON_START = 'ON_START';
	const EVENT_ON_ALL_DONE = 'ON_ALL_DONE';
	const EVENT_ON_PROCESS_ERROR = 'ON_PROCESS_ERROR';
	const EVENT_ON_PROCESS_ADD = 'ON_PROCESS_ADD';
	const EVENT_ON_PROCESS_FINISH = 'ON_PROCESS_FINISH';
	const EVENT_ON_PROCESS_RUNNING = 'ON_PROCESS_RUNNING';
	const EVENT_ON_PROCESS_INTERRUPT = 'ON_PROCESS_INTERRUPT';

	//执行命令
	private $cmd;

	//剩余命令参数集合
	private $params = [];

	//当前状态
	private $state;

	//是否开启调试输出
	private $debug = false;

	//总进程执行时间
	private $total_max_execution_time = 0;

	//进程最大执行时间（超过时间主动杀死进程）
	private $process_max_execution_time = 0;

	//并发数量
	private $parallel_count = 10;

	//进程状态检测间隔时间(秒)
	private $check_interval = 1;

	//开始执行时间
	private $start_time_float = 0;

	//当前任务索引下标
	private $process_index = 0;

	//总任务数量
	private $total_task_count = 1;

	//当前正在运行进程列表，格式：[索引，进程]
	private $process_list = [];

	//运行结果，格式：[索引=>[开始时间，结束时间，结果内容],...]
	private $result_list = [];

	//回调列表
	private $callback_list = [];

	/**
	 * Parallel constructor.
	 * @param $cmd
	 * @param array $params 参数集合，进程任务分配通过参数个数进行拆分。
	 * 参数格式如:<pre>
	 * $params = [['id'=>1], ['id'=>2]]，任务将被分配到两个进程中执行
	 * </pre>
	 * @throws Exception
	 */
	public function __construct($cmd, array $params){
		if(count($params) < 1){
			throw new Exception('Parameters count must grater than 1');
		}
		$this->cmd = $cmd;
		$this->params = $params;
		$this->total_task_count = count($params);
		$this->state = self::STATE_INIT;
		$this->bindDebug();
	}

	/**
	 * 检测是否在调试过程中
	 * @return boolean
	 */
	public function isDebug(){
		return $this->debug;
	}

	/**
	 * 开启调试信息输出
	 */
	public function debugOn(){
		$this->debug = true;
	}

	/**
	 * 关闭调试信息
	 */
	public function debugOff(){
		$this->debug = false;
	}

	/**
	 * 获取设置进程池最大执行时间
	 * @return int
	 */
	public function getTotalMaxExecutionTime(){
		return $this->total_max_execution_time;
	}

	/**
	 * 设置进程池最大执行时间
	 * @param int $total_max_execution_time
	 */
	public function setTotalMaxExecutionTime($total_max_execution_time){
		$this->total_max_execution_time = $total_max_execution_time;
		set_time_limit($this->total_max_execution_time);
	}

	/**
	 * 获取设置单个进程最大执行时间
	 * @return int
	 */
	public function getProcessMaxExecutionTime(){
		return $this->process_max_execution_time;
	}

	/**
	 * 设置单个进程最大执行时间
	 * @param int $process_max_execution_time
	 */
	public function setProcessMaxExecutionTime($process_max_execution_time){
		$this->process_max_execution_time = $process_max_execution_time;
	}

	/**
	 * 获取设置进程并发数量
	 * @return int
	 */
	public function getParallelCount(){
		return $this->parallel_count;
	}

	/**
	 * 设置进程并发数量
	 * @param int $parallel_count
	 */
	public function setParallelCount($parallel_count){
		$this->parallel_count = $parallel_count;
	}

	/**
	 * 获取检测间隔时间
	 * @return int
	 */
	public function getCheckInterval(){
		return $this->check_interval;
	}

	/**
	 * 获取进程统一执行命令
	 * @return mixed
	 */
	public function getCmd(){
		return $this->cmd;
	}

	/**
	 * 设置进程状态检测时间
	 * @param int $check_interval
	 */
	public function setCheckInterval($check_interval){
		$this->check_interval = $check_interval;
	}

	/**
	 * 获取进程池总状态
	 * get master state
	 * @return mixed
	 */
	public function getState(){
		return $this->state;
	}

	/**
	 * 开始
	 */
	public function start(){
		$this->state = self::STATE_RUNNING;
		$this->start_time_float = microtime(true);
		$this->triggerEvent(self::EVENT_ON_START);
		$this->dispatch();
		$this->loopCheck();
	}

	/**
	 * 进程任务分发
	 * @return bool 任务是否分派成功
	 */
	private function dispatch(){
		if($this->params){
			$running_count = count($this->process_list);
			$start_count = min($this->parallel_count-$running_count, count($this->params));
			$now = microtime(true);

			//预计结束时间
			$tmp_per_task = ($now-$this->start_time_float)/($this->process_index+1);
			$forecast_left_time = $tmp_per_task*($this->total_task_count-$this->process_index+1);

			//进程下标
			$idx_offset = $this->total_task_count - count($this->params);
			for($i = 0; $i<$start_count; $i++){
				$cmd = Console::buildCommand($this->cmd, $this->params[$i]);
				$process = new Process($cmd);
				$this->process_list[] = [$idx_offset+$i, $process];
				$this->result_list[$idx_offset+$i] = [$now];
				$this->triggerEvent(self::EVENT_ON_PROCESS_ADD, $idx_offset+$i, $process, $forecast_left_time);
			}
			$this->params = array_slice($this->params, $this->parallel_count-$running_count);
			return true;
		}
		//no task to dispatch
		return false;
	}

	/**
	 * 心跳检测
	 */
	private function loopCheck(){
		while($this->state != self::STATE_ALL_DONE){
			if(!$this->process_list && !$this->params){
				$this->state = self::STATE_ALL_DONE;
				$this->triggerEvent(self::EVENT_ON_ALL_DONE);
				unset($this);
				break;
			}

			/** @var Process $process */
			foreach($this->process_list as $k => list($index, $process)){
				if($process->isFinished()){
					$output = $process->getOutput();
					$this->result_list[$index][1] = microtime(true);
					$this->result_list[$index][2] = $output;

					$ev = $process->isFail() ? self::EVENT_ON_PROCESS_ERROR : self::EVENT_ON_PROCESS_FINISH;
					$this->triggerEvent($ev, $index, $process);

					unset($this->process_list[$k]);
					if(strpos($output, self::PS_INT_CLEAN_LEFT_FLAG) !== false){
						$this->triggerEvent(self::EVENT_ON_PROCESS_INTERRUPT, $index, $process);
						$this->triggerEvent(self::EVENT_ON_ALL_DONE);
						unset($this);
						return false;
					}
					$this->dispatch();
				} else if($process->isRunning()){
					if($this->process_max_execution_time){
						$start_time = $this->result_list[$index][0];
						if(microtime(true) - $start_time > $this->process_max_execution_time){
							$process->terminate();
							$this->triggerEvent(self::EVENT_ON_PROCESS_INTERRUPT, $index, $process);
							continue;
						}
					}
					$this->triggerEvent(self::EVENT_ON_PROCESS_RUNNING, $index, $process);
				}
			}
			usleep($this->check_interval*1000000);
		}
		return true;
	}

	/**
	 * 触发事件
	 * @param $event
	 */
	private function triggerEvent($event){
		$args = func_get_args();
		$args = array_slice($args, 1);
		foreach($this->callback_list[$event] ?: array() as $callback){
			call_user_func_array($callback, $args);
		}
	}

	/**
	 * 事件监听
	 * @param string $event event name
	 * @param callable $handler
	 */
	public function listen($event, $handler){
		$this->callback_list[$event][] = $handler;
	}

	/**
	 * 等待结束（父进程阻塞）
	 * @param int $int
	 * @throws \Exception
	 */
	public function waitForFinish($int = 100000){
		if($this->state == self::STATE_INIT){
			throw new \Exception('master should start first');
		}
		while($this->state != self::STATE_ALL_DONE){
			usleep($int);
		}
	}

	/**
	 * 绑定调试输出
	 * （仅在开启调试时有效）
	 */
	private function bindDebug(){
		$this->listen(self::EVENT_ON_START, function(){
			$this->debug(Console::getColorString('starting', Console::FORE_COLOR_PURPLE), date('Y-m-d H:i:s'));
		});
		$this->listen(self::EVENT_ON_PROCESS_ADD, function($index, Process $process){
			$this->debug(Console::getColorString('add', Console::FORE_COLOR_PURPLE), "#$index/$this->total_task_count", "PID:{$process->getPid()}", $process->getCommand());
		});
		$this->listen(self::EVENT_ON_PROCESS_RUNNING, function($index, Process $process){
			$this->debug(Console::getColorString('running', Console::FORE_COLOR_CYAN), "#$index/$this->total_task_count", "PID:{$process->getPid()}", $process->getCommand());
		});
		$this->listen(self::EVENT_ON_PROCESS_INTERRUPT, function($index, Process $process){
			$output = $this->result_list[$index][2];
			$this->debug(Console::getColorString('interrupt', Console::FORE_COLOR_RED), "#$index/$this->total_task_count", "PID:{$process->getPid()}", $process->getCommand(), $output);
		});
		$this->listen(self::EVENT_ON_PROCESS_ERROR, function($index, Process $process){
			$output = $this->result_list[$index][2];
			$this->debug(Console::getColorString('failure', Console::FORE_COLOR_RED), "#$index/$this->total_task_count", "PID:{$process->getPid()}", $process->getCommand(), $output);
		});
		$this->listen(self::EVENT_ON_PROCESS_FINISH, function($index, Process $process){
			$output = $this->result_list[$index][2];
			$this->debug(Console::getColorString('finished', Console::FORE_COLOR_GREEN), "#$index/$this->total_task_count", "PID:{$process->getPid()}", $process->getCommand(), $output);
		});
	}

	/**
	 * 输出调试信息
	 */
	private function debug(){
		if(!$this->isDebug()){
			return;
		}
		$args = func_get_args();
		call_user_func_array([Console::class, 'debug'], $args);
	}
}