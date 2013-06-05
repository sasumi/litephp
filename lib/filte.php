<?php
/**
 * 过滤数据
 * @param array &$data
 * @param array $rules
**/
function filte_array(array &$data, array $rules, $throwException=true){
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
				$err_msgs[$key] = array_merge($err_msgs[$key], $e->getMsgList());
			}

			if($pass){
				$filted_data[$key] = $val;
			}
		}
	}
	$data = $filted_data;
	if(!empty($err_msgs) && $throwException){
		throw new FilteException($err_msgs,  $data, $rules);
	}
}

/**
 * 过滤一个数据项
 * @param mix &$data
 * @param string||array $rules
**/
function filte_one(&$data, $rules, $throwException=true){
	$__DEF_REG_RULES__ = array(
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
		'TRIM' => "/^\s+|\s+$/g",
		'DATE' => ''
	);
	$check = function($data, $key, $msg){
		$err_msgs = array();
		$def_reg = $__DEF_REG_RULES__[strtoupper($key)];	//内置正则规则命中

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
		} else {
			if(!call_user_func($key, $data)){
				return $msg;
			}
		}
		return null;
	};

	$err_msgs = array();
	if(is_string($rules) && $__DEF_REG_RULES__[$rules]){
		$def_reg = $__DEF_REG_RULES__[$rules];
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
		$data = null;
		throw new FilteException($err_msgs, $data, $rules);
	}
}

/**
 * 过滤器异常类
**/
class FilteException extends Exception {
	private $msg_arr;
	private $data;
	protected $rules;
	private $trace_info;

	public function __construct($msg_arr=array(), $data=array(), $rules=array()){
		$this->msg_arr = $msg_arr;
		$this->data = $data;
		$this->rules = $rules;
		$this->trace_info = debug_backtrace();
	}

	public function getMsgList(){
		return $this->msg_arr;
	}

	public function getOneMsg(){
		$tmp = array_pop($this->msg_arr);
		return is_array($tmp) ? array_shift($tmp) : $tmp;
	}

	public function dump(){
		$html .= '<b>Errors:</b><br/>';
		$html .= '<b>'.var_dump($this->msg_arr).'</b>';
		$html .= '<ul>';
		foreach($this->trace_info as $t){
			$html .= '<li>'.$t['file'].' -- &lt;'.$t['line'].'&gt;</li>';
		}
		$html .= '</ul>';
		echo $html;
	}
}
