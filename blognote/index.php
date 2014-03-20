<?php
include 'config/app.inc.php';
$tmp = db_get_page('SELECT * FROM `catalog`');
$catalog_list = array();
foreach($tmp as $item){
	$catalog_list[$item['name']] = 0;
}

$note_list = db_get_page('SELECT * FROM `note`');
$note_list = array_group($note_list, 'id', true);

$tag_list = array();
foreach($note_list as $key=>$note){
	$tags = explode(',',$note['tags']);
	foreach($tags as $tag){
		if(trim($tag)){
			$tag_list[trim($tag)] += 1;
		}
	}
	$catalog_list[$note['catalog']] += 1;
}

include tpl('index.php');