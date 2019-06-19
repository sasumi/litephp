<?php

namespace Lite\Component\Upload;

use Lite\Component\File\MimeInfo;
use Lite\Component\Server;
use Lite\Component\Upload\Exception\UploadException;

/**
 * 文件上传配置
 * Class UploadConfig
 * @package Lite\Component\Upload
 */
class BaseConfig implements \Serializable, \JsonSerializable{
	//允许上传文件mime
	protected $allow_mimes = [];
	
	//上传文件限制最大大小
	protected $file_max_size = 0;
	
	//上传文件限制最小大小
	protected $file_min_size = 0;
	
	//文件名称规则
	//支持{MD5}、{EXT}、或{YMD}等日期占位符
	protected $file_name_rule = '';
	
	//是否自动修正错误扩展名
	protected $auto_fixed_extension = true;
	
	/**
	 * UploadConfig constructor.
	 * @param array $config
	 */
	public function __construct(array $config = []){
		isset($config['allow_mimes']) && $this->setAllowMimes($config['allow_mimes']);
		isset($config['file_max_size']) && $this->setFileMaxSize($config['file_max_size']);
		isset($config['file_min_size']) && $this->setFileMinSize($config['file_min_size']);
		isset($config['file_name_rule']) && $this->setFileNameRule($config['file_name_rule']);
		isset($config['auto_fixed_extension']) && $this->setAutoFixedExtension(!!$config['auto_fixed_extension']);
	}
	
	/**
	 * 设置扩展名
	 * @param array $extensions
	 */
	public function setAllowMimeByExtensions(array $extensions){
		$this->setAllowMimes(MimeInfo::getMimesByExtensions($extensions));
	}
	
	/**
	 * @return array
	 */
	public function getAllowMimes(){
		return $this->allow_mimes;
	}
	
	/**
	 * @param array $allow_mimes
	 */
	public function setAllowMimes($allow_mimes){
		$this->allow_mimes = $allow_mimes;
	}
	
	/**
	 * @return int
	 */
	public function getFileMaxSize(){
		return $this->file_max_size;
	}
	
	/**
	 * @param int $file_max_size
	 * @param bool $check_server_config 是否检测服务器配置允许上传文件最大尺寸
	 */
	public function setFileMaxSize($file_max_size, $check_server_config = true){
		if($check_server_config){
			$server_max_size = Server::getUploadMaxSize();
			if($server_max_size<$file_max_size){
				throw new UploadException('Server allow max upload size in-conformity');
			}
		}
		$this->file_max_size = $file_max_size;
	}
	
	/**
	 * @return int
	 */
	public function getFileMinSize(){
		return $this->file_min_size;
	}
	
	/**
	 * @param int $file_min_size
	 */
	public function setFileMinSize($file_min_size){
		$this->file_min_size = $file_min_size;
	}
	
	/**
	 * @param $org_file
	 * @return mixed|string
	 */
	public function getSaveFileName($org_file){
		if(is_scalar($this->file_name_rule)){
			return call_user_func($this->file_name_rule, $org_file);
		} else if($this->file_name_rule){
			$file = $this->file_name_rule;
			if(strpos($file, "{MD5}") !== false){
				$file = str_replace('{MD5}', md5(file_get_contents($org_file)), $file);
			}
			if(strpos('{EXT}', $file) !== false){
				$file = str_replace('{EXT}', MimeInfo::detectExtensionByFile($file), $file);
			}
			if(strpos('{', $file) !== false){
				$file = date($file);
			}
			return $file;
		} else{
			return end(explode('/', str_replace('\\', '/', $org_file)));
		}
	}
	
	/**
	 * @return string
	 */
	public function getFileNameRule(){
		return $this->file_name_rule;
	}
	
	/**
	 * @param string $file_name_rule
	 */
	public function setFileNameRule($file_name_rule){
		$this->file_name_rule = $file_name_rule;
	}
	
	/**
	 * @return bool
	 */
	public function isAutoFixedExtension(){
		return $this->auto_fixed_extension;
	}
	
	/**
	 * @param bool $auto_fixed_extension
	 */
	public function setAutoFixedExtension($auto_fixed_extension){
		$this->auto_fixed_extension = $auto_fixed_extension;
	}
	
	/**
	 * @return string
	 */
	public function __toString(){
		return $this->serialize();
	}
	
	/**
	 * 序列化
	 * @return string
	 */
	public function serialize(){
		return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
	}
	
	/**
	 * 反序列化
	 * @param string $serialized
	 */
	public function unserialize($serialized){
		$data = json_decode($serialized);
		$this->file_max_size = $data['file_max_size'];
	}
	
	/**
	 * 数据调试接口
	 * @return array
	 */
	public function __debugInfo(){
		return $this->jsonSerialize();
	}
	
	/**
	 * json序列化字段
	 * @return array
	 */
	public function jsonSerialize(){
		return [
			'allow_mimes'          => $this->allow_mimes,
			'file_max_size'        => $this->file_max_size,
			'file_min_size'        => $this->file_min_size,
			'file_name_rule'       => $this->file_name_rule,
			'auto_fixed_extension' => $this->auto_fixed_extension,
		];
	}
	
	/**
	 * json反序列化
	 * @param string $serialized
	 * @return \Lite\Component\Upload\BaseConfig
	 */
	public static function jsonUnSerialize($serialized){
		$data = json_decode($serialized, true);
		return new self($data);
	}
}