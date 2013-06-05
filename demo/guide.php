<?php
include 'config/app.inc.php';
include tpl('guide.php');

$man = new Man();
dump($man, 1);