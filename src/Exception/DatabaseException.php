<?php
namespace Lite\Exception;

/**
 * 数据库异常类
 */
class DatabaseException extends Exception {
	public $query;
	public $config;

	public function __construct($message, $query = null, $config = null, $prev_exception = null){
		$this->query = $query;
		$this->config = $config;
		parent::__construct($message, 0, ['query' => $query, 'host' => $config], $prev_exception);
	}
}