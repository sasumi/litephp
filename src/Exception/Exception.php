<?php
namespace Lite\Exception;

use Exception as OrgException;
use Lite\Component\Server;
use function Lite\func\var_export_min;

/**
 * Lite框架通用异常类
 */
class Exception extends OrgException implements \Serializable{
	protected $message = 'Unknown exception';     // Exception message
	protected $code = -1;                         // User-defined exception code
	protected $file;                              // Source filename of exception
	protected $line;                              // Source line of exception
	public $data;

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
	 * 获取数据
	 * @return mixed
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * 转化exception到数组
	 * @param $e
	 * @return array
	 */
	public static function convertExceptionToArray(\Exception $e){
		$data = '';
		if($e instanceof self){
			$data = $e->getData();
		}
		$ret = array(
			'message'      => $e->getMessage(),
			'data'         => $data,
			'file'         => $e->getFile(),
			'code'         => $e->getCode(),
			'line'         => $e->getLine(),
			'trace_string' => $e->getTraceAsString(),
		);
		return $ret;
	}

	/**
	 * 数组格式输出
	 * @return array
	 */
	public function toArray(){
		return self::convertExceptionToArray($this);
	}

	/**
	 * 优化打印输出
	 * @param \Exception $e
	 * @param bool $return
	 * @return string | null
	 */
	public static function prettyPrint(\Exception $e, $return = false){
		$text = PHP_SAPI != 'cli' ? '<PRE>' : '';
		$text .= $e->getMessage()." [{$e->code}]".PHP_EOL;
		$text .= str_repeat('-', 80).PHP_EOL;
		$text .= "[Locate]:".$e->getFile()." #".$e->getLine().PHP_EOL;
		if($e instanceof Exception){
			$text .= "[Data]:".var_export_min($e->getData(), true).PHP_EOL;
		}
		$text .= "[Trace]:".PHP_EOL;
		$text .= $e->getTraceAsString();
		$text .= PHP_SAPI != 'cli' ? '</PRE>' : '';
		if(!$return){
			echo $text;
			return null;
		} else {
			return $text;
		}
	}
	
	/**
	 * 序列化接口
	 * @return string
	 */
	public function serialize(){
		return json_encode($this->toArray(), JSON_PRETTY_PRINT);
	}
	
	/**
	 * 反序列化接口
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * The string representation of the object.
	 * </p>
	 * @return void
	 * @since 5.1.0
	 */
	public function unserialize($serialized){
		$obj = json_decode($serialized, true);
		$this->data = $obj['data'];
		$this->code = $obj['code'];
		$this->message = $obj['message'];
		$this->file = $obj['file'];
		$this->line = $obj['line'];
	}
	
	/**
	 * 优化调试信息
	 * @return array
	 */
	public function __debugInfo(){
		return static::convertExceptionToArray($this);
	}
	
	/**
	 * 打印异常对象message
	 * @return string
	 */
	public function __toString(){
		return "{$this->message} [{$this->code}]";
	}
}