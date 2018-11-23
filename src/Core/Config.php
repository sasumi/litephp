<?php
namespace Lite\Core;
use Lite\Exception\Exception;

/**
 * 配置项基础类
 * User: sasumi
 * Date: 2015/01/08
 * Time: 9:00
 */
abstract class Config {
	private static $app_root = '';
	private static $config_path = '';
	private static $default_charset = 'utf-8';
	private static $CONFIGS = array();
	private function __construct(){}

	/**
	 * 获取配置项
	 * @example
	 * <p> Config::get('app/url');</p>
	 * @param string $uri 配置项key，格式如：myconfig/configitem
	 * @param bool $force_exists 是否要求配置项必须存在，如果为true，则配置项不存在时，系统会抛异常
	 * @param bool $refresh
	 * @return mixed
	 * @throws \Lite\Exception\Exception
	 */
	public static function get($uri, $force_exists=false, $refresh=false){
		$keys = explode('/', $uri);
		$key = $keys[0];
		if(!self::$CONFIGS[$key] || $refresh){
			$load_result = self::load($key, $force_exists);
			if(!$load_result){
				return null;
			}
		}
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
	 * 设置配置到文件
	 * @todo 当前方法未设定 self::$CONFIGS，应当需要设定
	 * @param string $uri
	 * @param mixed $data 数据
	 * @param bool $over_write_all 是否全部设置覆盖，缺省为部分覆盖
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public static function set($uri, $data, $over_write_all=false){
		$keys = explode('/', $uri);
		$key = $keys[0];
		$config = array();
		if(self::get($key) && !$over_write_all){
			if(self::get($uri) == $data){
				return true;
			}
			$config = self::get($key);
		}

		if(!$over_write_all && $config){
			$ori_part = self::get($uri);
			if($ori_part){

			}
			for($i=0; $i<count($keys); $i++){
				if($i == (count($keys)-1)){
					return $config[$keys[$i]];
				} else {
					$config = $config[$keys[$i]];
				}
			}
		} else {
			$config = array_merge($config, $data);
		}

		$file = Config::get('app/root')."config/$key.inc.php";
		$content = "<?php\n".
			"//Last update:".date('Y-m-d H:i:s')."\n\n".
			"return ".var_export($config, true).";";
		return !!file_put_contents($file, $content);
	}
	
	/**
	 * @param $uri
	 * @param $data
	 * @param null $over_write_all
	 * @return bool
	 * @throws \Lite\Exception\Exception
	 */
	public static function save($uri, $data, $over_write_all=null){
		if(self::set($uri, $data, $over_write_all)){
			$keys = explode('/', $uri);
			$key = $keys[0];
			$config = array();
			if(self::get($key) && !$over_write_all){
				if(self::get($uri) == $data){
					return true;
				}
				$config = self::get($key);
			}

			if(!$over_write_all && $config){
				$ori_part = self::get($uri);
				if($ori_part){

				}
				for($i=0; $i<count($keys); $i++){
					if($i == (count($keys)-1)){
						return $config[$keys[$i]];
					} else {
						$config = $config[$keys[$i]];
					}
				}
			} else {
				$config = array_merge($config, $data);
			}

			$file = Config::get('app/root')."config/$key.inc.php";
			$content = "<?php\n".
				"//Last update:".date('Y-m-d H:i:s')."\n\n".
				"return ".var_export($config, true).";";
			return !!file_put_contents($file, $content);
		}
		return false;
	}

	/**
	 * 判断配置项是否已经设置，没有设置则强制设置缺省值
	 * @param mixed $target 配置项值
	 * @param mixed $default_value 缺省值
	 */
	private static function assignConfig(&$target, $default_value){
		if(!isset($target)){
			$target = $default_value;
		}
	}

	/**
	 * 加载配置项、初始化系统缺省配置项
	 * @param $key
	 * @param $force_exists
	 * @return bool
	 * @throws Exception
	 */
	private static function load($key, $force_exists){
		$file = self::$config_path.$key.'.inc.php';
		if(!file_exists($file)){
			if($force_exists){
				throw new Exception('config file not found:'.$file);
			}else{
				$config = array();
			}
		}else{
			$config = include $file;
		}

		$config = !is_array($config) ? array() : $config;

		switch($key){
			case 'app':
				self::assignConfig($config['root'], self::$app_root);
				self::assignConfig($config['path'], $config['root'].'app/');
				self::assignConfig($config['charset'], self::$default_charset);
				self::assignConfig($config['auto_render'], true);
				self::assignConfig($config['database_source'], $config['root'].'database/');
				self::assignConfig($config['render'], __NAMESPACE__.'\\View');
				self::assignConfig($config['tpl'], $config['path'].'template/');
				self::assignConfig($config['include'], $config['path'].'include/');
				self::assignConfig($config['url'], '/');
				self::assignConfig($config['static'], $config['url'].'static/');
				self::assignConfig($config['js'], $config['static'].'js/');
				self::assignConfig($config['img'], $config['static'].'img/');
				self::assignConfig($config['css'], $config['static'].'css/');
				self::assignConfig($config['flash'], $config['static'].'flash/');
				break;

			case 'router':
				self::assignConfig($config['mode'], Router::MODE_NORMAL);
				self::assignConfig($config['router_key'], Router::DEFAULT_ROUTER_KEY);
				self::assignConfig($config['lower_case_uri'], true);
				self::assignConfig($config['controller_key'], 'mod');
				self::assignConfig($config['action_key'], 'act');
				self::assignConfig($config['default_controller'], 'Index');
				self::assignConfig($config['default_action'], 'index');
				break;

			case 'api':
				self::assignConfig($config['path'], Config::get('app/path').'api/');
				break;
		}

		self::$CONFIGS[$key] = $config;
		return true;
	}

	/**
	 * 框架配置初始化，检测配置目录是否存在
	 * @param string $app_root application root
	 * @throws \Lite\Exception\Exception
	 */
	public static function init($app_root = null){
		if(!$app_root){
			$app_root = dirname(dirname($_SERVER['SCRIPT_FILENAME'])).'/';
		}
		self::$app_root = $app_root;
		self::$config_path = $app_root.'config/';
		if(!is_dir(self::$config_path)){
			throw new Exception('application config directory no found', null, self::$config_path);
		}
	}
}