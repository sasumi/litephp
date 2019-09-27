<?php
namespace Lite\Component;
use function Lite\func\format_size;
use function Lite\func\resolve_size;

/**
 * 服务器环境集成类
 * User: sasumi
 */
class Server{
	public static function inWindows(){
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	/**
	 * 服务器最大上传文件大小
	 * 通过对比文件上传限制与post大小获取
	 * @param bool $human_readable 是否以可读方式返回
	 * @return int
	 */
	public static function getUploadMaxSize($human_readable = false){
		$upload_sz = trim(ini_get('upload_max_filesize'));
		$upload_sz = resolve_size($upload_sz);
		$post_sz = trim(ini_get('post_max_size'));
		$post_sz = resolve_size($post_sz);
		$ret = min($upload_sz, $post_sz);
		if($human_readable){
			return format_size($ret);
		}
		return $ret;
	}
}