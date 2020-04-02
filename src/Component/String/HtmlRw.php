<?php
namespace Lite\Component\String;

use function LFPhp\Func\h;

/**
 * Trait HtmlRw
 * Html自动读写渲染切换
 * @package Lite\Component\String
 */
trait HtmlRw{
	use Html;

	protected static $disabled = true;

	public static function disabledAllElement(){
		static::$disabled = true;
	}

	public static function enabledAllElement(){
		static::$disabled = false;
	}

	public static function htmlRadioGroup($name, $options, $current_value = '', $wrapper_tag = '', $radio_extra_attributes = []){
		if(!static::$disabled){
			return Html::htmlRadioGroup($name, $options, $current_value, $wrapper_tag, $radio_extra_attributes);
		}
		return strlen($current_value) ? $options[$current_value] : '';
	}

	public static function htmlRadio($name, $value, $title = '', $checked = false, $attributes = []){
		if(!static::$disabled){
			return Html::htmlRadio($name, $value, $title, $checked, $attributes);
		}
		return $checked ? $title : '';
	}

	public static function htmlCheckboxGroup($name, $options, $current_value = null, $wrapper_tag = '', $checkbox_extra_attributes = []){
		if(!static::$disabled){
			return Html::htmlCheckboxGroup($name, $options, $current_value, $wrapper_tag, $checkbox_extra_attributes);
		}
		return '';
	}

	public static function htmlCheckbox($name, $value, $title = '', $checked = false, $attributes = []){
		if(!static::$disabled){
			return Html::htmlCheckbox($name, $value, $title, $checked, $attributes);
		}
		return $checked ? $title : '';
	}

	public static function htmlSelect($name, array $options, $current_value = null, $placeholder = '', $attributes = []){
		if(!static::$disabled){
			return Html::htmlSelect($name, $options, $current_value, $placeholder, $attributes);
		}

		//单层option
		if(count($options, COUNT_RECURSIVE) == count($options, COUNT_NORMAL)){
			return $options[$current_value] ?: '';
		} //optgroup支持
		else{
			foreach($options as $var1 => $var2){
				if(is_array($var2)){
					if($var2[$current_value]){
						return $var2[$current_value];
					}
				} else if($var1 == $current_value){
					return $var2;
				}
			}
		}
		return '';
	}

	/**
	 * 构建HTML节点
	 * @param string $tag
	 * @param array $attributes
	 * @param string $inner_html
	 * @return string
	 */
	public static function htmlElement($tag, $attributes = [], $inner_html = ''){
		if(!static::$disabled){
			return Html::htmlElement($tag, $attributes, $inner_html);
		}
		$tag = strtolower($tag);
		if($tag == 'input'){
			switch($attributes['type']){
				case 'text':
				case 'textarea':
				case 'number':
				case 'date':
				case 'datetime':
					return strlen($attributes['value']) ? h($attributes['value']) : '';
				default:
					break;
			}
		}
		return '';
	}
}
