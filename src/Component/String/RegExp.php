<?php
/**
 * User: Sasumi
 * Date: 2015/12/18
 * Time: 17:57
 */
namespace Lite\Component\String;

abstract class RegExp {
	const REG_MOBILE = '';
	const REG_PHONE = '/^[0-9]{7,13}$/';
	const REG_AREA_CODE = '/^0[1-2][0-9]$|^0[1-9][0-9]{2}$/';
	const REG_POSTCODE = '/^[0-9]{6}$/';
	const REG_EMAIL = '/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/';
	const REG_CHINESE_ID = '/^\d{14}(\d{1}|\d{4}|(\d{3}[xX]))$/';
	const REG_REQUIRE = '/^[\s|\S]+$/';
	const REG_QQ = '/^\d{5,13}$/';
	const REG_VAR_NAME = '/^\w+$/';
}