<?php
/**
 * 字符串相关操作函数
 * User: sasumi
 * Date: 2015/3/30
 * Time: 11:19
 */

namespace Lite\func;

use Lite\I18N\Lang;

/**
 * 多语言翻译，支持多重变量替换
 * 多重数组调用格式：{user.age.value}
 * @param $text
 * @param array $param
 * @param string $domain
 * @return string
 */
function t($text, $param = [], $domain = ''){
	return Lang::getTextSoft($text, $param, $domain);
}

/**
 * Translate litephp library
 * @param $text
 * @param $param
 * @return string
 */
function _tl($text, $param = []){
	return Lang::getTextSoft($text, $param, Lang::DOMAIN_LITEPHP);
}
