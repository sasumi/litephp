<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/06/20
 * Time: 09:24
 */
include '../bootstrap.php';
$lang = \Lite\I18N\Lang::instance()->detectLanguageFromBrowser();
dump($lang, 1);