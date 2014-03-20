<?php
abstract class Config {
	private static $LITE_APP_CONFIG_KEY = '__LITE_APP_CONFIG__';
	private static $CONFIGS = array();
	private function __construct(){}

	/**
	 * get config
	 * @param string $keys
	 * @return unknown|NULL
	 */
	public static function get($key=''){
		$keys = explode('/', $key);
		$config = self::$CONFIGS;
		for($i=0; $i<count($keys); $i++){
			if($i == (count($keys)-1)){
				return $config[$keys[$i]];
			} else {
				$config = $config[$keys[$i]];
			}
		}
		return null;
	}

	/**
	 * set config
	 * @param string $key
	 * @param mix $val
	 */
	public static function set($key, $val){
		self::$CONFIGS[$key] = $val;
	}

	/**
	 * init config
	 * @param string $app_path application path
	 */
	public static function init($app_path = null){
		$DS = DIRECTORY_SEPARATOR;
		if(!$app_path){
			$app_path = dirname($_SERVER['SCRIPT_FILENAME']).$DS;
		}
		$config_path = $app_path.$DS.'config'.$DS;
		
		$set_config = function(&$target, $default_value){
			if(!isset($target)){
				$target = $default_value;
			}
		};

		$get_config_from_file = function($file){
			$vars = array();
			if(file_exists($file)){
				$vars = include $file;
			}
			return $vars;
		};

		$all_configs = array();
		$config_files = get_files($config_path);
		foreach($config_files as $file){
			$file_name = basename($file);
			list($key) = explode('.', $file_name);
			if($key != 'boot'){
				$all_configs[$key] = $get_config_from_file($file);
			}
		}

		//set default configuration
		$set_config($all_configs['lib'], array());
		$set_config($all_configs['lib']['path'], __DIR__.$DS);

		//app config
		$app_config = $all_configs['app'];
		$app_config['config'] = $config_path;
		$set_config($app_config['path'], $app_path);
		$set_config($app_config['autorender'], true);
		$set_config($app_config['tpl'], $app_config['path'].'template'.$DS);
		$set_config($app_config['include'], $app_config['path'].'include'.$DS);
		$set_config($app_config['library'], $app_config['path'].'library'.$DS);
		$set_config($app_config['url'], '/');
		$set_config($app_config['static'], $app_config['url'].'static/');
		$set_config($app_config['js'], $app_config['static'].'js/');
		$set_config($app_config['img'], $app_config['static'].'img/');
		$set_config($app_config['css'], $app_config['static'].'css/');
		$set_config($app_config['flash'], $app_config['static'].'flash/');
		$all_configs['app'] = $app_config;

		//router config
		$router_config = $all_configs['router'];
		$set_config($router_config['mode'], 'path');
		$set_config($router_config['controller_key'], 'mod');
		$set_config($router_config['action_key'], 'act');
		$set_config($router_config['default_controller'], 'index');
		$set_config($router_config['default_action'], 'index');

		$all_configs['router'] = $router_config;
		self::$CONFIGS = $all_configs;

		//system config
		$sys_config = $all_configs['sys'];
		$set_config($sys_config['close_magic_gpc'], true);

		//recursive replace inside config
		array_walk_recursive(self::$CONFIGS, function(&$val, $key){
			if(is_string($val)){
				$val = preg_replace_callback('/(\{[^\}]+\})/', function($match){
					$config_key = substr($match[0], 1, strlen($match[0])-2);
					return Config::get($config_key);
				}, $val);
			}
		});
	}
}

