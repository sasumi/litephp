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
}