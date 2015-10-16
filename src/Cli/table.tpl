<?php
namespace {$namespace};

/**
 * User: Lite Scaffold
 * Date: {$generate_date}
 * Time: {$generate_time}
 */
use Lite\DB\Model as Model;

{$class_comment}
abstract class {$class_name} extends Model {
	public function __construct($data=array()){
		$this->addFilterRules(
{$filter_rules}
		);
		parent::__construct($data);
	}

	/**
	 * current model table name
	 * @return string
	 */
	public function getTableName() {
		return '{$table_name}';
	}

	/**
	 * current mode primary key
	 * @return string
	 */
	public function getPrimaryKey() {
		return '{$primary_key}';
	}
}