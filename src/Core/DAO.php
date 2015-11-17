<?php
namespace Lite\Core;
use ArrayAccess as ArrayAccess;
use Iterator as Iterator;
use function Lite\func\dump;

/**
 * 数据库元数据抽象类
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
abstract class DAO implements Iterator, ArrayAccess{
	const SETTER_KEY_NAME = 'setter';
	const GETTER_KEY_NAME = 'getter';

	private $_properties_define = array();

	private $_values = array();
	private $_values_change_keys = array();

	/**
	 * 构造方法,设置元数据
	 * @param array $data
	 */
	public function __construct($data=array()){
		if($data){
			$this->setValues($data);
		}
	}

	/**
	 * 设置属性定义
	 * @param $pro_def
	 */
	public function setPropertiesDefine(array $pro_def){
		foreach($pro_def as $key=>$def){
			if(!$this->_properties_define[$key]){
				$this->_properties_define[$key] = $def;
			} else {
				foreach($def as $k=>$v){
					$this->_properties_define[$key][$k] = $v;
				}
			}
		}
	}

	/**
	 * 获取属性定义
	 * @param null $key
	 * @return array|null
	 */
	public function getPropertiesDefine($key=null){
		if(!$key){
			return $this->_properties_define;
		}
		return $this->_properties_define[$key];
	}

	/**
	 * 获取实例属性定义
	 * @return array
	 */
	public function getEntityPropertiesDefine(){
		$ret = array();
		foreach($this->_properties_define as $f=>$def){
			if($def['entity']){
				$ret[$f] = $def;
			}
		}
		return $ret;
	}

	/**
	 * setValue前序事件
	 * @param $key
	 * @param $val
	 * @return boolean
	 */
	public function onBeforeSetValue($key, $val){
		return true;
	}

	/**
	 * getValue前序事件
	 * @param $key
	 * @param $val
	 * @return bool
	 */
	public function onBeforeGetValue($key, &$val){
		return true;
	}

	/**
	 * 批量设置数据
	 * @param array $data
	 */
	final public function setValues(array $data=array()){
		foreach($data as $key=>$value){
			$this->setValue($key, $value);
		}
	}

	/**
	 * 设置单个数据
	 * @param $key
	 * @param $value
	 */
	final public function setValue($key, $value){
		$this->$key = $value;
	}

	/**
	 * 获取单个数据
	 * @param $key
	 * @return mixed
	 */
	final public function getValue($key){
		return $this->$key;
	}

	/**
	 * 获取所有数据
	 * @return array
	 */
	final public function getValues(){
		return $this->_values;
	}

	/**
	 * 转换当前数据为数组
	 * @return array
	 */
	final public function toArray(){
		return $this->_values;
	}

	/**
	 * 获取被变更过的key
	 * @return array
	 */
	protected function getValueChangeKeys(){
		return $this->_values_change_keys;
	}

	/**
	 * 重设被变更过的状态
	 * @param string $key
	 */
	public function resetValueChangeState($key=''){
		if($key){
			unset($this->_values_change_keys[$key]);
		} else {
			$this->_values_change_keys = array();
		}
	}

	/**
	 * 检测对象是否为空
	 * @return bool
	 */
	final public function isEmpty(){
		return empty($this->_values);
	}

	/**
	 * 转换对象数组为二维数组
	 * @param array $object_list
	 * @return array
	 */
	public static function convertObjectListToArray(array $object_list){
		$ret = array();
		/** @var DAO $obj */
		foreach($object_list as $obj){
			$ret[] = $obj->toArray();
		}
		return $ret;
	}

	/**
	 * 获取数据长度
	 * @return int
	 */
	final public function size(){
		return count($this->_values);
	}

	/**
	 * setter
	 * @param $key
	 * @param $value
	 */
	public function __set($key, $value){
		$rule = $this->getPropertiesDefine($key) ?: array();
		$setter = $rule[self::SETTER_KEY_NAME];
		if($setter && call_user_func_array($setter, array($value, $this)) === false){
			return;
		}
		if($this->onBeforeSetValue($key, $value) === false){
			return;
		}
		$this->_values[$key] = $value;
		$this->_values_change_keys[$key] = $key;
	}

	/**
	 * getter
	 * @param $key
	 * @return mixed
	 */
	public function __get($key){
		$rule = $this->getPropertiesDefine($key) ?: array();
		$getter = $rule[self::GETTER_KEY_NAME];
		if($getter){
			$data = call_user_func_array($getter, array($this));
			$this->_values[$key] = $data;
			return $data;
		}
		$val = $this->_values[$key];
		$this->onBeforeGetValue($key, $val);
		return $val;
	}

	public function __isset($key){
		//这个代码必须保留，否则会出现不能正确触发__get方法的情况
		$data = $this->{$key};
		return isset($this->_values[$key]);
	}

	public function __unset($key){
		unset($this->_values[$key]);
	}

	final public function rewind() {
		reset($this->_values);
	}

	final public function current() {
		$var = current($this->_values);
		return $var;
	}

	final public function key() {
		$var = key($this->_values);
		return $var;
	}

	final public function next() {
		$var = next($this->_values);
		return $var;
	}

	final public function valid() {
		$var = $this->current() !== false;
		return $var;
	}

	final public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->_values[] = $value;
		} else {
			$this->_values[$offset] = $value;
		}
	}

	final public function offsetExists($offset) {
		return isset($this->_values[$offset]);
	}

	final public function offsetUnset($offset) {
		unset($this->_values[$offset]);
	}

	final public function offsetGet($offset) {
		return isset($this->_values[$offset]) ? $this->_values[$offset] : null;
	}
}