<?php
namespace Lite;
use Lite\I18N\Lang;
//绑定LitePHP翻译
if(function_exists('gettext')){
	Lang::addDomain(Lang::DOMAIN_LITEPHP, __DIR__.'/I18N/litephp_lang', ['en_US', 'zh_CN'], 'en_US');
}
