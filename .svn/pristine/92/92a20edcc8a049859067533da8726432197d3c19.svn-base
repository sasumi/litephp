<?php
class Filter {
	private static $instance;
	private $DEF_FILTERS = array(
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
		'ID' => "/^\w+$/",
		'DATE' => ''
	);

	private function __construct(){
		$this->DEF_FILTERS['MIN'] = function($data, $i){
			return $data >= $i;
		};
		$this->DEF_FILTERS['MAX'] = function($data, $i){
			return $data <= $i;
		};
		$this->DEF_FILTERS['MAXLEN'] = function($data, $len){
			return strlen($data) <= $len;
		};
		$this->DEF_FILTERS['MINLEN'] = function($data, $len){
			return strlen($data) >= $len;
		};
		$this->DEF_FILTERS['TRIM'] = function(&$data){
			$data = trim($data);
			return true;
		};
	}

	public static function init(){
		if(!self::$instance){
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * filte array
	 * @param array &$data filte source data
	 * @param array $rules
	 * @return array exception result
	 **/
	public function filteArray(array &$data, array $rules, $break_on_fail=true){
		if(empty($data)){
			throw new LException('数据为空');
		}

		//human set
		if(empty($rules)){
			return;
		}

		$result = array();
		foreach($data as $key=>$val){
			if(!$rules[$key]){
				unset($data[$key]);
				continue;
			}
			$msg = self::filteOne($val, $rules[$key]);
			if($msg){
				$result[$key] = $msg;
				if($break_on_fail){
					break;
				}
			}
		}
		return $result;
	}

	private static function isRegStr($str){
		return strpos($str, '/') === 0;
	}

	/**
	 * 过滤一个数据项
	 * @param mix &$data
	 * @param array array($key=>(string || callable || array $option(string || callable, arg1, arg2)),...)
	 * @return string
	 **/
	public function filteOne(&$data, array $rules=array()){
		if(empty($rules)){
			return $data;
		}

		foreach($rules as $rule=>$option){
			if(is_string($option)){
				$option = array($option);
			} else if(is_callable($option)){
				$option = array($option);
			}

			if(is_callable($option[0])){
				$args = array_slice($option, 1);
				array_unshift($args, $data);
				$msg = call_user_func_array($option[0], $args);
				if($msg){
					$data = null;
					return $msg;
				}
			} else if(self::isRegStr($rule)){
				if(!preg_match($rule, $data)){
					$data = null;
					return $option[0];
				}
			} else if($this->DEF_FILTERS[$rule]){
				$item = $this->DEF_FILTERS[$rule];
				if(is_callable($item)){
					$args = array_slice($option, 1);
					array_unshift($args, $data);
					if(!call_user_func_array($item, $args)){
						$data = null;
						return $option[0];
					}
				} else if(self::isRegStr($item)){
					if(!preg_match($item, $data)){
						$data = null;
						return $option[0];
					}
				}
			}
		}
	}
}