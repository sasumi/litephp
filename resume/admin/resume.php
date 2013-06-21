<?php
include 'config/app.inc.php';
$pager = Pager::instance();
$pager->setPageSize(2);
$data = db_get_page('SELECT * FROM `resume`', $pager);
$options = array(
	'tfoot' => $pager
);
$fields = array();
include tpl();
