<?php
define('CONFIG_PATH', __DIR__.DIRECTORY_SEPARATOR);
define('APP_URL', 'http://localhost/litephp/resume/');
define('ROUTE_MODE', 1);
define('INCLUDE_PATH', CONFIG_PATH.'../include/');
define('ADMIN_URL', 'http://localhost/litephp/resume/admin/');
define('YSL_URL', 'http://localhost/ysl/source/ysl.base.js');
define('UPLOAD_DIR', CONFIG_PATH.'../upload/');
define('UPLOAD_URL', APP_URL.'upload/');
define('APP_SALT_KEY', 'HELLO_RESUME');

include CONFIG_PATH.'../../lib/lite.php';
