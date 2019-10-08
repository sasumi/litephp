<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/06/20
 * Time: 09:24
 */

use function Lite\func\t;
use Lite\I18N\Lang as Lang;

include '../bootstrap.php';
Lang::setSupportLanguageList(['zh_CN', 'en_US']);
Lang::bindDomain(Lang::DOMAIN_DEFAULT, __DIR__);
Lang::bindDomain('menu', __DIR__);
$lang = $_SESSION['lang'] ?: Lang::detectLanguageListFromBrowser()[0] ?: 'zh_CN';
$lang = 'en_US';
Lang::setCurrentLanguage($lang);

t('hello world');

dump(Lang::getCurrentLanguage(), 1);