<?php

namespace Lite\Component\Upload\Config;

use Lite\Component\File\MimeInfo;
use Lite\Component\Server;
use Lite\Component\Upload\Exception\UploadException;
use function Lite\func\_tl;

/**
 * 文件上传配置
 * Class UploadConfig
 * @package Lite\Component\Upload
 */
class BaseConfig{
	//允许上传文件mime
	protected $allow_mimes = [];
	
	//上传文件限制最大大小
	protected $file_max_size = 0;
	
	//上传文件限制最小大小
	protected $file_min_size = 0;
	
	//文件名称规则，支持以下规则：
	//{MD5}：文件内容md5摘要
	//{NAME}：原文件名（不包含扩展及路径）
	//{RAND16}：随机字符串16位
	//{RAND32}：随机字符串32位
	//{EXT}：扩展名（经过mime检测修复}
	//{Y}{M}{D}等日期占位符，规则与date函数一致
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
				throw new UploadException(_tl('Server allow max upload size in-conformity'));
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
		if(is_callable($this->file_name_rule)){
			return call_user_func($this->file_name_rule, $org_file);
		} else if($this->file_name_rule){
			$rule = $this->file_name_rule;
			if(strpos($rule, '{MD5}') !== false){
				$rule = str_replace('{MD5}', md5(file_get_contents($org_file)), $rule);
			}
			if(strpos($rule, '{RAND16}') !== false){
				$rule = str_replace('{RAND16}', substr(md5(mt_srand().time()), 0, 16), $rule);
			}
			if(strpos($rule, '{RAND32}') !== false){
				$rule = str_replace('{RAND32}', md5(mt_srand().time()), $rule);
			}
			if(strpos($rule, '{NAME}')){
				$f = end(explode('/', str_replace('\\', '/', $org_file)));
				$name = current(explode('.', $f));
				$rule = str_replace('{NAME}', $name, $rule);
			}
			if(strpos($rule, '{EXT}') !== false){
				$rule = str_replace('{EXT}', MimeInfo::detectExtensionByFile($org_file), $rule);
			}
			if(strpos($rule, '{') !== false){
				$rule = preg_replace_callback('/\{([^\}]+)}/', function($matches){
					return date($matches[1]);
				}, $rule);
			}
			return $rule;
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
}
