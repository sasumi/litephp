<?php
include 'config/app.inc.php';
$catalog_list = db_get_page('SELECT * FROM `catalog`');
$catalog_list = array_group($catalog_list, 'id', true);

$note_list = db_get_page('SELECT * FROM `note`');
$note_list = array_group($note_list, 'id', true);

$tag_list = array();
foreach($note_list as $key=>$note){
	$tags = explode(',',$note['tag']);
	foreach($tags as $tag){
		$tag_list[trim($tag)] += 1;
	}
	$catalog_list[$note['catalog_id']]['count'] += 1;
}
include tpl('index.php');
