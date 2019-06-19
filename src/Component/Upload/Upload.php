<?php

namespace Lite\Component\Upload;

use Lite\Component\File\MimeInfo;
use Lite\Component\Upload\Config\BaseConfig;
use Lite\Component\Upload\Exception\UploadFileAccessException;
use Lite\Component\Upload\Exception\UploadSizeException;
use Lite\Component\Upload\Exception\UploadTypeException;
use function Lite\func\restructure_files;

/**
 * 文件上传基础类
 */
abstract class Upload{
	protected $config;
	
	/**
	 * 单例
	 * @param BaseConfig $config
	 * @return static
	 */
	public static function instance(BaseConfig $config){
		static $instance;
		if(!$instance){
			$instance = new static($config);
		}
		return $instance;
	}
	
	/**
	 * Upload constructor.
	 * @param BaseConfig $config
	 */
	public function __construct(BaseConfig $config){
		$this->config = $config ?: new BaseConfig();
	}
	
	/**
	 * @param $file
	 * @return string file path
	 */
	abstract protected function saveFile($file);
	
	/**
	 * 单文件上传
	 * @param $file
	 * @return string
	 */
	public function uploadFile($file){
		$file = $file ?: current($_FILES)['tmp_name'];
		$this->checkFile($file);
		return $this->saveFile($file);
	}
	
	/**
	 * 多文件上传
	 * @param array $FILES
	 * @param bool $break_on_error
	 * @return array|null
	 */
	public function uploadFiles(array $FILES = null, $break_on_error = true){
		$fs = $FILES ? array_column($FILES, 'tmp_name') : self::plainFiles();
		$result = [];
		foreach($fs as $f){
			$rst = $this->uploadFile($f);
			if(!$rst && $break_on_error){
				return null;
			}
			$result[] = $rst;
		}
		return $result;
	}
	
	/**
	 * 整理$_FILES文件格式回正常关联数组
	 * @return array
	 */
	protected static function plainFiles(){
		$FILES = restructure_files($_FILES);
		$fs = [];
		array_walk_recursive($FILES, function($v, $k) use (&$fs){
			if($k == 'tmp_name'){
				$fs[] = $v;
			}
		});
		return $fs;
	}
	
	/**
	 * 文件检查
	 * @param $file
	 * @param bool $upload_file_only
	 * @return bool
	 */
	protected function checkFile($file, $upload_file_only = true){
		if($upload_file_only && !is_uploaded_file($file)){
			throw new UploadFileAccessException('No upload file detected', null, $file);
		} else if(!is_file($file)){
			throw new UploadFileAccessException('No file detected', null, $file);
		}
		
		$fsz = filesize($file);
		if(!$fsz){
			throw new UploadFileAccessException('File content empty');
		}
		
		$size_max_limit = $this->config->getFileMaxSize();
		$size_min_limit = $this->config->getFileMinSize();
		if($size_max_limit && $fsz>$size_max_limit){
			throw new UploadSizeException('File size overload', null, $fsz);
		}
		if($size_min_limit){
			throw new UploadSizeException('File size too minimum', null, $size_min_limit);
		}
		if($allow_mimes = $this->config->getAllowMimes()){
			$mime = MimeInfo::getMimeByFile($file);
			if(!in_array($mime, $allow_mimes)){
				throw new UploadTypeException('Update file type miss match:'.$mime, 0, $allow_mimes);
			}
		}
		return true;
	}
}