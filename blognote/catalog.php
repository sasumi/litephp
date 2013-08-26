<?php
include 'config/app.inc.php';
$tmp = db_get_page('SELECT * FROM `catalog`');
$catalog_list = array();
foreach($tmp as $item){
	$catalog_list[$item['name']] = 0;
}
include tpl('catalog.php');