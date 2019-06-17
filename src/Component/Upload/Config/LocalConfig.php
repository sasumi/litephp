<?php

namespace Lite\Component\Upload;

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
	 * @throws \Lite\Component\Upload\Exception\UploadDirAccessException
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
	
	/**
	 * 反序列化
	 * @param string $serialized
	 */
	public function unserialize($serialized){
		$data = json_decode($serialized);
		$this->file_save_path = $data['file_save_path'];
		parent::unserialize($serialized);
	}
	
	/**
	 * json序列化字段
	 * @return array
	 */
	public function jsonSerialize(){
		$base = parent::jsonSerialize();
		$base['file_save_path'] = $this->file_save_path;
		return $base;
	}
}