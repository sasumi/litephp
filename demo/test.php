<?php
include 'config/app.inc.php';

if(ACTION == 'add'){
	include tpl('add.php');
} else {
	$data = db_get_page('select * from user');
	include tpl('test.php');
}
