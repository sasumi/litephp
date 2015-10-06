<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/9/2
 * Time: 11:53
 */
namespace Lite\DB\Meta;

class Table {
	private $name;  //表名
	private $alias; //别名
	private $fields; //字段
	private $engine; //引擎

	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getAlias(){
		return $this->alias;
	}

	/**
	 * @return array
	 */
	public function getFields(){
		return $this->fields;
	}

	/**
	 * @return mixed
	 */
	public function getEngine(){
		return $this->engine;
	}

	/**
	 * @param mixed $engine
	 */
	public function setEngine($engine){
		$this->engine = $engine;
	}

	/**
	 * @param $name
	 * @param string $alias
	 * @param array $fields
	 */
	public function __construct($name, $alias='', array $fields=array()){
		$this->name = $name;
		$this->alias = $alias;
		foreach($fields as $field){
			$this->addField($field);
		}
	}

	public function addField(Field $field){
		$this->fields[] = $field;
	}

	public function __toString(){
		$comment = addslashes($this->alias);
		$content = [];
		foreach($this->fields as $field){
			$content[] = $field.'';
		}
		$content = join(',', $content);
		$sql = "CREATE TABLE '{$this->name}' ($content) ENGINE={$this->engine} DEFAULT CHARSET=utf8 COMMENT='$comment'";
		return $sql;
	}
}