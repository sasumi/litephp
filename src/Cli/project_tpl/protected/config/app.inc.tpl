<?php
return array(
	'site_name' => '{$SITE_NAME}',
	'url' => '{$SITE_URL}',
	'auto_statistics' => true,  //是否开启性能统计
	'auto_process_logic_error' => true,     //自动显示逻辑错误
	'debug' => false,
	'render' => '\{$NAMESPACE}\ViewBase',
	'static' => '{$SITE_URL}static/',
);