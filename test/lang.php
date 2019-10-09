<?php
use function Lite\func\t;
use Lite\I18N\Lang as Lang;

include '../bootstrap.php';
Lang::addDomain(Lang::DOMAIN_LITEPHP, dirname(__DIR__).'/src/I18N/litephp_lang', ['zh_CN', 'en_US'], 'en_US');
Lang::setCurrentDomain(Lang::DOMAIN_LITEPHP);
//$lang = $_SESSION['lang'] ?: Lang::detectLanguageListFromBrowser()[0] ?: 'zh_CN';
Lang::setCurrentLanguage('zh_CN');

echo t('hello world', [], Lang::DOMAIN_LITEPHP);

dump(Lang::getCurrentLanguage(), Lang::getCurrentDomain(), 1);