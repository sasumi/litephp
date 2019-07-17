<?php
namespace Lite\Cache;

/**
 * 文件缓存
 * 默认缓存在system temporary临时目录中
 * 默认开启进程内变量缓存，避免多次获取变量读取文件
 * @package Lite\Cache
 */
class CacheFile extends CacheAdapter{
	private $cache_in_process = true;
	private static $cache_store = [];

	protected function __construct(array $config = []){
		if(isset($config['cache_in_process'])){
			$this->cache_in_process = !!$config['cache_in_process'];
		}
		if(!$config['dir']){
			$dir = sys_get_temp_dir();
			$config['dir'] = $dir.'/litephp_cache/';
		}
		if(!is_dir($config['dir'])){
			mkdir($config['dir'], 0777, true);;
		}
		parent::__construct($config);
	}

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
			if($result && $this->cache_in_process){
				self::$cache_store[$cache_key] = $data;
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
	 * @param $cache_key
	 * @return null
	 */
	public function get($cache_key){
		if(isset(self::$cache_store[$cache_key])){
			return self::$cache_store[$cache_key];
		}
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			$string = file_get_contents($file);
			if($string){
				$data = unserialize($string);
				if($data && strtotime($data['expired'])>time()){
					return $data['data'];
				}
			}
			//清空cache，防止cache膨胀
			$this->delete($cache_key);
		}
		return null;
	}

	public function delete($cache_key){
		if(isset(self::$cache_store[$cache_key])){
			unset(self::$cache_store[$cache_key]);
		}
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			return unlink($file);
		}
		return false;
	}

	/**
	 * flush cache dir
	 */
	public function flush(){
		self::$cache_store = [];
		$dir = $this->getConfig('dir');
		if(is_dir($dir)){
			array_map('unlink', glob($dir.'/*'));
		}
	}
}