<?php
namespace Lite\Core;
use ArrayAccess;
use Iterator;

/**
 * 引用参数辅助类，用于支持5.3以上非直接调用不能引用传参的问题
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
class RefParam implements Iterator, ArrayAccess{
	private $data = array();

	public function __construct(array $data=array()){
		$this->data = $data;
	}

	public function __set($key, $val){
		$this->data[$key] = $val;
	}

	public function __get($key){
		return $this->data[$key];
	}

	public function set($key, $val){
		$this->data[$key] = $val;
	}

	public function get($key){
		return $this->data[$key];
	}

	public function __unset($key){
		unset($this->data[$key]);
	}

	final public function rewind() {
		reset($this->data);
	}

	final public function current() {
		$var = current($this->data);
		return $var;
	}

	final public function key() {
		$var = key($this->data);
		return $var;
	}

	final public function next() {
		$var = next($this->data);
		return $var;
	}

	final public function valid() {
		$var = $this->current() !== false;
		return $var;
	}

	final public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	final public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	final public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	final public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}
}
