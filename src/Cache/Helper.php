<?php
namespace Lite\Cache;
use Lite\Core\Config;

class Helper {
	const CACHE_REDIS = 'Redis';
	const CACHE_FILE = 'File';
	const CACHE_MEMCACHE = 'Memcache';
	const CACHE_SESSION = 'Session';

	/**
	 * 初始化缓存实例
	 * @param array $config
	 * @return CacheAdapter
	 */
	public static function init(array $config=array()){
		if(!$config){
			$config = Config::get('cache');
		}
		if(!$config['type']){
			$config['type'] = self::CACHE_FILE;
		}
		$result = call_user_func(array('\\'.__NAMESPACE__.'\\'.$config['type'], 'instance'), $config);
		return $result;
	}
}