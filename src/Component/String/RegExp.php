<?php

namespace Lite\Component\String\RegExp;

const EMAIL_SIMPLE = '/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/';
const CHINA_AREA_CODE = '/^0[1-2][0-9]$|^0[1-9][0-9]{2}$/';
const CHINA_POSTCODE = '/^[0-9]{6}$/';
const CHINA_MOBILE_NUMBER = '/^[1][0-9]{10}$/';
const CHINESE_ID = '/^\d{14}(\d{1}|\d{4}|(\d{3}[xX]))$/';
const REQUIRE_ANYTHING = '/^[\s|\S]+$/';
const QQ = '/^\d{5,13}$/';
const VAR_NAME = '/^[a-zA-Z][a-zA-Z0-9_]*$/';