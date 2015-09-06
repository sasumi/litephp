<?php
namespace Lite\Exception;
use \Exception as OrgException;

/**
 * Lite框架通用异常类
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
class Exception extends OrgException {
	protected $message = 'Unknown exception';     // Exception message
    protected $code    = -1;                       // User-defined exception code
    protected $file;                              // Source filename of exception
    protected $line;                              // Source line of exception

	public $trace_info;
	public $data;

	/**
	 * 构造方法，支持传入数据
	 * @param null $message
	 * @param int $code
	 * @param null $data current context data
	 */
	public function __construct($message=null, $code=0, $data=null){
		parent::__construct($message, 0);
		$this->trace_info = debug_backtrace();
		$this->data = $data;
	}

	/**
	 * 调试当前异常
	 * @param bool $html_format
	 * @return string
	 */
	public function dump($html_format=true){
		if($html_format){
			$html = '<div style="font-size:14px;">';
			$line = "<br/>";
		} else {
			$html = '<pre>';
			$line = "\n";
		}

		$html .= $this->message.$line;
		$html .= $this->code .$line;
		$html .= print_r($this->data, true);
		$html .= $line.$line.str_repeat('=', 80).$line;
		$c = count($this->trace_info);
		foreach($this->trace_info as $k=>$t){
			if($html_format && $this->isLibFile($t['file'])){
				$html .= '<span style="color:#ccc;">';
			}
			$html .= '[#'.($c-$k).'] '.$t['file']."({$t['line']}) ";
			$html .= $t['class'].$t['type'].$t['function']."()".$line;
			if($html_format && $this->isLibFile($t['file'])){
				$html .= '</span>';
			}
		}
		return $html;
	}

	/**
	 * 检测文件是否为Lite框架库文件
	 * @param $file
	 * @return bool
	 */
	private function isLibFile($file){
		return stripos($file, 'litephp\lib') !== false;
	}

	/**
	 * 打印异常对象message
	 * @return string
	 */
	public function __toString(){
		return $this->message;
	}
}