<?php

namespace Lite\DB\Exception;

use Lite\Exception\Exception;

/**
 * 数据库异常类
 */
class DatabaseException extends Exception {
	public $query;
	public $config;

	public function __construct($message, $query = null, $config = null, $code = null, $prev_exception = null){
		$this->query = $query;
		$this->config = $config;
		parent::__construct($message, $code, ['query' => $query, 'host' => $config], $prev_exception);
	}
}
