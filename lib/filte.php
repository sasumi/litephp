<?php
$GLOBALS['__DEF_REG_RULES__'] = array(
	'REQUIRE' => "/^.+$/",										//必填
	'CHINESE_ID' => "/^\d{14}(\d{1}|\d{4}|(\d{3}[xX]))$/",		//身份证
	'PHONE' => "/^[0-9]{7,13}$/",								//手机+固话
	'EMAIL' => "/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/",		//emali
	'POSTCODE' => "/^[0-9]{6}$/",								//邮编
	'AREACODE' => "/^0[1-2][0-9]$|^0[1-9][0-9]{2}$/",			//区号
	'CT_PASSPORT' => "/^[0-9a-zA-Z]{5,40}$/",					//电信账号
	'CT_MOBILE' => "/^(13|15|18)[0-9]{9}$/",					//中国电信号码
	'QQ' => "/^\d{5,13}$/",
	'NUMBER' => '/^\d+$/',
	'KEY' => '/^\w+$/',
	'TRIM' => "/^\s+|\s+$/",
	'ID' => "/^\w+$/",
	'DATE' => ''
);

/**
 * 过滤数据
 * @param array &$data
 * @param array $rules
**/
function filte_array(array &$data, array $rules, $throwException=true){
	if(empty($rules)){
		return $data;
	}
	$err_msgs = array();
	$filted_data = array();
	foreach($data as $key=>$val){
		if($rules[$key]){
			$pass = true;
			try {
				filte_one($val, $rules[$key]);
			} catch(Exception $e){
				$pass = false;
				if(!$err_msgs[$key]){
					$err_msgs[$key] = array();
				}
				$curr_msg = $e->getMsgList();
				$err_msgs[$key] = array_merge($err_msgs[$key], $e->getMsgList());
			}

			if($pass){
				$filted_data[$key] = $val;
			}
		}
	}

	$data = $filted_data;
	if(!empty($err_msgs) && $throwException){
		$ex = new FilteException();
		$ex->setMsgArr($err_msgs);
		$ex->setData($data);
		$ex->setRules($rules);
		throw $ex;
	}
}

/**
 * 过滤一个数据项
 * @param mix &$data
 * @param string||array $rules
**/
function filte_one(&$data, $rules, $throwException=true){
	if(empty($rules)){
		return $data;
	}
	$check = function($data, $key, $msg){
		$def_reg = $GLOBALS['__DEF_REG_RULES__'][strtoupper($key)];	//内置正则规则命中

		if($def_reg){
			if(!preg_match($def_reg, $data)){
				return $msg;
			}
		} else if(stripos($key, 'min') === 0){
			$min = (int)substr($key, 3);
			if($min && strlen($data) < $min){
				return $msg;
			}
		} else if(stripos($key, 'max') === 0){
			$max = (int)substr($key, 3);
			if($max && strlen($data) > $max){
				return $msg;
			}
		} else if(strpos('/', $key) === 0){
			if(!preg_match($key, $data)){
				return $msg;
			}
		} else if(is_callable($msg)){
			return call_user_func($msg, $data);
		}
		return null;
	};

	$err_msgs = array();
	if(is_string($rules) && $GLOBALS['__DEF_REG_RULES__'][$rules]){
		$def_reg = $GLOBALS['__DEF_REG_RULES__'][$rules];
		if(!preg_match($def_reg, $data)){
			$err_msgs = array($rules);
		}
	}
	else if(is_array($rules)){
		foreach($rules as $rule=>$msg){
			$err = $check($data, $rule, $msg);
			if($err){
				$err_msgs[] = $err;
			}
		}
	}

	if(!empty($err_msgs) && $throwException){
		$ex = new FilteException();
		$ex->setMsgArr($err_msgs);
		$ex->setData($data);
		$ex->setRules($rules);
		$data = null;
		throw $ex;
	}
}

/**
 * 过滤器异常类
**/
class FilteException extends Exception {
	protected $msg_arr;
	private $data;
	protected $rules;
	private $trace_info;
	protected $message;

	public function __construct($message=null, $code=0){
		parent::__construct($message, $code);
		$this->trace_info = debug_backtrace();
		$this->message = $this->getOneMsg();
	}

	public function setMsgArr($msg_arr){
		$this->msg_arr = $msg_arr;
	}

	public function setData($data){
		$this->data = $data;
	}

	public function setRules($rules){
		$this->rules = $rules;
	}

	public function getMsgList(){
		return $this->msg_arr;
	}

	public function getOneMsg(){
		if(!empty($this->msg_arr)){
			$tmp = array_pop($this->msg_arr);
			return is_array($tmp) ? array_shift($tmp) : $tmp;
		}
		return null;
	}

	public function __toString(){
		$html .= '<br/><b>Errors:</b><br/>';
		$html .= '<b>'.print_r($this->msg_arr, true).'</b>';
		$html .= '<ul>';
		foreach($this->trace_info as $t){
			$html .= '<li>'.$t['file'].' ['.$t['line'].']</li>';
		}
		$html .= '</ul>';
		echo $html;
	}
}
