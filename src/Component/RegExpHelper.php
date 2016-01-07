<?php
/**
 * Created by PhpStorm.
 * User: Sasumi
 * Date: 2015/12/18
 * Time: 17:57
 */
namespace Lite\Component;

abstract class RegExpHelper {
	public static $REG_MOBILE = '';
	public static $REG_PHONE = '/^[0-9]{7,13}$/';
	public static $REG_AREA_CODE = '/^0[1-2][0-9]$|^0[1-9][0-9]{2}$/';
	public static $REG_POSTCODE = '/^[0-9]{6}$/';
	public static $REG_EMAIL = '/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/';
	public static $REG_CHINESE_ID = '/^\d{14}(\d{1}|\d{4}|(\d{3}[xX]))$/';
	public static $REG_REQUIRE = '/^[\s|\S]+$/';
	public static $REG_QQ = '/^\d{5,13}$/';
	public static $REG_VAR_NAME = '/^\w+$/';
}