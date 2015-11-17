<?php
namespace Lite\Component;
use Lite\Exception\Exception;
use function Lite\func\dump;
use function Lite\func\format_size;

/**
 * 文件上传类
 * @example
 * $up = new Uploader(array('upload_dir'=>'c:/'));
 * $up->upload();
 */
class Uploader {
	private $config;

	/**
	 * 初始化
	 * @param array $config 上传配置
	 * @throws Exception
	 */
	public function __construct(array $config){
		$this->config = array_merge(array(
			'upload_dir' => '',						//上传目标物理目录（如果目录不存在，则自动新建）
			'file_name_converter' => '',			//文件名称转换方法（如无指定，会采用覆盖形式上传）
			'max_size' => 1024*1024*2,				//最大文件大小(B)
			'file_type' => 'jpg,png,jpeg,gif,bmp',	//支持文件类型（这里会检测meta信息）
			'max_file_count' => 1  					//单次最大上传文件个数
		), $config);

		if($this->getServerMaxSize() < $this->config['max_size']){
			//服务器配置上传大小与当前上传配置冲突，这里可以不需要抛出异常
			//throw(new Exception('UPLOAD SIZE LIMITED BY SERVER CONFIG'));
			$this->config['max_size'] = $this->getServerMaxSize();
		}

		if(!$this->config['upload_dir'] && !file_exists($this->config['upload_dir'])){
			throw(new Exception('NO UPLOAD DIR SETTING'));
		}
		$this->config['upload_dir'] = preg_replace('/\/$/', '', str_replace('\\', '/', $this->config['upload_dir']));
	}

	/**
	 * 获取整理好的需要上传文件列表
	 * @return array
	 */
	public function getUploadFiles(){
		$result_arr = array();
		foreach($_FILES as $key=>$file){
			if(is_array($file['name'])){
				for($i=0; $i<count($file['name']); $i++){
					$result_arr[] = array(
						'upload_key' => $key,
						'name' => $file['name'][$i],
						'type' => $file['type'][$i],
						'tmp_name' => $file['tmp_name'][$i],
						'error' => $file['error'][$i],
						'size' => $file['size'][$i]
					);
				}
			} else {
				$file['upload_key'] = $key;
				$result_arr[] = $file;
			}
		}
		return $result_arr;
	}

	/**
	 * 获取服务器设置 - 最大文件上传大小
	 * @return integer
	 */
	private function getServerMaxSize(){
		$val = trim(ini_get('upload_max_filesize'));
		$last = strtolower($val{strlen($val) - 1});
		switch ($last){
			case 'g':
			    $val *= 1024;
			case 'm':
			    $val *= 1024;
			case 'k':
			    $val *= 1024;
		}
		return $val;
	}

	/**
	 * 建文件夹
	 * @param  string $path
	 * @return bool
	 */
	private function buildDir($path){
		$dir = dirname($path);
		if(is_dir($dir)){
			return true;
		}
		return mkdir($dir);
	}

	/**
	 * 检查文件类型是否符合（包括文件后缀）
	 * @param  string $file_types
	 * @param  array $file
	 * @return bool
	 */
	private function checkFileType($file, $file_types){
		$ext = end(explode('.', strtolower($file['name'])));
		if(!in_array($ext, explode(',', $file_types))){
			return false;
		} else {
			return MimeInfo::checkByExts($this->config['file_type'], $file['type']);
		}
	}

	/**
	 * 上传文件
	 * @param array $fail
	 * @return array
	 */
	public function upload(&$fail=array()){
		$files = $this->getUploadFiles();
		$success_list = array();

		$files = array_slice($files, 0, $this->config['max_file_count']);

		foreach($files as $file){
			$error = '';
			$new_name = $file['name'];
			if($this->config['file_name_converter']){
				$new_name = call_user_func($this->config['file_name_converter'], $file['name']);
			}
			$new_path = $this->config['upload_dir'].'/'.$new_name;
			if($file['error']){
				$error = '系统错误';
			} else if($file['size'] > $this->config['max_size']){
				$error = '文件大小超出系统设置：'.format_size($this->config['max_size']);
			} else if(!$this->checkFileType($file, $this->config['file_type'])){
				$error = '文件类型错误';
			} else if(!$this->buildDir($new_path)){
				$error = '上传目录创建失败';
			} else if(!move_uploaded_file($file['tmp_name'], $new_path)){
				$error = '上传文件不存在';
			}

			if(!$error){
				$success_list[$file['upload_key']] = $new_name;
			} else {
				$fail[$file['upload_key']] = $error;
			}
		}
		return $success_list;
	}
}