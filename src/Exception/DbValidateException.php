<?php
namespace Lite\Exception;

/**
 * 逻辑异常
 * User: sasumi
 * Date: 14/1/2015 0014
 * Time: 18:59
 */
class DbValidateException extends BizException{
	private $table;
	private $field;
	private $field_description;
	private $field_value;

	const TYPE_OVER_LENGTH = 'over length';
	const TYPE_REQUIRED = 'required';

	public function __construct($message, $code, $data, $prev_exception){
		parent::__construct($message, $code, $data, $prev_exception);
	}

}