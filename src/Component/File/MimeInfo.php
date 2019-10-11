<?php

namespace Lite\Component\File;

/**
 * Mime信息
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
class MimeInfo{
	private static function getMimeMaps(){
		static $mime_maps;
		if(!$mime_maps){
			$mime_maps = include __DIR__.'/mime.extension.php';
		}
		return $mime_maps;
	}

	/**
	 * 检测文件mime信息是否符合
	 * @param array $extensions
	 * @param string $mime
	 * @return boolean
	 */
	public static function checkByExtensions(array $extensions, $mime){
		$mime_maps = self::getMimeMaps();
		$mime = strtolower($mime);

		foreach($extensions as $ext){
			if(in_array($mime, $mime_maps[$ext])){
				return true;
			}
		}
		return false;
	}

	/**
	 * 根据mime信息获取扩展名
	 * @param $mime
	 * @return int|null|string
	 */
	public static function getExtensionByMime($mime){
		$mime_info = strtolower($mime);
		$mime_maps = self::getMimeMaps();

		foreach($mime_maps as $ext => $mimes){
			if(in_array($mime_info, $mimes)){
				return $ext;
			}
		}
		return null;
	}

	/**
	 * 根据文件（MIME）信息获取扩展名
	 * @param $file
	 * @param bool $use_original_as_default
	 * @return int|mixed|null|string
	 */
	public static function detectExtensionByFile($file, $use_original_as_default = true){
		$mime = static::getMimeByFile($file);
		$ext = static::getExtensionByMime($mime);
		if(!$ext && $use_original_as_default){
			return end(explode('.', $file));
		}
		return $ext;
	}

	/**
	 * 通过文件获取mime信息
	 * @param $file
	 * @return mixed
	 */
	public static function getMimeByFile($file){
		$file_info = new \finfo(FILEINFO_MIME);
		$mime = current(explode(';', $file_info->file($file)));
		return $mime;
	}

	/**
	 * 获取后缀mime信息
	 * @param array $extensions
	 * @return array
	 */
	public static function getMimesByExtensions(array $extensions){
		$result = [];
		$mime_maps = self::getMimeMaps();

		foreach($extensions as $ext){
			if($mime_maps[$ext]){
				$result = array_merge($result, $mime_maps[$ext]);
			}
		}
		return array_unique($result);
	}

	/**
	 * 检测当前mime是否为图像
	 * @param $mime_content_type
	 * @return bool
	 */
	public static function isImage($mime_content_type){
		return stripos($mime_content_type, 'image/') === 0;
	}
}