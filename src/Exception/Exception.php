<?php
namespace Lite\Exception;

use Exception as OrgException;

/**
 * Lite框架通用异常类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
class Exception extends OrgException{
	protected $message = 'Unknown exception';     // Exception message
	protected $code = -1;                       // User-defined exception code
	protected $file;                              // Source filename of exception
	protected $line;                              // Source line of exception

	public $data;

	public function getData(){
		return $this->data;
	}

	/**
	 * 构造方法，支持传入数据
	 * @param null $message
	 * @param int $code
	 * @param null $data current context data
	 * @param null $prev_exception
	 */
	public function __construct($message = null, $code = 0, $data = null, $prev_exception = null){
		parent::__construct($message, $code, $prev_exception);
		$this->data = $data;
	}

	/**
	 * 打印异常对象message
	 * @return string
	 */
	public function __toString(){
		return $this->message;
	}
}