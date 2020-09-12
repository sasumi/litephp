<?php
namespace Lite\Core;

use Lite\Component\String\Html;
use Lite\Component\String\HtmlOrder;
use Lite\DB\Model;
use Lite\Exception\Exception;
use function LFPhp\Func\array_unshift_assoc;
use function LFPhp\Func\h;

/**
 * 视图类，大部分方法用于在模版中直接调用
 * 注意：部分方法由于继承于路由，调用这些方法的时候，需考虑是否做htmlspecialchars保护
 * User: sasumi
 * Date: 2015/01/06
 * Time: 9:49
 */
class View extends Router{
	use Html;
	use HtmlOrder;

	private static $pre_vars = array();

	const EVENT_BEFORE_VIEW_RENDER = 'EVENT_BEFORE_VIEW_RENDER';
	const EVENT_AFTER_VIEW_RENDER = 'EVENT_AFTER_VIEW_RENDER';

	const REQ_PAGE = 'page';                //普通页面访问，直接返回模板渲染结果HTML
	const REQ_JSON = 'json';                //JSON请求,返回JSON对象字符串（数据部分)
	const REQ_JSONP = 'jsonp';              //JSONP请求,返回JSONP函数调用（数据部分)
	const REQ_FORM_SENDER = 'formsender';   //formsender请求，返回formElement方法调用（数据部分)
	const REQ_IFRAME = 'iframe';            //iframe内页面请求，返回渲染结果HTML，主要差异体现在<HTML>节点类名上
	const REQ_HTMLJSON = 'htmljson';        //封装HTML以JSON方式返回

	const REQ_LIST = [
		self::REQ_PAGE,
		self::REQ_JSON,
		self::REQ_JSONP,
		self::REQ_FORM_SENDER,
		self::REQ_IFRAME,
		self::REQ_HTMLJSON
	];

	const REQ_DES_KEY = 'ref';

	//选项转换成select控件数量，大于这个数量，options将被转换成select
	//包括checkbox也会被转换成select[multiple]
	public static $option_convert_count = 2;

	//options数据失效显示文本
	public static $option_data_invalid_text = '[该项数据已失效]';

	/** @var \Lite\Core\Result */
	private $result;

	/**
	 * 构造方法
	 * @param mixed $data 视图渲染数据
	 */
	public function __construct($data = null){
		if($data instanceof Result){
			$this->result = $data;
		} else if($data instanceof self){
			$this->result = $data->result;
		} else if(is_string($data)){
			//默认返回数组情况的标记为"成功"
			$this->result = new Result($data, true, $data);
		} else {
			$this->result = new Result('', true, $data);
		}
	}

	/**
	 * 显示集合
	 * @param $value
	 * @param $options
	 * @param \Lite\DB\Model $model_instance
	 * @return string
	 * @internal param $params
	 */
	public static function displaySet($value, $options, Model $model_instance){
		$vs = explode(',', $value);
		$t = array();
		foreach($vs as $v){
			$t[] = $options[$v];
		}
		return join(',', $t);
	}

	/**
	 * 渲染搜索表单元素
	 * @param $value
	 * @param $field
	 * @param $define
	 * @param null $model_instance
	 * @param array $extend_attr
	 * @return mixed|string
	 */
	public static function renderSearchFormElement($value, $field, $define, $model_instance = null, $extend_attr = array()){
		unset($define['rel']);
		unset($define['default']);
		unset($define['required']);

		$extend_attr = array_merge(array(
			'title' => $define['alias'],
		), $extend_attr);

		if(in_array($define['type'], ['date', 'datetime', 'time', 'timestamp'])){
			return static::renderDateRangeElement($value, $field, $define, $model_instance, $extend_attr);
		}
		if(in_array($define['type'], array('text', 'simple_rich_text', 'rich_text'))){
			$define['type'] = 'string';
		}
		if($define['options']){
			$define['options'] = is_callable($define['options']) ? call_user_func($define['options']) : $define['options'];
			array_unshift_assoc($define['options'], '', '全部'.$define['alias']);
		}
		$extend_attr = array_merge(array('placeholder' => $define['alias']), $extend_attr);
		return static::renderFormElement($value, $field, $define, $model_instance, $extend_attr, false);
	}

	/**
	 * 渲染日期范围选择
	 * @param $value_range
	 * @param $field
	 * @param $define
	 * @param null $model_instance
	 * @param array $extend_attr
	 * @return string
	 */
	public static function renderDateRangeElement($value_range, $field, $define = [], $model_instance = null, $extend_attr = array()){
		list($st, $et) = $value_range;
		$org_pl = $extend_attr['placeholder'];
		unset($extend_attr['placeholder']);
		$rel = $define['rel'];
		$start_input = static::htmlElement('input', array_merge(array(
			'type'        => 'text',
			'rel'         => $rel,
			'name'        => $field.'[]',
			'required'    => $define['required'] ? 'required' : null,
			'value'       => $st,
			'text'        => $st,
			'placeholder' => $org_pl.'开始'
		), $extend_attr));

		$end_input = static::htmlElement('input', array_merge(array(
			'type'        => 'text',
			'rel'         => $rel,
			'name'        => $field.'[]',
			'required'    => $define['required'] ? 'required' : null,
			'value'       => $et,
			'text'        => $et,
			'placeholder' => $org_pl.'结束'
		), $extend_attr));
		return $start_input.' - '.$end_input;
	}

	/**
	 * 渲染字段
	 * renderFormElement别名方法
	 * @param $field
	 * @param Model $model_instance
	 * @param array $extend_attr
	 * @param bool|true $add_default_selection
	 * @return string
	 */
	public static function renderField($field, Model $model_instance, $extend_attr = array(), $add_default_selection = true){
		$val = $model_instance->$field;
		$def = $model_instance->getPropertiesDefine($field);
		return static::renderFormElement($val, $field, $def, $model_instance, $extend_attr, $add_default_selection);
	}

	/**
	 * 获取选择选项文本
	 * @param $val
	 * @param array $options
	 * @param bool $use_invalid_text
	 * @return null
	 */
	private static function __getOptionTextByVal($val, array $options, $use_invalid_text = false){
		if(count($options) != count($options, COUNT_RECURSIVE)){
			foreach($options as $gn => $opts){
				foreach($opts as $k => $n){
					if($k == $val){
						return $n;
					}
				}
			}
		} else{
			foreach($options as $k => $n){
				if($k == $val){
					return $n;
				}
			}
		}

		if($use_invalid_text){
			return static::$option_data_invalid_text;
		}
		return null;
	}

	/**
	 * 快速渲染
	 * @param Model $model_instance
	 * @param $field
	 * @return string
	 */
	public static function renderFormElementQuick(Model $model_instance, $field){
		$define = $model_instance->getPropertiesDefine($field);
		return static::renderFormElement($model_instance->$field, $field, $define, $model_instance);
	}

	/**
	 * 渲染表单元素
	 * @param $value
	 * @param string $field
	 * @param array $define
	 * @param Model|null $model_instance 实例对象
	 * @param array $extend_attr 扩展属性
	 * @param bool $add_default_selection 是否添加默认选择选项
	 * @return string
	 */
	public static function renderFormElement($value, $field, $define, $model_instance = null, $extend_attr = array(), $add_default_selection = true){
		$rel = $define['rel'];
		$required = $define['required'];
		$readonly = $define['readonly'];
		$disabled = $define['disabled'];
		//default value
		if(isset($define['default']) && !isset($value) && $define['type'] != 'set'){
			$value = $define['default'];
		}

		//form
		if(is_callable($define['form'])){
			return call_user_func($define['form'], $value, $model_instance);
		}

		//transform closure options to array
		if(is_callable($define['options'])){
			$define['options'] = call_user_func($define['options'], $model_instance);
		}

		if(is_callable($define['group_options'])){
			$define['group_options'] = call_user_func($define['group_options'], $model_instance);
		}

		//禁用选项
		if($disabled){
			$extend_attr['disabled'] = 'disabled';
		}

		//只读
		if($readonly){
			$extend_attr['readonly'] = 'readonly';
		}

		//可选项表单（包括checkbox、select、radio）
		//optional field define
		$options = $define['options'];
		if($options){
			//集合控件特殊处理
			if($define['type'] == 'set'){
				$defaults = explode(',', $define['default']) ?: array();
				$values = isset($value) ? explode(',', $value) : null;

				$html = '';
				$disabled = false;
				foreach($options as $k => $n){
					if(is_array($n)){
						$k = $n['value'];
						$disabled = $n['disabled'];
						$n = $n['name'];
					}
					$attr = array(
						'type'  => 'checkbox',
						'rel'   => $rel,
						'name'  => $field.'[]',
						'value' => $k
					);
					if($disabled){
						$attr['disabled'] = 'disabled';
					}
					if(($values && in_array($k, $values)) || (!isset($values) && in_array($k, $defaults))){
						$attr['checked'] = 'checked';
					}

					//数据失效处理
					if($values && !in_array($k, $values)){
						$attr['text'] = static::$option_data_invalid_text;
					}

					$html .= '<label>'.static::buildElement('input', array_merge($attr, $extend_attr), $define);
					$html .= $n;
					$html .= '</label>';
				}
				return $html;
			}

			//使用select组件
			//设置组合选项|选项数量过多|默认值不在选项里面
			else if($define['group_options'] || count($options)>static::$option_convert_count || (isset($define['default']) && !$options[$define['default']])){
				//处理选项只读情况
				if($readonly){
					if(count($options) != count($options, COUNT_RECURSIVE)){
						$options = array_combine(array_column($options, 'value'), array_column($options, 'name'));
					}
					$html = '<input type="hidden" value="'.h($value).'" name="'.$field.'"/>';
					$html .= static::buildElement('input', array(
						'readonly' => 'readonly',
						'type'     => 'text',
						'value'    => self::__getOptionTextByVal($value, $options, false)
					), $define);
					return $html;
				}

				$extend_attr_str = static::htmlAttributes($extend_attr);
				$html = '<select size="1" name="'.$field.'"'.($required ? ' required="required"' : '').$extend_attr_str.'>';
				if($add_default_selection){
					$html .= static::htmlOption('请选择'.$define['alias']);
				}

				//数据失效处理
				if($value != null && strlen($value) && !isset($options[$value])){
					$html .= static::htmlOption(static::$option_data_invalid_text, $value, true);
				}

				if($define['group_options']){
					foreach($define['group_options'] as $group_name => $options){
						$html .= '<optgroup label="'.h($group_name).'">';
						$disabled = false;
						foreach($options as $k => $n){
							if(is_array($n)){
								$k = $n['value'];
								$disabled = $n['disabled'];
								$n = $n['name'];
							}
							$attr = array();
							if($disabled){
								$attr['disabled'] = 'disabled';
							}
							if((string)$value === (string)$k || (isset($define['default']) && $define['default'] == $k)){
								$attr['selected'] = 'selected';
							}
							$html .= static::htmlOption($n, $k, false, $attr);
						}
						$html .= '</optgroup>';
					}
				} else{
					$disabled = false;
					foreach($options as $k => $n){
						if(is_array($n)){
							$k = $n['value'];
							$disabled = $n['disabled'];
							$n = $n['name'];
						}
						$attr = [];
						if($disabled){
							$attr['disabled'] = 'disabled';
						}
						if((string)$value === (string)$k || (!strlen($value) && isset($define['default']) && $define['default'] == $k)){
							$attr['selected'] = 'selected';
						}
						$html .= static::htmlOption($n, $k, $attr);
					}
				}
				$html .= '</select>';
			} else{
				$html = '';
				$disabled = false;
				foreach($options as $k => $n){
					if(is_array($n)){
						$k = $n['value'];
						$disabled = $n['disabled'];
						$n = $n['name'];
					}
					$attr = array(
						'type'  => 'radio',
						'rel'   => $rel,
						'name'  => $field,
						'value' => $k
					);
					if($disabled){
						$attr['disabled'] = 'disabled';
					}
					if((string)$value == (string)$k || (!strlen($value) && isset($define['default']) && $define['default'] == $k)){
						$attr['checked'] = 'checked';
					}

					//数据失效处理
					if($value != null && strlen($value) && !isset($options[$value])){
						$attr['text'] = static::$option_data_invalid_text;
					}

					$html .= '<label>'.static::buildElement('input', array_merge($attr, $extend_attr), $define);
					$html .= $n;
					$html .= '</label>';
				}
			}
			return $html;
		}

		switch($define['type']){
			case 'text':
			case 'simple_rich_text':
			case 'rich_text':
				$html = static::buildElement('textarea', array_merge(array(
					'type'     => 'text',
					'rel'      => $rel,
					'name'     => $field,
					'value'    => $value,
					'required' => $define['required'] ? 'required' : null,
				), $extend_attr), $define);
				break;

			case 'int':
			case 'float':
			case 'decimal':
			case 'double':
				$attr = array(
					'type'  => 'number',
					'name'  => $field,
					'rel'   => $rel,
					'value' => $value
				);
				if($define['type'] == 'int'){
					$attr['step'] = 1;
				} else if($define['precision']){
					$attr['step'] = '0.'.str_repeat('0', $define['precision']-1).'1';
				}

				if(isset($define['min'])){
					$attr['min'] = $define['min'];
				}
				if(isset($define['max'])){
					$attr['max'] = $define['max'];
				}
				$html = static::buildElement('input', array_merge($attr, $extend_attr), $define);
				break;

			case 'file':
				$html = static::buildElement('input', array_merge(array(
					'type'  => 'file',
					'rel'   => $rel,
					'name'  => $field,
					'value' => $value
				), $extend_attr), $define);
				break;

			case 'password':
				$attr = array(
					'type'  => 'password',
					'rel'   => $rel,
					'name'  => $field,
					'value' => $value,
				);
				$html = static::buildElement('input', array_merge($attr, $extend_attr), $define);
				break;

			default:
				$attr = array(
					'type'      => 'text',
					'maxlength' => $define['length'] ?: null,
					'rel'       => $rel,
					'name'      => $field,
					'value'     => $value
				);
				$html = static::buildElement('input', array_merge($attr, $extend_attr), $define);
				break;
		}
		return $html;
	}

	/**
	 * 构建元素属性
	 * @param $tag
	 * @param array $attributes
	 * @param array $define
	 * @return string
	 */
	public static function buildElement($tag, $attributes = array(), $define = array()){
		$attributes['required'] = $define['required'] ? 'required' : null;
		$inner_html = htmlspecialchars($attributes['value']);
		return static::htmlElement($tag, $attributes, $inner_html);
	}

	/**
	 * 遍历显示的字段
	 * @param $callback callback(alias, value, field_name)
	 * @param \Lite\DB\Model $model_instance
	 * @param array $fields
	 */
	public static function walkDisplayProperties(callable $callback, Model $model_instance = null, $fields = array()){
		if(!$model_instance){
			return;
		}

		$defines = $model_instance->getPropertiesDefine();
		if($fields){
			foreach($fields as $field){
				$alias = $defines[$field]['alias'];
				if($alias){
					call_user_func($callback, $alias, static::displayField($field, $model_instance), $field);
				}
			}
		} else{
			foreach($defines as $field => $define){
				if(!$define['primary']){
					$alias = $defines[$field]['alias'];
					if($alias){
						call_user_func($callback, $alias, static::displayField($field, $model_instance), $field);
					}
				}
			}
		}
	}

	/**
	 * 显示字段
	 * @param string $field
	 * @param Model $model_instance
	 * @return string|false
	 */
	public static function displayField($field, Model $model_instance = null){
		if(!$model_instance){
			return false;
		}
		$define = $model_instance->getPropertiesDefine($field);
		$value = $model_instance->$field;

		if(isset($define['display']) && $define['display']){
			if(is_callable($define['display'])){
				$define['display'] = call_user_func($define['display'], $model_instance);
			}
			return $define['display'];
		}

		if(isset($define['options']) && $define['options']){
			if(is_callable($define['options'])){
				$define['options'] = call_user_func($define['options'], $model_instance);
			}
			if($define['type'] == 'set'){
				return static::displaySet($value, $define['options'], $model_instance);
			} else{
				$value = $define['options'][$value];
			}
		}

		switch($define['type']){
			//hide password display in form field
			case 'password':
				return '******';

			case 'text':
			case 'string':
			case 'float':
			case 'decimal':
			case 'double':
			case 'int':
				return h($value);

			case 'date':
			case 'datetime':
			case 'timestamp':
				if(strpos($value, '0000-00-00') !== false){
					return '-';
				}
				return $value;

			case 'microtime':
				if($value == 0){
					return '-';
				}
				list($t, $s) = explode('.', $value);
				$s = (int)$s;
				return date('Y-m-d H:i:s', $t).($s ? ' '.$s : '');

			case 'file':
			case 'set':
			case 'enum':
			default:
				return $value;
		}
	}

	/**
	 * 渲染视图数据
	 * @param string||array $key
	 * @param string $val
	 */
	public function assign($key, $val = null){
		if(isset($val)){
			$this->result->setItem($key, $val);
		} else{
			foreach($key as $k => $v){
				$this->result->setItem($k, $v);
			}
		}
	}

	/**
	 * 预渲染视图数据，用于系统全局前置变量的渲染
	 * @param array $pre_vars
	 */
	public static function preAssignVar(array $pre_vars){
		self::$pre_vars = array_merge(self::$pre_vars, $pre_vars);
	}

	/**
	 * 获取前置视图变量
	 * @return array
	 */
	public static function getPreVar(){
		return self::$pre_vars;
	}

	/**
	 * 解析当前请求类型
	 * @return string
	 */
	public static function parseRequestType(){
		$type = Router::get(self::REQ_DES_KEY);
		if(empty($type) || !in_array($type, self::REQ_LIST)){
			$type = self::REQ_PAGE;
		}
		return $type;
	}

	/**
	 * 设置渲染数据
	 * @param Result $result
	 */
	public function setResult(Result $result){
		$this->result = $result;
	}

	/**
	 * 获取渲染的数据
	 * @return Result
	 */
	public function getResult(){
		return $this->result;
	}

	/**
	 * 获取数据
	 * @param null $key
	 * @param bool $html_escape 默认强制view进行html编码保护
	 * @return array
	 */
	public function getData($key = null, $html_escape = true){
		$data = array();
		if($this->result->getData()){
			$data = array_merge(self::$pre_vars, $this->result->getData());
		}
		if($html_escape){
			$data = h($data);
		}
		if(!$key){
			return $data;
		}
		return $data[$key];
	}

	/**
	 * 渲染模版
	 * @param string $file 文件名称
	 * @param bool $return 是否以返回方式返回渲染结果
	 * @param null $req_type 请求类型，缺省采用自动解析请求类型
	 * @return string
	 * @throws \Exception
	 */
	public function render($file = null, $return = false, $req_type = null){
		$result = $this->result;
		$jump_url = $result->getJumpUrl();
		$html = '';

		Hooker::fire(self::EVENT_BEFORE_VIEW_RENDER);

		if($req_type === null || !in_array($req_type, self::REQ_LIST)){
			$req_type = self::parseRequestType();
		}

		switch($req_type){
			case self::REQ_FORM_SENDER:
				$html = $result->getIframeResponse();
				break;

			case self::REQ_JSON:
				$html = $result->getJSON();
				break;

			case self::REQ_JSONP:
				$html = $result->getJSONP();
				break;

			case self::REQ_HTMLJSON:
			case self::REQ_PAGE:
			case self::REQ_IFRAME:
			default:
				$data = $result->getData();
				$resolved_file = static::resolveTemplate($file);
				if($file && !$resolved_file){
					throw new Exception('Template file not found:'.$file);
				}

				if($resolved_file){
					$ob_level = ob_get_level();
					ob_start();
					if(is_array(self::$pre_vars)){
						extract(self::$pre_vars);
					}
					if(is_array($data)){
						extract($data);
					}
					try{
						include $resolved_file;
					} catch(\Exception $e){
						while(ob_get_level()>$ob_level){
							ob_end_clean();
						}
						throw $e;
					}
					$html = ob_get_clean();
				}
				if($jump_url){
					$html .= '<script>';
					$html .= ($jump_url ? 'location.href="'.addslashes($jump_url).'";' : '');
					$html .= '</script>';
				}

				if($req_type == self::REQ_HTMLJSON){
					$data = $result->getObject();
					$data['data'] = $html;
					$html = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				}
				break;
		}

		//引用方式抛事件
		$ref_param = new RefParam(array('html' => $html, 'return' => $return));
		Hooker::fire(self::EVENT_AFTER_VIEW_RENDER, $ref_param);
		if($return){
			return $ref_param['html'];
		} else{
			echo $ref_param['html'];
			return '';
		}
	}
	
	/**
	 * 获取模版文件路径
	 * @param string $file_name
	 * @return string 文件路径
	 * @throws \Lite\Exception\Exception
	 */
	public static function resolveTemplate($file_name = null){
		$tpl_path = Config::get('app/tpl');
		if(is_file($file_name)){
			return $file_name;
		}
		if($file_name){
			$file_name = trim(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $file_name), DIRECTORY_SEPARATOR);
			return is_file($tpl_path.$file_name) ? $tpl_path.$file_name : null;
		}

		$ctrl = static::getControllerAbbr();
		$action = static::getAction();
		if(strcasecmp($action, static::getDefaultAction()) == 0){
			$file = $ctrl.'.php';
		} else{
			$file = $ctrl.'_'.$action.'.php';
		}
		$file = Config::get('app/tpl').strtolower($file);
		$file2 = Config::get('app/tpl').strtolower(static::getCurrentUri()).'.php';
		$f = is_file($file) ? $file : (is_file($file2) ? $file2 : '');
		return $f;
	}

	/**
	 * include template without variable pollute
	 * @param $file_name
	 * @param array $_parameters_
	 */
	public static function includeTemplate($file_name, $_parameters_ = []){
		if($_parameters_){
			extract($_parameters_);
		}
		$file = static::resolveTemplate($file_name);
		if(!is_file($file)){
			throw new Exception("Template file no exists:$file($file_name)");
		}
		include $file;
	}

	/**
	 * 获取静态资源url，重载路由方法，保护html输出
	 * @param string $file_name
	 * @param null $type
	 * @return string
	 */
	public static function getStaticUrl($file_name, $type = null){
		$url = call_user_func_array('parent::getStaticUrl', func_get_args());
		return htmlspecialchars($url);
	}

	/**
	 * 获取url，重载路由方法，保护html输出
	 * @param string $target
	 * @param array $params
	 * @return string
	 */
	public static function getUrl($target = '', $params = array()){
		$url = call_user_func_array('parent::getUrl', func_get_args());
		return htmlspecialchars($url);
	}

	/**
	 * 获取$_GET参数，保护html输出
	 * @param null $key
	 * @return array|string
	 */
	public static function get($key = null){
		return h(parent::get($key));
	}

	/**
	 * 获取$_POST，保护html输出
	 * @param null $key
	 * @return string
	 */
	public static function post($key = null){
		return h(parent::post($key));
	}

	/**
	 * 获取$_REQUEST，保护html输出
	 * @param null $key
	 * @return string
	 */
	public static function request($key = null){
		return h(parent::request($key));
	}

	/**
	 * 获取当前action页面url，重载路由方法，保护html输出
	 * @param array $param
	 * @return string
	 */
	public static function getCurrentActionPageUrl($param = array()){
		//由于parent::getCurrentActionPageUrl会重新调 static::getUrl，已经做了相应的转码，因此这里不再需要转码
		return call_user_func_array('parent::getCurrentActionPageUrl', func_get_args());
	}

	/**
	 * 获取js资源url，重载路由方法，保护html输出
	 * @param string $file_name
	 * @return string
	 */
	public static function getJsUrl($file_name){
		$url = call_user_func_array('parent::getJsUrl', func_get_args());
		return htmlspecialchars($url, ENT_QUOTES);
	}

	/**
	 * 调用css路径，重载路由方法，保护html输出
	 * @param string $css
	 * @return string
	 */
	public static function getCssUrl($css){
		$url = call_user_func_array('parent::getCssUrl', func_get_args());
		return htmlspecialchars($url, ENT_QUOTES);
	}

	/**
	 * 调用img路径，重载路由方法，保护html输出
	 * @param string $file_name
	 * @return string
	 */
	public static function getImgUrl($file_name){
		$url = call_user_func_array('parent::getImgUrl', func_get_args());
		return htmlspecialchars($url, ENT_QUOTES);
	}

	/**
	 * 调用flash路径，重载路由方法，保护html输出
	 * @param string $file_name
	 * @return string
	 */
	public static function getFlashUrl($file_name){
		$url = call_user_func_array('parent::getFlashUrl', func_get_args());
		return htmlspecialchars($url, ENT_QUOTES);
	}

	/**
	 * 获取脚本链接代码
	 * @param string||array $js
	 * @return string
	 **/
	public static function getJs($js/**, $js2, $js3...*/){
		$args = func_get_args();
		$rst = '';
		foreach($args as $js){
			if(gettype($js) == 'string'){
				$js = Router::getJsUrl($js);
				$rst .= static::htmlJs($js);
			} else{
				$js['src'] = Router::getJsUrl($js['src']);
				static::htmlJs('', $js);
			}
		}
		return $rst;
	}

	/**
	 * 获取样式表链接代码
	 * @param string||array $css
	 * @return string
	 **/
	public static function getCss($css/**, $css2, $css3...*/){
		$args = func_get_args();
		$rst = '';
		foreach($args as $css){
			if(gettype($css) == 'string'){
				$rst .= static::htmlCss(Router::getCssUrl($css));
			} else{
				$css['href'] = Router::getCssUrl($css['href']);
				$rst .= static::htmlCss('', $css);
			}
		}
		return $rst;
	}
	
	/**
	 * 获取IMG代码
	 * @param string $src 图片src
	 * @param array $option 选项
	 * @return string
	 */
	public static function getImg($src, $option = array()){
		$attributes = [
			'src' => Router::getImgUrl($src)
		];
		$adjust = false;
		foreach($option as $key => $val){
			if(preg_match("/(min-height|min-width|max-height|max-width)/i", $key)){
				$adjust = true;
				$attributes['data-'.$key] = $val;
			} else{
				$attributes[$key] = $val;
			}
		}
		$option['onload'] = $option['onload'] ?: ($adjust ? '(function(img){window.__img_adjust__ &&　__img_adjust__(img);})(this)' : null);
		$option['onerror'] = $option['onerror'] ?: ($adjust ? '(function(img){window.__img_error__ &&　__img_error__(img);})(this)' : null);
		return static::htmlElement('img', $attributes);
	}
}
