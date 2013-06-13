<?php
include 'config/app.inc.php';
$pager = Pager::instance();
$pager->setPageSize(2);
$data = db_get_page('SELECT * FROM user', $pager);
$fields = array(
	'id' => 'index',
	'type' => 'type',
	'qq' => 'qq',
	'bno_189' => 'Num of 189',
	'phone' => 'Phone'
);
$options = array(
	'tfoot' => $pager
);
include tpl('db.php');