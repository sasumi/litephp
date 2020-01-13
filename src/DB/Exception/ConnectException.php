<?php
namespace Lite\DB\Exception;
class ConnectException extends DatabaseException {
	public function __construct($message, $query = null, $config = null, $code = null, $prev_exception = null){
		parent::__construct($message, $query, $config, $code, $prev_exception);
	}
}
