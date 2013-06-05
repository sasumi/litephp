<?php
include 'config/app.inc.php';
include tpl('guide.php');

$man = new Man();

$pager = Pager::instance();
dump($man, 1);