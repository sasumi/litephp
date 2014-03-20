<?php
class DataObject implements Iterator{
	private $datas = array();
	private $datas_change_keys = array();
	private $is_changed = false;

	public function __construct($datas){
		$this->datas = $datas;
	}

	public function toArray(){
		return $this->datas;
	}

	public function isChanged($key){
		if($key){
			return $this->datas_change_keys[$key];
		}
		return empty($this->datas_change_keys);
	}

	public function __toString(){
		return print_r($this->datas, true);
	}

	public function __call($method_name, $params){
		//TODO
	}

	public function __set($key, $val){
		$this->datas[$key] = $val;
		$this->datas_change_keys[$key] = true;
	}

	public function __get($key){
		return $this->datas[$key];
	}

	public function __isset($key){
		return isset($this->datas[$key]);
	}

	public function __unset($key){
		unset($this->datas[$key]);
	}

	public function rewind() {
		reset($this->datas);
	}

	public function current() {
		$var = current($this->datas);
		return $var;
	}

	public function key() {
		$var = key($this->datas);
		return $var;
	}

	public function next() {
		$var = next($this->datas);
		return $var;
	}

	public function valid() {
		$var = $this->current() !== false;
		return $var;
	}
}

?>