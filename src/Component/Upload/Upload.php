<?php

namespace Lite\Component\Upload;

use Lite\Component\File\MimeInfo;
use Lite\Component\Upload\Exception\UploadFileAccessException;
use Lite\Component\Upload\Exception\UploadSizeException;
use Lite\Component\Upload\Exception\UploadTypeException;

/**
 * 文件上传基础类
 */
abstract class Upload{
	protected $config;
	
	/**
	 * 单例
	 * @param BaseConfig|null $config
	 * @return static
	 */
	public static function instance(BaseConfig $config = null){
		static $instance;
		if(!$instance){
			$instance = new static($config);
		}
		return $instance;
	}
	
	/**
	 * Upload constructor.
	 * @param BaseConfig|null $config
	 */
	public function __construct(BaseConfig $config = null){
		$this->config = $config ?: new BaseConfig();
	}
	
	/**
	 * @param $file
	 * @return string file path
	 */
	abstract protected function saveFile($file);
	
	/**
	 * 单文件上传
	 * @param array $FILE
	 * @return string
	 */
	public function uploadFile(array $FILE = null){
		$FILE = $FILE ?: current($_FILES);
		$file = $FILE ?: $FILE['tmp_name'];
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
		$FILES = $FILES ?: $_FILES;
		$result = [];
		foreach($FILES as $FILE){
			$rst = $this->uploadFile($FILE);
			if(!$rst && $break_on_error){
				return null;
			}
			$result[] = $rst;
		}
		return $result;
	}
	
	/**
	 * 扁平化 $_FILES变量
	 * @return array
	 */
	protected function plainUploadFiles(){
		return $_FILES;
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