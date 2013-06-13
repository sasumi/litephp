<?php
include 'config/app.inc.php';
$module_path = APP_PATH.'modules/';
$module_config_file = $module_path.'config.php';
$module_config = array();

if(file_exists($module_config_file)){
	$module_config = unserialize(file_get_contents($module_config_file));
}

/**
 * 获取模块列表
 * @param string $module_path 模块目录
 * @return array;
 **/
function get_module_list($module_path, $module_config){
	$dir_list = get_file_list($module_path);
	$modules = array();
	foreach($dir_list as $dir){
		$path = $module_path.$module.$dir;
		if(file_exists($path.'/config.php')){
			$config = include($path.'/config.php');
			$name = $config['name'];
			$key = $dir;
			$disabled = file_exists($path.'/disabled');

			$modules[$key] = $config;
			$modules[$key]['dir'] = $path;

			if(!$module_config[$key]){
				$module_config[$key] = array();
			}
			$module_config[$key] = array_merge(array(
				'enable' => 0,
				'installed' => false
			), $module_config[$key]);

			$modules[$key] = array_merge($modules[$key], $module_config[$key]);
			$modules[$key]['state'] = !$modules[$key]['installed'] ? '未安装' : '';
			if(!$modules[$key]['state']){
				$modules[$key]['state'] = $modules[$key]['enable'] ? '已启用' : '未启用';
			}
		}
	}
	return $modules;
}

/**
 * 更新模块配置
 * @param array $configs
 * @param string $module_config_file
 * @return bool
 **/
function update_module_config($configs, $module_config_file){
	return file_put_contents($module_config_file, serialize($configs));
};

if(ACTION == 'install' || ACTION == 'uninstall' || ACTION == 'enable' || ACTION == 'disable'){
	$id = one_get_request('id', 'KEY');
	$module_config = get_module_list($module_path, $module_config);
	$script = '';

	//update config
	if(ACTION == 'install' || ACTION == 'uninstall'){
		$script = $module_path.$id.'/'.ACTION.'.php';
		$module_config[$id]['installed'] = ACTION == 'install';
	}
	if(ACTION == 'enable' || ACTION == 'disable'){
		$module_config[$id]['enable'] = ACTION == 'enable';
	}
	$data = array();
	foreach($module_config as $key=>$mc){
		$data[$key] = array(
			'installed' => $mc['installed'],
			'enable' => $mc['enable']
		);
	}
	$result = update_module_config($module_config, $module_config_file);

	//exec script
	if($result){
		if($script){
			include $script;
		}
	}
	jump_to('module');
}

else {
	$module_list = get_module_list($module_path, $module_config);
	include tpl('module.php');
}
