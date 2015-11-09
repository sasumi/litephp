<?php
namespace Lite\Cache;

/**
 * Created by PhpStorm.
 * User: Sasumi
 * Date: 2015/10/19
 * Time: 20:20
 */
interface CacheInterface {
	/**
	 * 设置缓存接口
	 * @param $cache_key
	 * @param $data
	 * @param int $expired
	 * @return mixed
	 */
	public function set($cache_key, $data, $expired=60);

	/**
	 * 获取数据接口
	 * @param $cache_key
	 * @return mixed
	 */
	public function get($cache_key);

	/**
	 * 删除缓存接口
	 * @param $cache_key
	 * @return mixed
	 */
	public function delete($cache_key);

	/**
	 * 清空整个缓存区域接口
	 * @return mixed
	 */
	public function flush();
}