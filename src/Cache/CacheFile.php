<?php
namespace Lite\Cache;

/**
 * 文件缓存
 * 默认缓存在system temporary临时目录中
 * 默认开启进程内变量缓存，避免多次获取变量读取文件 config:cache_in_process
 * @package Lite\Cache
 */
class CacheFile extends CacheAdapter{
	private $cache_in_process = true;
	private static $process_cache = [];

	protected function __construct(array $config = []){
		if(!isset($config['cache_in_process'])){
			$this->cache_in_process = true;
		}
		if(!isset($config['dir']) || !$config['dir']){
			$dir = sys_get_temp_dir();
			$config['dir'] = $dir.'/litephp_cache/';
		}
		if(!is_dir($config['dir'])){
			mkdir($config['dir'], 0777, true);;
		}
		parent::__construct($config);
	}

	/**
	 * 设置缓存
	 * @param $cache_key
	 * @param $data
	 * @param int $expired
	 * @return bool|int|mixed
	 */
	public function set($cache_key, $data, $expired = 60){
		$file = $this->getFileName($cache_key);
		$string = serialize(array(
			'cache_key' => $cache_key,
			'expired'   => date('Y-m-d H:i:s', time()+$expired),
			'data'      => $data,
		));
		if($handle = fopen($file, 'w')){
			$result = fwrite($handle, $string);
			fclose($handle);
			chmod($file, 0777);
			if($result && $this->cache_in_process){
				self::$process_cache[$cache_key] = $data;
			}
			return $result;
		}
		return false;
	}

	/**
	 * 获取缓存文件名
	 * @param $cache_key
	 * @return string
	 */
	public function getFileName($cache_key){
		return $this->getConfig('dir').md5($cache_key);
	}

	/**
	 * 获取缓存
	 * @param $cache_key
	 * @return null
	 */
	public function get($cache_key){
		if($this->cache_in_process && isset(self::$process_cache[$cache_key])){
			return self::$process_cache[$cache_key];
		}
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			$string = file_get_contents($file);
			if($string){
				$data = unserialize($string);
				if($data && strtotime($data['expired'])>time()){
					if($this->cache_in_process){
						self::$process_cache[$cache_key] = $data['data'];
					}
					return $data['data'];
				}
			}
			//清空无效缓存，防止缓存文件膨胀
			$this->delete($cache_key);
		}
		return null;
	}

	/**
	 * 删除缓存
	 * @param $cache_key
	 * @return bool|mixed
	 */
	public function delete($cache_key){
		if(isset(self::$process_cache[$cache_key])){
			unset(self::$process_cache[$cache_key]);
		}
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			return unlink($file);
		}
		return false;
	}

	/**
	 * 清空缓存
	 * flush cache dir
	 */
	public function flush(){
		self::$process_cache = [];
		$dir = $this->getConfig('dir');
		if(is_dir($dir)){
			array_map('unlink', glob($dir.'/*'));
		}
	}
}
