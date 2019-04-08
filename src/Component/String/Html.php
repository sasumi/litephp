<?php
namespace Lite\Component\String;

use Lite\Core\Router;
use function Lite\func\array_clear_null;
use function Lite\func\array_first;
use function Lite\func\h;
use function Lite\func\ha;
use function Lite\func\is_assoc_array;

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
	 * @param $name
	 * @param array $options 选项数据，如果是分组模式，为[group_name=>options, ...]格式
	 * @param string $value
	 * @param string $placeholder
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlSelect($name, array $options, $value = '', $placeholder = '', $attributes = []){
		$attributes = array_merge($attributes, [
			'name'        => $name ?: null,
			'placeholder' => $placeholder ?: null
		]);
		if(count($options, COUNT_RECURSIVE) == count($options, COUNT_NORMAL)){
			$option_html = static::htmlOptions($options, $value);
		} else{
			$option_html = '';
			foreach($options as $group_name => $opts){
				$option_html .= static::htmlOptionGroup($group_name, $opts);
			}
		}
		return static::htmlElement('select', $attributes, $option_html);
	}

	/**
	 * 构建select选项
	 * @param array $options [value=>text,...] option data
	 * @param mixed $value
	 * @return string
	 */
	public static function htmlOptions(array $options, $value = ''){
		$html = '';
		foreach($options as $k => $n){
			$html .= static::htmlOption($n, $k, $value == $k);
		}
		return $html;
	}

	/**
	 * 构建option节点
	 * @param $text
	 * @param string $value
	 * @param bool $selected
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlOption($text, $value = '', $selected = false, $attributes = []){
		return static::htmlElement('option', array_merge([
			'selected' => $selected ?: null,
			'value'    => $value
		], $attributes), h($text));
	}

	/**
	 * 构建optgroup节点
	 * @param $label
	 * @param $options
	 * @param string $value
	 * @return string
	 */
	public static function htmlOptionGroup($label, $options, $value = ''){
		$option_html = static::htmlOptions($options, $value);
		return static::htmlElement('optgroup', ['label' => $label], $option_html);
	}

	/**
	 * 构建hidden表单节点
	 * @param $name
	 * @param string $value
	 * @return string
	 */
	public static function htmlHidden($name, $value = ''){
		return static::htmlElement('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
	}

	/**
	 * @param $name
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
	 * 获取HTML摘要信息
	 * @param $html_content
	 * @param int $len
	 * @return string
	 */
	public static function htmlAbstract($html_content, $len = 200){
		$str = str_replace(array("\n", "\r"), "", $html_content);
		$str = preg_replace('/<br([^>]*)>/i', '$L', $str);
		$str = strip_tags($str);
		$str = html_entity_decode($str, ENT_QUOTES);
		$str = h($str, $len);
		$str = preg_replace('/[\\$L]+/', '<br/>', $str);

		//移除头尾空白行
		$str = preg_replace('/^(<br[^>]*>)*/i', '', $str);
		$str = preg_replace('/(<br[^>]*>)*$/i', '', $str);
		return $str;
	}

	/**
	 * @param $name
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
	 * 构建HTML节点
	 * @param $tag
	 * @param array $attributes
	 * @param string $inner_html
	 * @return string
	 */
	public static function htmlElement($tag, $attributes = [], $inner_html = ''){
		$tag = strtolower($tag);
		$single_tag = in_array($tag, static::$SELF_CLOSING_TAGS);

		$html = "<$tag ";
		$html .= static::htmlAttributes($attributes);
		$html .= $single_tag ? "/>" : ">".$inner_html."</$tag>";
		return $html;
	}

	/**
	 * @param $inner_html
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
	 * @param $href
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
	 * @param $src
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
	 * datalist
	 * @param $id
	 * @param array $lv_data
	 * @return string
	 */
	public static function htmlDataList($id, $lv_data = []){
		$opts = '';
		foreach($lv_data as $label=>$value){
			$opts .= '<option label="'.ha($label).'">'.h($value).'</option>';
		}
		return static::htmlElement('datalist', ['id'=>$id], $opts);
	}

	/**
	 * submit input
	 * @param $value
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlInputSubmit($value, $attributes=[]){
		$attributes['type'] ='submit';
		$attributes['value'] = $value;
		return static::htmlElement('input', $attributes);
	}

	/**
	 * submit button
	 * @param $inner_html
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
	 * @param array $attributes
	 * @return string
	 */
	public static function htmlAttributes(array $attributes = []){
		$attributes = array_clear_null($attributes);
		$html = [];
		foreach($attributes as $k => $v){
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
	 * @param $text
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
}