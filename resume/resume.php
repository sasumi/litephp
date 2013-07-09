<?php
include 'config/app.inc.php';

include 'mods/resumemods.class.php';
die;

include CONFIG_PATH.'mods/resumemods.class.php';

$org_mods = ResumeMods::init()->getAllMods();
$mods = array();
$ords = array('title', 'info', 'skill', 'career', 'education', 'intro');
foreach($ords as $k){
	$mods[$k] = $org_mods[$k];
}

$theme_css = ResumeMods::init()->getAllThemeCss();
include tpl();
include 'test.php';