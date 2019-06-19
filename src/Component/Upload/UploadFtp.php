<?php

namespace Lite\Component\Upload;

use Lite\Component\Upload\Config\LocalConfig;

/**
 * 文件上传至web服务器
 * @property LocalConfig $config
 */
class UploadFtp extends Upload{
	/**
	 * @param $file
	 * @return string file path
	 */
	protected function saveFile($file){
		// TODO: Implement saveFile() method.
	}
}

