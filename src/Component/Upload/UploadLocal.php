<?php

namespace Lite\Component\Upload;

use Lite\Component\Upload\Config\LocalConfig;
use Lite\Component\Upload\Exception\UploadException;

/**
 * 文件上传至web服务器
 * @property LocalConfig $config
 */
class UploadLocal extends Upload{
	public function __construct(LocalConfig $config){
		parent::__construct($config);
	}
	
	/**
	 * 保存文件
	 * @param string $file 上传文件，如果是WEB上传，则为 $_FILE[name]['tmp_name']
	 * @return string new file
	 */
	protected function saveFile($file){
		$dir = $this->config->getFileSavePath();
		$file_name = $this->config->getSaveFileName($file);
		$dir = rtrim(str_replace('\\', '/', $dir), '/').'/';
		$new_file = $dir.$file_name;
		$base_dir = dirname($new_file);
		if(!is_dir($base_dir)){
			mkdir($base_dir, null, true);
		}
		$rst = move_uploaded_file($file, $new_file);
		if(!$rst){
			throw new UploadException("Upload file move fail:$file => $new_file");
		}
		return $file_name;
	}
}

