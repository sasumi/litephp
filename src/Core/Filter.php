<?php
namespace Lite\Core;

/**
 * Lite框架过滤器
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
class Filter {
	private static $instance;

	/**
	 * @var array 过滤器缺省规则
	 */
	private $DEF_FILTERS = array(
		'REQUIRE' => '/^[\s|\S]+$/',										//必填
		'CHINESE_ID' => '/^\d{14}(\d{1}|\d{4}|(\d{3}[xX]))$/',		//身份证
		'PHONE' => "/^[0-9]{7,13}$/",								//手机+固话
		'EMAIL' => '/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/',		//EMAIL
		'POSTCODE' => '/^[0-9]{6}$/',								//邮编
		'AREACODE' => '/^0[1-2][0-9]$|^0[1-9][0-9]{2}$/',			//区号
		'CT_PASSPORT' => '/^[0-9a-zA-Z]{5,40}$/',					//电信账号
		'CT_MOBILE' => '/^(13|15|18)[0-9]{9}$/',					//中国电信号码
		'QQ' => '/^\d{5,13}$/',
		'NUMBER' => '/^\d+$/',
		'KEY' => '/^\w+$/',
		'ID' => '/^\w+$/',
		'DATE' => ''
	);

	/**
	 * 构造方法
	 */
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
		$this->DEF_FILTERS['TO_TRIM'] = function(&$data){
			$data = trim($data);
			return true;
		};
		$this->DEF_FILTERS['TO_INT'] = function(&$data){
			$data = (int)$data;
			return true;
		};
		$this->DEF_FILTERS['TO_FLOAT'] = function(&$data){
			$data = (float)$data;
			return true;
		};
	}

	/**
	 * 单例初始化
	 * @return Filter
	 */
	public static function init(){
		if(!self::$instance){
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * 过滤数组
	 * @param array $data 被过滤的数据
	 * @param array $rules 过滤规则
	 * @param bool $break_on_fail 是否在异常时中断
	 * @return array
	 */
	public function filterArray(array &$data, array $rules, $break_on_fail=true){
		//human set
		if(empty($data) || empty($rules)){
			return array();
		}

		$result = array();

		//DEFAULT & VALUE
		foreach($rules as $key=>&$rule){
			if(isset($rule['DEFAULT']) && !isset($data[$key])){
				$data[$key] = $rule['DEFAULT'];
				unset($rule['DEFAULT']);
			}
			if(isset($rule['VALUE'])){
				$data[$key] = $rule['VALUE'];
				$rule['VALUE'] = array();
			}
		}

		foreach($data as $key=>$val){
			if(!array_key_exists($key, $rules)){
				unset($data[$key]);
				continue;
			}

			$msg = self::filterOne($val, $rules[$key]);		//$val 不可以使用引用模式，Deprecated: Call-time pass-by-reference has been deprecated
			$data[$key] = $val;

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
	 * 排序过滤规则，让操作类型过滤器优先执行
	 * @param array $rules
	 * @return array
	 */
	private static function sortRules(array $rules){
		$bef = $aft = array();
		foreach($rules as $key=>$rule){
			if(stripos($key, 'TO_') === 0){
				$bef[$key] = $rule;
			} else {
				$aft[$key] = $rule;
			}
		}
		return array_merge($bef, $aft);
	}

	/**
	 * 过滤单个数据
	 * @param &$data
	 * @param array array($key=>(string || callable || array $option(string || callable, arg1, arg2)),...)
	 * @return string
	 **/
	public function filterOne(&$data, array $rules=null){
		if(empty($rules)){
			return null;
		}

		$this->sortRules($rules);

		//if current isn't required, no deal while data is empty
		if(!isset($rules['REQUIRE']) && !isset($rules['TO_INT']) &&!isset($rules['TO_FLOAT'])&& !isset($data)){
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
					$msg = $option[0];
					$opt1 = $option[1];
					if(call_user_func_array($item, array(&$data, $opt1)) !== true){
						$data = null;
						return $msg;
					}

				} else if(self::isRegStr($item)){
					if(!preg_match($item, $data)){
						$data = null;
						return $option[0];
					}
				}
			}
		}

		return null;
	}
}