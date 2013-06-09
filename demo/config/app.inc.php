<?php
define(CONFIG_PATH, __DIR__.DIRECTORY_SEPARATOR);
define(APP_URL, 'http://localhost/litephp/demo/');
//define(ROUTE_MODE, 'REWRITE');
//define(ROUTE_MODE, 'NORMAL');
define(ROUTE_MODE, 'PATH');
include CONFIG_PATH.'../../lib/lite.php';