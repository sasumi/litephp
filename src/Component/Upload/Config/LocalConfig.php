<?php

namespace Lite\Component\Upload\Config;

use Lite\Component\Upload\Exception\UploadDirAccessException;

/**
 * 本地文件上传配置
 * Class UploadConfig
 * @package Lite\Component\Upload
 */
class LocalConfig extends BaseConfig{
	//文件保存路径
	protected $file_save_path = '/';
	
	/**
	 * UploadConfig constructor.
	 * @param array $config
	 */
	public function __construct(array $config = []){
		parent::__construct($config);
		isset($config['file_save_path']) && $this->setFileSavePath($config['file_save_path']);
	}
	
	/**
	 * @param bool $auto_create
	 * @return string
	 */
	public function getFileSavePath($auto_create = true){
		if($auto_create && !is_dir($this->file_save_path)){
			if(!mkdir($this->file_save_path)){
				throw new UploadDirAccessException('Directory create fail:'.$this->file_save_path);
			}
		}
		return $this->file_save_path;
	}
	
	/**
	 * @param string $file_save_path
	 */
	public function setFileSavePath($file_save_path){
		$this->file_save_path = $file_save_path;
	}
}