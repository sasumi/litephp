<?php
include 'config/app.inc.php';

$all_mods = ResumeMod::init()->getAllMods();

if(ACTION == 'create'){
	$resume = array(
		'theme' => 'base'
	);
	$mods = array();
	$ords = array('conver', 'title', 'info', 'skill', 'career', 'education', 'intro');
	foreach($ords as $k){
		$mods[$k] = $all_mods[$k];
	}
	$mods['conver']['invisible'] = true;
	$mods['intro']['invisible'] = true;
	$template_css = ResumeMod::init()->getAllTemplateCss();

	$themes = ResumeTheme::init()->getAllThemes();
	$current_theme = $resume['theme'];
	$theme_css = ResumeTheme::init()->getAllThemesCss();
}

if(ACTION == 'addCol'){
	$cur_mods = gets('cur_mods', '');
	$cur_mods = explode(',', $cur_mods);
}

if(ACTION == 'changeAvatar'){
	$org_src = gets('org_src', '');
}

include tpl();