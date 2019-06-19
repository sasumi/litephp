<?php

namespace Lite\Component\Upload;

use Lite\Component\Upload\Exception\UploadException;

/**
 * 文件上传至web服务器
 * @property LocalConfig $config
 */
class UploadLocal extends Upload{
	public function __construct(LocalConfig $config = null){
		if(!$config){
			$config = new LocalConfig();
		}
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
		$dir = rtrim('/', str_replace('\\', '/', $dir));
		$new_file = $dir.$file_name;
		if(!rename($file, $new_file)){
			throw new UploadException('Upload fail');
		}
		return $file_name;
	}
}

