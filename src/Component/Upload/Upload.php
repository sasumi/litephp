<?php
namespace Lite\Component\Upload;

use Lite\Exception\Exception;

/**
 * User: Administrator
 * Date: 2019/03/27
 * Time: 10:10
 */
class Upload{
	public static function fileCheck($file, array $allow_mimes, $max_size = 0, $access_upload_file = true){
		if($access_upload_file && !is_uploaded_file($file)){
			throw new UploadFileAccessException('No upload file detected', null, $file);
		} else if(!is_file($file)){
			throw new UploadFileAccessException('No file detected', null, $file);
		}

		$fsz = filesize($file);
		if(!$fsz){
			throw new UploadFileAccessException('File content empty');
		}

		if($max_size && $fsz>$max_size){
			throw new UploadSizeException('File size overload', null, $fsz);
		}

		$finfo = new \finfo(FILEINFO_MIME);
		$type = $finfo->file($file);
		



	}

	public static function quickUpload($file){

	}
}

class UploadDirAccessException extends Exception{

}

class UploadFileAccessException extends Exception{

}

class UploadTypeException extends Exception{

}

class UploadSizeException extends Exception{

}