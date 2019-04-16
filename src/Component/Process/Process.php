<?php
namespace Lite\Component\Process;

/**
 * 进程类
 */
class Process{
	const STDIN = 0;
	const STDOUT = 1;
	const STDERR = 2;

	const FLAG_READ = 'r';
	const FLAG_WRITE = 'w';

	protected $command;

	/** @var resource */
	protected $process;

	/** @var resource */
	protected $stdout;

	/** @var resource */
	protected $stderr;

	/** @var string */
	private $output;

	/** @var string */
	private $error_output;

	/** @var int */
	private $status_code;

	/**
	 * @param string $cmd 程序运行命令行
	 * @param string $stdInInput
	 * @throws RunTimeException
	 */
	public function __construct($cmd, $stdInInput = null){
		$this->command = $cmd;
		$descriptors = [
			self::STDIN  => ['pipe', self::FLAG_READ],
			self::STDOUT => ['pipe', self::FLAG_WRITE],
			self::STDERR => ['pipe', self::FLAG_WRITE],
		];

		$this->process = proc_open($this->command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);

		//set no blocking for IO
		stream_set_blocking($pipes[0], 0);
		stream_set_blocking($pipes[1], 0);

		$this->pid = getmypid();
		if($this->process === false || $this->process === null){
			throw new RunTimeException("Cannot create new process: {$this->command}");
		}
		list($stdin, $this->stdout, $this->stderr) = $pipes;
		if($stdInInput){
			fwrite($stdin, $stdInInput);
		}
		fclose($stdin);
	}

	/**
	 * 获取进程ID
	 * @return mixed
	 */
	public function getPid(){
		if($this->process){
			return proc_get_status($this->process)['pid'];
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getCommand(){
		return $this->command;
	}

	public function close(){
		if(!$this->isFinished()){
			proc_close($this->process);
		}
	}

	/**
	 * 总结进程
	 */
	public function terminate(){
		if(!$this->isFinished()){
			proc_terminate($this->process);
		}
	}

	/**
	 * 进程是否正在运行
	 * @return bool
	 */
	public function isRunning(){
		if($this->status_code !== null){
			return false;
		}
		$status = proc_get_status($this->process);
		if($status['running']){
			return true;
		}
		return false;
	}

	/**
	 * 监测进程是否已经结束
	 * @return bool
	 */
	public function isFinished(){
		if($this->status_code !== null){
			return true;
		}
		$status = proc_get_status($this->process);
		if($status['running']){
			return false;
		} elseif($this->status_code === null){
			$this->status_code = (int)$status['exitcode'];
		}

		// Process outputs
		$this->output = stream_get_contents($this->stdout);
		fclose($this->stdout);
		$this->error_output = stream_get_contents($this->stderr);
		fclose($this->stderr);
		$statusCode = proc_close($this->process);
		if($this->status_code === null){
			$this->status_code = $statusCode;
		}
		$this->process = null;
		return true;
	}

	/**
	 * 阻塞等待子进程结束
	 */
	public function waitForFinish(){
		while(!$this->isFinished()){
			usleep(100);
		}
	}

	/**
	 * 获取进程输出结果（仅在进程结束后才允许获取）
	 * @return string
	 * @throws RunTimeException
	 */
	public function getOutput(){
		if(!$this->isFinished()){
			throw new RunTimeException("Cannot get output for running process");
		}
		return $this->output;
	}

	/**
	 * 获取进程错误输出结果（仅在进程结束后才允许获取）
	 * @return string
	 * @throws RunTimeException
	 */
	public function getErrorOutput(){
		if(!$this->isFinished()){
			throw new RunTimeException("Cannot get error output for running process");
		}
		return $this->error_output ?: 'no error output';
	}

	/**
	 * 获取进程状态码
	 * @return int
	 * @throws RunTimeException
	 */
	public function getStatusCode(){
		if(!$this->isFinished()){
			throw new RunTimeException("Cannot get status code for running process");
		}
		return $this->status_code;
	}

	/**
	 * @return bool
	 */
	public function isFail(){
		return $this->getStatusCode() === 1;
	}
}