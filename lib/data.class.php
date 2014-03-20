<?php
/**
 * data item
 * @author sasumihuang
 */
abstract class Data implements Iterator, ArrayAccess{
	private $items = array();
	private $items_change_keys = array();

	public function __construct(array $items=array()){
		$this->setItems($items);
	}

	/**
	 * set items
	 * @param array|object $items
	 */
	final public function setItems($items=array()){
		foreach($items as $key=>$val){
			$this->setItem($key, $val);
		}
		return $this;
	}
	
	final public function addItem($key=null, $val){
		if($key == null){
			$this->items[] = $val;
		} else {
			$this->items[$key] = $val;
		}
	}

	protected function __onSetItem($key, $val){
		return true;
	}

	final public function setItem($key, $val){
		if($this->__onSetItem($key, &$val) !== false){
			$this->items[$key] = $val;
		}
	}

	final public function toArray(){
		return $this->items;
	}

	final public function toString(){
		return print_r($this->items, true);
	}

	protected function __onGetItem($key){
		return true;
	}

	final public function getitems(){
		return $this->items;
	}

	final public function getItem($key){
		if($this->__onGetItem($key) !== false){
			return $this->items[$key];
		}
	}

	protected function getItemChangekeys(){
		return $this->items_change_keys;
	}
	
	final public function size(){
		return count($this->items);
	}

	final public function __set($key, $val){
		$this->setItem($key, $val);
		$this->items_change_keys[$key] = true;
	}

	final public function __get($key){
		return $this->items[$key];
	}

	final public function __isset($key){
		return isset($this->items[$key]);
	}

	final public function __unset($key){
		unset($this->items[$key]);
	}

	final public function rewind() {
		reset($this->items);
	}

	final public function current() {
		$var = current($this->items);
		return $var;
	}

	final public function key() {
		$var = key($this->items);
		return $var;
	}

	final public function next() {
		$var = next($this->items);
		return $var;
	}

	final public function valid() {
		$var = $this->current() !== false;
		return $var;
	}

	final public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	final public function offsetExists($offset) {
		return isset($this->items[$offset]);
	}

	final public function offsetUnset($offset) {
		unset($this->items[$offset]);
	}

	final public function offsetGet($offset) {
		return isset($this->items[$offset]) ? $this->items[$offset] : null;
	}
}
?>