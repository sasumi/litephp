<?php
namespace Lite\Component\String;

use function Lite\func\array_clear_null;
use function Lite\func\array_first;
use function Lite\func\h;
use function Lite\func\ha;
use function Lite\func\substr_utf8;

/**
 * Trait Html
 * HTML字符串相关操作类封装
 * @package Lite\Component\String
 */
trait Html{
	/**
	 * html单标签节点列表
	 * @var array
	 */
	protected static $SELF_CLOSING_TAGS = [
		'area',
		'base',
		'br',
		'col',
		'embed',
		'hr',
		'img',
		'input',
		'link',
		'meta',
		'param',
		'source',
		'track',
		'wbr',
		'command',
		'keygen',
		'menuitem',
	];

	/**
	 * 构建select节点，支持optgroup模式
	 * @param string $name
	 * @param array $options 选项数据，
	 * 如果是分组模式，格式为：[value=>text, label=>options, ...]
	 * 如果是普通模式，格式为：options: [value1=>text, value2=>text,...]
	 * @param string|array $current_value
	 * @param string $placeholder
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlSelect($name, array $options, $current_value = null, $placeholder = '', $attributes = []){
		$attributes = array_merge($attributes, [
			'name'        => $name ?: null,
			'placeholder' => $placeholder ?: null
		]);

		//多选
		if(is_array($current_value)){
			$attributes['multiple'] = 'multiple';
		}

		$option_html = $placeholder ? static::htmlOption($placeholder, '') : '';

		//单层option
		if(count($options, COUNT_RECURSIVE) == count($options, COUNT_NORMAL)){
			$option_html .= static::htmlOptions($options, $current_value);
		}

		//optgroup支持
		else{
			foreach($options as $var1 => $var2){
				if(is_array($var2)){
					$option_html .= static::htmlOptionGroup($var1, $var2, $current_value);
				} else {
					$option_html .= static::htmlOption($var2, $var1, $current_value);
				}
			}
		}
		return static::htmlElement('select', $attributes, $option_html);
	}

	/**
	 * 构建select选项
	 * @param array $options [value=>text,...] option data 选项数组
	 * @param string|array $current_value 当前值
	 * @return string
	 */
	public static function htmlOptions(array $options, $current_value = null){
		$html = '';
		foreach($options as $val => $ti){
			$html .= static::htmlOption($ti, $val, self::htmlValueCompare($val, $current_value));
		}
		return $html;
	}

	/**
	 * 构建option节点
	 * @param string $text 文本，空白将被转义成&nbsp;
	 * @param string $value
	 * @param bool $selected
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlOption($text, $value = '', $selected = false, $attributes = []){
		return static::htmlElement('option', array_merge([
			'selected' => $selected ?: null,
			'value'    => $value
		], $attributes), self::htmlFromText($text));
	}

	/**
	 * 构建optgroup节点
	 * @param string $label
	 * @param array $options
	 * @param string|array $current_value 当前值
	 * @return string
	 */
	public static function htmlOptionGroup($label, $options, $current_value = null){
		$option_html = static::htmlOptions($options, $current_value);
		return static::htmlElement('optgroup', ['label' => $label], $option_html);
	}

	/**
	 * 构建textarea
	 * @param string $name
	 * @param string $value
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlTextArea($name, $value = '', $attributes = []){
		$attributes['name'] = $name;
		return static::htmlElement('textarea', $attributes, htmlspecialchars($value));
	}

	/**
	 * 构建hidden表单节点
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	public static function htmlHidden($name, $value = ''){
		return static::htmlElement('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
	}

	/**
	 * 构建数据hidden列表
	 * @param array $data_list 数据列表（可以多维数组）
	 * @return string
	 */
	public static function htmlHiddenList($data_list){
		$html = '';
		$entries = explode('&', http_build_query($data_list));
		foreach($entries as $entry){
			list($key, $value) = explode('=', $entry);
			$html .= static::htmlHidden(urldecode($key), urldecode($value)).PHP_EOL;
		}
		return $html;
	}

	/**
	 * 构建Html数字输入
	 * @param string $name
	 * @param string $value
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlNumber($name, $value = '', $attributes = []){
		$attributes['type'] = 'number';
		$attributes['name'] = $name;
		$attributes['value'] = $value;
		return static::htmlElement('input', $attributes);
	}

	/**
	 * @param string $name
	 * @param array $options 选项[value=>title,...]格式
	 * @param string $current_value
	 * @param string $wrapper_tag 每个选项外部包裹标签，例如li、div等
	 * @param array $radio_extra_attributes 每个radio额外定制属性
	 * @return string
	 */
	public static function htmlRadioGroup($name, $options, $current_value = '', $wrapper_tag = '', $radio_extra_attributes = []){
		$html = [];
		foreach($options as $val=>$ti){
			$html[] = static::htmlRadio($name, $val, $ti, self::htmlValueCompare($val, $current_value), $radio_extra_attributes);
		}

		if($wrapper_tag){
			$rst = '';
			foreach($html as $h){
				$rst .= ' '.static::htmlElement($wrapper_tag, [], $h);
			}
			return $rst;
		} else {
			return join(' ', $html);
		}
	}

	/**
	 * 构建 radio按钮
	 * 使用 label>(input:radio+{text}) 结构
	 * @param string $name
	 * @param mixed $value
	 * @param string $title
	 * @param bool $checked
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlRadio($name, $value, $title='', $checked = false, $attributes = []){
		$attributes['type'] = 'radio';
		$attributes['name'] = $name;
		$attributes['value'] = $value;
		if($checked){
			$attributes['checked'] = 'checked';
		}
		return static::htmlElement('label', [], static::htmlElement('input', $attributes).$title);
	}

	/**
	 * @param string $name
	 * @param array $options 选项[value=>title,...]格式
	 * @param string|array $current_value
	 * @param string $wrapper_tag 每个选项外部包裹标签，例如li、div等
	 * @param array $checkbox_extra_attributes 每个checkbox额外定制属性
	 * @return string
	 */
	public static function htmlCheckboxGroup($name, $options, $current_value = null, $wrapper_tag = '', $checkbox_extra_attributes = []){
		$html = [];
		foreach($options as $val=>$ti){
			$html[] = static::htmlCheckbox($name, $val, $ti, self::htmlValueCompare($val, $current_value), $checkbox_extra_attributes);
		}
		if($wrapper_tag){
			$rst = '';
			foreach($html as $h){
				$rst .= ' '.static::htmlElement($wrapper_tag, [], $h);
			}
			return $rst;
		} else {
			return join(' ', $html);
		}
	}

	/**
	 * 构建 checkbox按钮
	 * 使用 label>(input:checkbox+{text}) 结构
	 * @param string $name
	 * @param mixed $value
	 * @param string $title
	 * @param bool $checked
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlCheckbox($name, $value, $title = '', $checked = false, $attributes = []){
		$attributes['type'] = 'checkbox';
		$attributes['name'] = $name;
		$attributes['value'] = $value;
		if($checked){
			$attributes['checked'] = 'checked';
		}
		$checkbox = static::htmlElement('input', $attributes);
		if(!$title){
			return $checkbox;
		}
		return static::htmlElement('label', [], $checkbox.$title);
	}

	/**
	 * 获取HTML摘要信息
	 * @param string $html_content
	 * @param int $len
	 * @return string
	 */
	public static function htmlAbstract($html_content, $len = 200){
		$str = str_replace(array("\n", "\r"), "", $html_content);
		$str = preg_replace('/<br([^>]*)>/i', '$_NEW_LINE_', $str);
		$str = strip_tags($str);
		$str = html_entity_decode($str, ENT_QUOTES);
		$str = h($str, $len);
		$str = str_replace('$_NEW_LINE_', '<br/>', $str);

		//移除头尾空白行
		$str = preg_replace('/^(<br[^>]*>)*/i', '', $str);
		$str = preg_replace('/(<br[^>]*>)*$/i', '', $str);
		return $str;
	}

	/**
	 * 构建Html input:text文本输入框
	 * @param string $name
	 * @param string $value
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlText($name, $value = '', $attributes = []){
		$attributes['type'] = 'text';
		$attributes['name'] = $name;
		$attributes['value'] = $value;
		return static::htmlElement('input', $attributes);
	}
	
	/**
	 * 构建Html日期输入框
	 * @param string $name
	 * @param string $date_or_timestamp
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlDate($name, $date_or_timestamp = '', $attributes = []){
		$attributes['type'] = 'date';
		$attributes['name'] = $name;
		$attributes['value'] = is_numeric($date_or_timestamp) ? date('Y-m-d', $date_or_timestamp) :
			date('Y-m-d', strtotime($date_or_timestamp));
		return static::htmlElement('input', $attributes);
	}
	
	/**
	 * 构建Html日期+时间输入框
	 * @param string $name
	 * @param string $datetime_or_timestamp
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlDateTime($name, $datetime_or_timestamp = '', $attributes = []){
		$attributes['type'] = 'datetime-local';
		$attributes['name'] = $name;
		$attributes['value'] = is_numeric($datetime_or_timestamp) ? date('Y-m-dTH:i', $datetime_or_timestamp) :
			date('Y-m-d', strtotime($datetime_or_timestamp));
		return static::htmlElement('input', $attributes);
	}
	
	/**
	 * 构建Html月份选择器
	 * @param string $name
	 * @param int|null $current_month 当前月份，范围1~12表示
	 * @param string $format 月份格式，与date函数接受格式一致
	 * @param array $attributes 属性
	 * @return string
	 */
	public static function htmlMonthSelect($name, $current_month = null, $format = 'm', $attributes = []){
		$opts = [];
		$format = $format ?: 'm';
		for($i=1; $i<=12; $i++){
			$opts[$i] = date($format, strtotime('1970-'.$current_month.'-01'));
		}
		return self::htmlSelect($name, $opts, $current_month, $attributes['placeholder'], $attributes);
	}
	
	/**
	 * 构建Html年份选择器
	 * @param string $name
	 * @param int|null $current_year 当前年份
	 * @param int $start_year 开始年份（缺省为1970）
	 * @param string $end_year 结束年份（缺省为今年）
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlYearSelect($name, $current_year = null, $start_year = 1970, $end_year = '', $attributes = []){
		$start_year = $start_year ?: 1970;
		$end_year = $end_year ?: date('Y');
		$opts = [];
		for($i = $start_year; $i<=$end_year; $i++){
			$opts[$i] = $i;
		}
		return self::htmlSelect($name, $opts, $current_year, $attributes['placeholder'], $attributes);
	}

	/**
	 * 构建HTML节点
	 * @param string $tag
	 * @param array $attributes
	 * @param string $inner_html
	 * @return string
	 */
	public static function htmlElement($tag, $attributes = [], $inner_html = ''){
		$tag = strtolower($tag);
		$single_tag = in_array($tag, static::$SELF_CLOSING_TAGS);
		$html = "<$tag ";

		//针对textarea标签，识别value填充到inner_html中
		if($tag === 'textarea' && isset($attributes['value'])){
			$inner_html = $inner_html ?: h($attributes['value']);
			unset($attributes['value']);
		}

		$html .= static::htmlAttributes($attributes);
		$html .= $single_tag ? "/>" : ">".$inner_html."</$tag>";
		return $html;
	}

	/**
	 * 构建HTML链接
	 * @param string $inner_html
	 * @param string $href
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlLink($inner_html, $href = '', $attributes = []){
		$attributes['href'] = $href;
		return static::htmlElement('a', $attributes, $inner_html);
	}

	/***
	 * 构建css节点
	 * @param string $href
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlCss($href, $attributes = []){
		return static::htmlElement('link', array_merge([
			'type'  => 'text/css',
			'rel'   => 'stylesheet',
			'media' => 'all',
			'href'  => $href
		], $attributes));
	}

	/***
	 * 构建js节点
	 * @param string $src
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlJs($src, $attributes = []){
		return static::htmlElement('script', array_merge([
			'type'    => 'text/javascript',
			'charset' => 'utf-8',
			'src'     => $src,
		], $attributes));
	}

	/**
	 * 构建Html日期输入
	 * @param string $name
	 * @param string $value
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlDateInput($name, $value = '', $attributes = []){
		$attributes['type'] = 'date';
		$attributes['name'] = $name;
		$attributes['value'] = ($value && strpos($value, '0000') !== false) ? date('Y-m-d', strtotime($value)) : '';
		return static::htmlElement('input', $attributes);
	}

	/**
	 * 构建Html时间输入
	 * @param string $name
	 * @param string $value
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlDateTimeInput($name, $value = '', $attributes = []){
		$attributes['type'] = 'datetime-local';
		$attributes['name'] = $name;
		$attributes['value'] = ($value && strpos($value, '0000') !== false) ? date('Y-m-d H:i:s', strtotime($value)) : '';
		return static::htmlElement('input', $attributes);
	}

	/**
	 * 构建DataList
	 * @param string $id
	 * @param array $data [val=>title,...]
	 * @return string
	 */
	public static function htmlDataList($id, $data = []){
		$opts = '';
		foreach($data as $value=>$label){
			$opts .= '<option value="'.ha($value).'" label="'.ha($label).'">';
		}
		return static::htmlElement('datalist', ['id' => $id], $opts);
	}

	/**
	 * submit input
	 * @param mixed $value
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlInputSubmit($value, $attributes=[]){
		$attributes['type'] ='submit';
		$attributes['value'] = $value;
		return static::htmlElement('input', $attributes);
	}

	/**
	 * no script support html
	 * @param $html
	 * @return string
	 */
	public static function htmlNoScript($html){
		return '<noscript>'.$html.'</noscript>';
	}

	/**
	 * submit button
	 * @param string $inner_html
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlButtonSubmit($inner_html, $attributes=[]){
		$attributes['type'] ='submit';
		return static::htmlElement('button', $attributes, $inner_html);
	}

	/**
	 * 构建table节点
	 * @param $data
	 * @param array|false $headers 表头列表 [字段名 => 别名, ...]，如为false，表示不显示表头
	 * @param string $caption
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlTable($data, $headers = [], $caption = '', $attributes = []){
		$html = $caption ? static::htmlElement('caption', [], $caption) : '';
		if(is_array($headers) && $data){
			$all_fields = array_keys(array_first($data));
			$headers = $headers ?: array_combine($all_fields, $all_fields);
			$html .= '<thead><tr>';
			foreach($headers as $field => $alias){
				$html .= "<th>$alias</th>";
			}
			$html .= '</tr></thead>';
		}

		$html .= '<tbody>';
		foreach($data ?: [] as $row){
			$html .= '<tr>';
			if($headers){
				foreach($headers as $field => $alias){
					$html .= "<td>{$row[$field]}</td>";
				}
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';
		return static::htmlElement('table', $attributes, $html);
	}

	/**
	 * 构建HTML节点属性
	 * 修正pattern，disabled在false情况下HTML表现
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlAttributes(array $attributes = []){
		$attributes = array_clear_null($attributes);
		$html = [];
		foreach($attributes as $k => $v){
			if($k == 'disabled' && $v === false){
				continue;
			}
			if($k == 'pattern'){
				$html[] = "$k=\"".$v."\"";
			} else{
				$html[] = "$k=\"".ha($v)."\"";
			}
		}
		return join(' ', $html);
	}

	/**
	 * 转化明文文本到HTML
	 * @param string $text
	 * @param null $len
	 * @param string $tail
	 * @param bool $over_length
	 * @return mixed
	 */
	public static function htmlFromText($text, $len = null, $tail = '...', &$over_length = false){
		if($len){
			$text = substr_utf8($text, $len, $tail, $over_length);
		}
		$html = htmlspecialchars($text);
		$html = str_replace("\r", '', $html);
		$html = str_replace(array(' ', "\n", "\t"), array('&nbsp;', '<br/>', '&nbsp;&nbsp;&nbsp;&nbsp;'), $html);
		return $html;
	}

	/**
	 * HTML数值比较（通过转换成字符串之后进行严格比较）
	 * @param string|number $str1
	 * @param string|number|array $data
	 * @return bool 是否相等
	 */
	public static function htmlValueCompare($str1, $data){
		$str1 = (string)$str1;

		if(is_array($data)){
			foreach($data as $val){
				if((string)$val === $str1){
					return true;
				}
			}
		}
		return $str1 === (string)$data;
	}
}