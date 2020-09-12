<?php

namespace Lite\Component\Upload\Exception;

use Lite\Exception\Exception;
use function Lite\Func\_tl;

class UploadException extends Exception {
	public static function phpErrorLang($error_code){
		$map = [
			UPLOAD_ERR_INI_SIZE   => _tl('UPLOAD_ERR_INI_SIZE'),
			UPLOAD_ERR_FORM_SIZE  => _tl('UPLOAD_ERR_FORM_SIZE'),
			UPLOAD_ERR_PARTIAL    => _tl('UPLOAD_ERR_PARTIAL'),
			UPLOAD_ERR_NO_FILE    => _tl('UPLOAD_ERR_NO_FILE'),
			UPLOAD_ERR_NO_TMP_DIR => _tl('UPLOAD_ERR_NO_TMP_DIR'),
			UPLOAD_ERR_CANT_WRITE => _tl('UPLOAD_ERR_CANT_WRITE'),
			UPLOAD_ERR_EXTENSION  => _tl('UPLOAD_ERR_EXTENSION'),
		];
		return $map[$error_code] ?: _tl('UPLOAD_UNKNOWN_ERROR({code})', $error_code);
	}
}
