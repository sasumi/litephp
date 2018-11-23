<?php
namespace Lite\Cache;

use function Lite\func\session_start_once;
use function Lite\func\session_write_once;

class CacheSession extends CacheAdapter {
	private $cache_prefix = 'lp_cs_';

	protected function __construct(array $config=array()){
		session_start_once();
		parent::__construct($config);
	}

	public function set($cache_key, $data, $expired=60){
		$name = $this->getName($cache_key);
		$_SESSION[$name] = array(
			'data' => $data,
			'expired' => time()+$expired
		);
		session_write_once();
	}

	private function getName($cache_key){
		return $this->cache_prefix.urlencode($cache_key);
	}

	public function get($cache_key){
		$name = $this->getName($cache_key);
		$data = $_SESSION[$name];
		if($data && $data['expired'] > time()){
			return $data['data'];
		}
		return null;
	}

	public function delete($cache_key){
		$name = $this->getName($cache_key);
		if($_SESSION[$name]){
			unset($_SESSION[$name]);
			session_write_once();
		}
	}

	public function flush(){
		foreach($_SESSION as $key=>$val){
			if(strstr($key, $this->cache_prefix)){
				unset($_SESSION[$key]);
				session_write_once();
			}
		}
	}
}