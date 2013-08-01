<?php
include 'config/app.inc.php';

if(ACTION == 'table'){
	$data = array(
		array(
			'id' => 1,
			'name' => 'sasc'+rand(),
			'state' => true,
			'text' => 'asdfasdfasfd asfd as fas fa sa fsas <br/>asdf',
			'key' => 'dafasdfa ad asd '+rand()
		),
		array(
			'id' => 2,
			'name' => 'sasc'+rand(),
			'state' => false,
			'text' => 'asdfasdfasfd asfd as fas fa sa fsas <br/>asdf',
			'key' => 'dafasdfa ad asd '+rand()
		),
		array(
			'id' => 3,
			'name' => 'sasc'+rand(),
			'state' => true,
			'text' => 'asdfasdfasfd asfd as fas fa sa fsas <br/>asdf',
			'key' => 'dafasdfa ad asd '+rand()
		),
		array(
			'id' => 34,
			'name' => 'sasc'+rand(),
			'state' => true,
			'text' => 'asdfasdfasfd asfd as fas fa sa fsas <br/>asdf',
			'key' => 'dafasdfa ad asd '+rand()
		)
	);

	$fields = array(
		'select' => function($row){
			if($row){
				return '<input type="checkbox" name="ids[]" value="'.$row['id'].'"/>';
			} else {
				return '<input type="checkbox" id="select_all">';
			}
		},
		'id' => '索引号',
		'name' => '名称',
		'text' => '描述',
		'state' => function($row){
			if($row){
				return $row['state'] ? '<a href="">启用</a>' : '<a href="">禁用</a>';
			} else {
				return '状态';
			}
		}
	);

	$options = array(
		'tfoot' => '分页信息可以放置在这里'
	);

	include tpl('table.php');
}

else if(ACTION == 'form'){
	include tpl('form.php');
}

else if(ACTION == 'index'){
	include tpl('component.php');
}

else if(ACTION == 'upload'){
	if(is_post()){
		$upload_config = array(
			'upload_dir'=> dirname(__FILE__).'/upload',
			'file_type'=>'doc,ppt',
			'file_name_converter' => function($name){return rand();},
			'max_file_count'=>1
		);
		$up = new Uploader($upload_config);
 		$res = $up->upload();
 		if(!empty($res)){
 			echo '成功';
 		}
	}
	include tpl();
}