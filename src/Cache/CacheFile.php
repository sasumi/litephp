<?php
namespace Lite\Cache;
use Lite\Core\Config;

class CacheFile extends CacheAdapter {
	protected  function __construct(array $config = array()){
		$config = array_merge(array(
			'dir' => Config::get('app/path').'tmp'.DS.'filecache'.DS
		), $config);

		if(!is_dir($config['dir'])){
			mkdir($config['dir'], null, true);;
		}
		parent::__construct($config);
	}

	public function set($cache_key, $data, $expired=60){
		$file = $this->getFileName($cache_key);
		$string = serialize(array(
			'data' => $data,
			'expired' => time()+$expired
		));
		if($handle = fopen($file, 'w')){
			$result = fwrite($handle, $string);
			fclose($handle);
			return $result;
		}
		return false;
	}

	private function getFileName($cache_key){
		return $this->getConfig('dir').md5($cache_key);
	}

	public function get($cache_key){
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			$string = file_get_contents($file);
			if($string){
				$data = unserialize($string);
				if($data && $data['expired'] > time()){
					return $data['data'];
				}
			}
			//清空cache，放置cache膨胀
			$this->delete($cache_key);
		}
		return null;
	}


	public function delete($cache_key){
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			return unlink($file);
		}
		return false;
	}

	/**
	 * @todo test...
	 */
	public function flush(){
		$dir = $this->getConfig('dir');
		if(is_dir($dir)){
			array_map('unlink', glob($dir.'*'));
		}
	}

    public function getAll(){
        $dir=$this->getConfig('dir');
        return $dir;
    }
}