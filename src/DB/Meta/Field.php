<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/9/2
 * Time: 11:53
 */
namespace Lite\DB\Meta;

class Field {
	const TYPE_STRING = 1;
	const TYPE_INT = 2;
	const TYPE_FLOAT = 3;
	const TYPE_DOUBLE = 4;
	const TYPE_BOOL = 5;
	const TYPE_ENUM = 6;
	const TYPE_DATE = 7;
	const TYPE_DATETIME = 8;
	const TYPE_TIME = 9;
	const TYPE_TIMESTAMP = 10;

	/**
	 * 类型映射到数据库类型
	 * @var array
	 */
	private static $type_map = array(
		self::TYPE_STRING => 'varchar',
		self::TYPE_INT => 'int',
		self::TYPE_FLOAT => 'float',
		self::TYPE_DOUBLE => 'double',
		self::TYPE_BOOL => 'bool',
		self::TYPE_ENUM => 'enum',
		self::TYPE_DATE => 'date',
		self::TYPE_DATETIME => 'datetime',
		self::TYPE_TIME => 'time',
		self::TYPE_TIMESTAMP => 'timestamp'
	);

	public $primary_key;
	public $name;
	public $alias;
	public $type;
	public $require;
	public $length;
	public $auto_increment;
	public $default;

	public function __construct($primary_key=false, $definitions){
		$this->primary_key = $primary_key;
		foreach($definitions as $k=>$def){
			if(isset($this[$k])){
				$this[$k] = $def;
			}
		}
	}

	/**
	 * covert to SQL definition
	 * @return string
	 */
	public function __toString(){
		$type = self::$type_map[$this->type];
		$null = $this->require ? 'NOT NULL' : '';
		$default = $this->default ? "DEFAULT '{$this->default}'" : '';
		$comment = addslashes($this->alias);
		$sql = "`{$this->name}` $type({$this->length}) $null $default COMMENT '$comment'";
		if($this->primary_key){
			$sql .= "PRIMARY KEY (`{$this->name}`)";
		}
		return $sql;
	}
}