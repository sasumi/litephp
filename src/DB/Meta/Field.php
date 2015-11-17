<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/9/2
 * Time: 11:53
 */
namespace Lite\DB\Meta;

use Lite\DB\Model;
use function Lite\func\array_clear_null;
use function Lite\func\dump;

class Field {
	private $name = null;
	private $define = array();

	public function __construct($name, $define){
		$this->name = $name;
		$this->define = $define;
	}

	/**
	 * instance
	 * @param $name
	 * @param $define
	 * @return \Lite\DB\Meta\Field
	 */
	public static function instance($name, $define){
		return new self($name, $define);
	}

	/**
	 * 渲染成表单元素
	 * @param $value
	 * @param \Lite\DB\Model $instance
	 * @return string
	 */
	public function renderFormElement($value, Model $instance=null){
		$define = $this->define;
		$class = $define['class'];
		$rel = $define['rel'];
		$name = $this->name;
		$required = $define['required'] && !isset($define['default']);   //表单一定会提交null数据，因此需要判断default

		$html = '';
		switch($define['type']){
			case 'text':
				$html = self::buildElement('textarea', array(
					'type' => 'text',
					'rel' => $rel,
					'name' => $name,
					'class' => $class ?: 'txt small-txt',
					'text' => $value
				), $required);
				break;

			case 'enum':
				if(is_callable($define['options'])){
					$define['options'] = call_user_func($define['options'], $value, $instance);
				}
				if(count($define['options']) > 2){
					$html = '<select size="1" name="'.$name.'"'.($required ? ' required="required"' : '') .'>';
					$html .= self::buildElement('option', array(
						'value' => '',
						'rel' => $rel,
						'text' => '请选择'
					));
					foreach($define['options'] as $k=>$n){
						$attr = array(
							'value' => $k,
							'text' => $n
						);
						if($value == $k || (isset($define['default']) && $define['default'] == $k)){
							$attr['selected'] = 'selected';
						}
						$html .= self::buildElement('option', $attr);
					}
					$html .= '</select>';
				} else {
					foreach($define['options'] as $k=>$n){
						$attr = array(
							'type' => 'radio',
							'rel' => $rel,
							'name' => $name,
							'value' => $k
						);
						if($value == $k || (isset($define['default']) && $define['default'] == $k)){
							$attr['checked'] = 'checked';
						}
						$html .= '<label>'.self::buildElement('input', $attr);
						$html .= $n;
						$html .= '</label>';
					}
				}

				break;

			case 'set':
				$vs = explode(',',$value);
				if(is_callable($define['options'])){
					$define['options'] = call_user_func($define['options'], $vs, $instance);
				}
				if(count($define['options']) > 10){
					$html = '<select size="1" name="'.$name.'[]"'.($required ? ' required="required"' : '') .' multiple="multiple">';
					foreach($define['options'] as $k=>$n){
						$attr = array(
							'value' => $k,
							'text' => $n
						);
						if(in_array($k, $vs) || (isset($define['default']) && $define['default'] == $k)){
							$attr['selected'] = 'selected';
						}
						$html .= self::buildElement('option', $attr);
					}
					$html .= '</select>';
				} else {
					foreach($define['options'] as $k=>$n){
						$attr = array(
							'type' => 'checkbox',
							'rel' => $rel,
							'name' => $name.'[]',
							'value' => $k
						);
						if(in_array($k, $vs) || (isset($define['default']) && $define['default'] == $k)){
							$attr['checked'] = 'checked';
						}
						$html .= '<label>'.self::buildElement('input', $attr);
						$html .= $n;
						$html .= '</label>';
					}
				}
				break;

			case 'int':
			case 'float':
			case 'double':
				if($define['options']){
					if(is_callable($define['options'])){
						$define['options'] = call_user_func($define['options'], $value, $instance);
					}
					$html = '<select size="1" name="'.$name.'"'.($required ? ' required="required"' : '').'>';
					$html .= self::buildElement('option', array(
						'value' => '',
						'rel' => $rel,
						'text' => '请选择'
					));
					foreach($define['options'] as $k=>$n){
						$attr = array(
							'value' => $k,
							'text' => $n
						);
						if($value == $k || (isset($define['default']) && $define['default'] == $k)){
							$attr['selected'] = 'selected';
						}
						$html .= self::buildElement('option', $attr);
					}
					$html .= '</select>';
				} else {
					$tmp = array(
						'type' => 'number',
						'name' => $name,
						'rel' => $rel,
						'class' => $class ?: 'txt',
						'value' => $value
					);
					if($define['type'] == 'int'){
						$tmp['step'] = 1;
					}
					if(isset($define['min'])){
						$tmp['min'] = $define['min'];
					}
					if(isset($define['max'])){
						$tmp['max'] = $define['max'];
					}
					$html = self::buildElement('input', $tmp, $required);
				}
				break;

			case 'file':
				if(!$class){
					$class = 'txt';
				}
				$html = self::buildElement('input', array(
					'type' => 'file',
					'rel' => $rel,
					'name' => $name,
					'class' => $class,
					'value' => $value
				), $required);
				break;

			default:
				if(!$class){
					$class = 'txt';
					if($define['type'] != 'string'){
						$class .= ' '.$define['type'].'-txt';
					}
				}
				$attr = array(
					'type' => 'text',
					'rel' => $rel,
					'name' => $name,
					'class' => $class,
					'value' => $value
				);
				$pattern = self::getPattern($define);
				if($pattern){
					$attr['pattern'] = $pattern;
				}
				$html = self::buildElement('input', $attr, $required);
				break;
		}
		return $html;
	}

	private static function getPattern($define){
		switch($define['type']){
			case 'datetime':
				return'^\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}:\d{1,2}$';

			case 'date':
				return '^\d{4}\-\d{1,2}\-\d{1,2}$';

			case 'time':
				return '^\d{1,2]:\d{1,2}:\d{1,2}$';
		}
		return '';
	}

	/**
	 * 构建元素属性
	 * @param $tag
	 * @param array $attrs
	 * @param bool|false $required
	 * @return string
	 */
	private static function buildElement($tag, $attrs=array(), $required=false){
		$tag = strtolower($tag);
		$single_tag = in_array($tag, array('input', 'img'));
		$html = "<$tag ";

		$attrs = array_clear_null($attrs);
		$text = addslashes($attrs['text']);

		unset($attrs['text']);

		foreach($attrs as $k=>$v){
			if($k == 'pattern'){
				$html .= " $k=\"".$v."\"";
			} else {
				$html .= " $k=\"".addslashes($v)."\"";
			}
		}
		if($required){
			$html .= ' required="required"';
		}
		$html .= $single_tag ? "/>" : ">".$text."</$tag>";
		return $html;
	}

	/**
	 * 校验字段
	 * @param $value
	 * @return bool|string
	 */
	public function validate(&$value){
		$define = $this->define;
		$err = '';
		$val = $value;
		$name = $define['alias'];
		if(is_callable($define['options'])){
			$define['options'] = call_user_func($define['options']);
		}

		//type
		if(!$err){
			switch($define['type']){
				case 'int':
					if(!self::isInt($val)){
						$err = $name.'格式不正确';
					};
					break;

				case 'float':
				case 'double':
					if(!self::isFloat($val)){
						$err = $name.'格式不正确';
					}
					break;

				case 'enum':
					$err = !isset($define['options'][$val]);
					break;

				//string暂不校验
				case 'string':
					break;
			}
		}

		//required
		if(!$err && $define['required'] && strlen($val) == 0){
			$err = "请输入{$name}";
		}

		//length
		if(!$err && $define['length'] && $define['type'] != 'datetime' && $define['type'] != 'date' && $define['type'] != 'time'){
			$err = strlen($val) > $define['length'] ? "{$name}长度超出" :'';
		}

		if(!$err){
			$value = $val;
		}
		return $err;
	}

	public static function isInt($val){
		return $val == intval($val);
	}

	public static function isFloat($val){
		if(!self::isInt($val)){
			return $val == floatval($val);
		}
		return false;
	}
}