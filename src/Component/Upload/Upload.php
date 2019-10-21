<?php
namespace Lite\Component\Upload;

use Lite\Component\File\MimeInfo;
use Lite\Component\Upload\Config\BaseConfig;
use Lite\Component\Upload\Exception\UploadFileAccessException;
use Lite\Component\Upload\Exception\UploadSizeException;
use Lite\Component\Upload\Exception\UploadTypeException;
use function Lite\func\_tl;
use function Lite\func\format_size;
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
		self::checkUploadFile($file, [
			'max_size'  => $this->config->getFileMaxSize(),
			'min_size'  => $this->config->getFileMinSize(),
			'mime_list' => $this->config->getAllowMimes(),
		]);
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
	 * 检测上传文件是否符合规则
	 * @param string $file 上传文件路径（$_FILE['tmp_name']）
	 * @param array $rules 规则配置
	 * <pre>
	 * 规则配置
	 *   assert_upload_file:必须为PHP上传文件
	 *   max_size:最大尺寸，由于文件已经上传，缺省为空,
	 *   min_size:最小文件尺寸,
	 *   mime_list: 允许mime清单,
	 *   ext_list: 扩展列表（会与mime_list组合使用，由扩展反向检测mime）
	 * </pre>
	 */
	public static function checkUploadFile($file, $rules = []){
		$rules = array_merge([
			'assert_upload_file' => true,
			'max_size'           => 0,
			'min_size'           => 1,
			'mime_list'          => [],
			'ext_list'           => [],
		], $rules);

		if($rules['assert_upload_file'] && !is_uploaded_file($file)){
			throw new UploadFileAccessException(_tl('No upload file detected'), null, $file);
		}

		$fsz = filesize($file);
		if(!$fsz){
			throw new UploadFileAccessException(_tl('Upload file content is empty'));
		}

		if($rules['max_size'] && $fsz > $rules['max_size']){
			$p = [
				'file_size' => format_size($fsz),
				'max_size'  => format_size($rules['max_size']),
			];
			throw new UploadSizeException(_tl('Upload file size bigger than config, {file_size} > {max_size}', $p), null, $p);
		}

		if($rules['min_size'] && $fsz < $rules['min_size']){
			throw new UploadSizeException(_tl('Upload file size smaller than config {file_size} < {min_size}', [
				'file_size' => format_size($fsz),
				'max_size'  => format_size($rules['min_size']),
			]), null, $rules['min_size']);
		}

		if($rules['mime_list']){
			$file_mime = MimeInfo::getMimeByFile($file);
			if(!in_array($file_mime, $rules['mime_list'])){
				throw new UploadTypeException(_tl('Upload file type(mime：{file_mime}) no support by config mime list:{mime_list}', [
					'file_mime' => $file_mime,
					'mime_list' => join(',', $rules['mime_list']),
				]), 0, $rules['mime_list']);
			}
		}

		if($rules['ext_list']){
			$file_mime = MimeInfo::getMimeByFile($file);
			if(!MimeInfo::checkByExtensions($rules['ext_list'], $file_mime)){
				throw new UploadTypeException(_tl('Upload file type(mime：{file_mime}) no support by config extension list:{ext_list}', [
					'file_mime' => $file_mime,
					'ext_list'  => join(',', $rules['ext_list']),
				]), 0, $rules['ext_list']);
			}
		}
	}
}