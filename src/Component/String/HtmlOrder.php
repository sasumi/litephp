<?php
namespace Lite\Component\String;

use Lite\Core\Router;
use function Lite\func\array_clear_null;
use function Lite\func\array_first;
use function Lite\func\ha;

trait HtmlOrder{
	//排序控制字段
	//排序值
	protected static $DESC_VAL = 'desc';
	protected static $ASC_VAL = 'asc';
	
	//排序URL键值名称
	public static $order_field_key = 'ord';
	public static $order_dir_key = 'dir';
	public static $order_css_class_config = array('order-link', 'order-link-asc', 'order-link-desc'); //排序类名
	private static $allow_order_fields = array();   //允许排序字段
	private static $default_field = null;           //默认排序字段
	private static $default_dir = 'desc';           //默认排序方向
	
	/**
	 * 设置默认排序字段、方向
	 * @param array $allow_fields
	 * @param $default_field
	 * @param string $default_dir
	 */
	public static function setOrderConfig(array $allow_fields, $default_field = null, $default_dir = null){
		$default_dir = strtolower($default_dir) ?: static::$DESC_VAL;
		self::$allow_order_fields = $allow_fields;
		self::$default_field = $default_field;
		self::$default_dir = $default_dir;
	}
	
	/**
	 * 获取当前排序设置集合
	 * @return array [排序字段, 排序方向]
	 */
	public static function getCurrentOrderSet(){
		$get = Router::get();
		$ord = (isset($get[static::$order_field_key]) && $get[static::$order_field_key]) && in_array($get[static::$order_field_key], self::$allow_order_fields) ? $get[static::$order_field_key] : self::$default_field;
		$dir = in_array(isset($get[static::$order_dir_key]) ? $get[static::$order_dir_key] : null, [
			static::$ASC_VAL,
			static::$DESC_VAL
		]) ? $get[static::$order_dir_key] : self::$default_dir;
		return $ord ? array($ord, $dir) : array();
	}
	
	/**
	 * 显示排序链接
	 * @param $title
	 * @param $field
	 * @param array $exclude_keys
	 * @return string
	 */
	public static function getOrderLink($title, $field, $exclude_keys = array('page', 'page_size')){
		$get = Router::get() ?: [];
		
		if(!in_array($get[static::$order_field_key], self::$allow_order_fields)){
			unset($get[static::$order_field_key]);
			unset($get[static::$order_dir_key]);
		}
		$is_current = $get[static::$order_field_key] == $field || (!$get[static::$order_field_key] && $field == self::$default_field);
		
		if($is_current){
			$current_dir = ($get[static::$order_dir_key] && in_array($get[static::$order_dir_key], [
					static::$DESC_VAL,
					static::$ASC_VAL
				])) ? $get[static::$order_dir_key] : self::$default_dir;
			$to_dir = $current_dir == static::$DESC_VAL ? static::$ASC_VAL : static::$DESC_VAL;
		} else{
			$to_dir = self::$default_dir;
		}
		
		foreach($exclude_keys as $ek){
			unset($get[$ek]);
		}
		$get[static::$order_field_key] = $field;
		$get[static::$order_dir_key] = $to_dir;
		
		$url = Router::getUrl(Router::getCurrentUri(), $get);
		$class = '';
		if($is_current){
			$class = $to_dir == static::$ASC_VAL ? static::$order_css_class_config[2] : static::$order_css_class_config[1];
		}
		return Html::htmlLink($title, $url, ['class'=>$class]);
	}
}