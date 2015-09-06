<?php
namespace Lite\Component;

/**
 * Mime信息
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
class MimeInfo {
	public static $map = array(
		'image/bmp' => 'bmp',
		'image/gif' => 'gif',
		'image/jpeg' => 'jpg',
		'application/pdf' => 'pdf',
		'image/png' => 'png',

		'application/zip' => 'zip',
		'application/vnd.ms-powerpoint' => 'ppt',
		'application/vnd.ms-word' => 'doc,docx',
		'application/kswps' => 'doc'
	);

	/**
	 *
	 * 检测文件mime信息是否符合
	 * @param  string $exts
	 * @param  string $mime
	 * @return boolean
 	*/
	public static function checkByExts($exts, $mime){
		$exts = strtolower($exts);
		$exts = explode(',', $exts);

		foreach($exts as $ext){
			foreach(self::$map as $check_mime=>$item){
				if(in_array($ext, explode(',', $item)) && $check_mime == $mime){
					return true;
				}
			}
		}
		return false;
	}

	public static function getMimesByExt($ext){
		$result = array();
		foreach(self::$map as $check_mime=>$item){
			if(in_array($ext, explode(',', $item))){
				$result[] = $check_mime;
			}
		}
		return $result;
	}
}