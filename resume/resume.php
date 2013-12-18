<?php
include 'config/app.inc.php';

if(ACTION == 'create'){
	$resume = Resume::getResume(1);
}
include tpl();

