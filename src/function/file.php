<?php
/**
 * 文件相关操作函数
 * User: sasumi
 * Date: 2015/5/8
 * Time: 14:07
 */
namespace Lite\func {
	/**
	 * 递归的glob
	 * Does not support flag GLOB_BRACE
	 * @param $pattern
	 * @param int $flags
	 * @return array
	 */
	function glob_recursive($pattern, $flags = 0) {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
			$files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
		}

		//修正目录分隔符
		array_walk($files, function (&$file) {
			$file = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $file);
		});
		return $files;
	}

	/**
	 * 递归拷贝目录
	 * @param $src
	 * @param $dst
	 * @throw Exception
	 */
	function copy_recursive($src, $dst) {
		$dir = opendir($src);
		mkdir($dst);
		while (false !== ($file = readdir($dir))) {
			if(($file != '.') && ($file != '..')) {
				if(is_dir($src . '/' . $file)) {
					copy_recursive($src . '/' . $file, $dst . '/' . $file);
				} else {
					copy($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	/**
	 * 获取模块文件夹列表
	 * @param string $dir
	 * @return array
	 **/
	function get_dirs($dir) {
		$dir_list = array();
		if(false != ($handle = opendir($dir))) {
			$i = 0;
			while (false !== ($file = readdir($handle))) {
				if($file != "." && $file != ".." && is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
					$dir_list[$i] = $dir . DIRECTORY_SEPARATOR . $file;
					$i++;
				}
			}
			closedir($handle);
		}
		return $dir_list;
	}

	/**
	 * read file by line
	 * @param callable $handle
	 * @param string $file
	 * @param int $buff_size
	 * @return bool
	 */
	function read_line(callable $handle, $file, $buff_size=1024){
		$hd = fopen($file, 'r') or die('file open fail');
		$stop = false;
		$line_buff = '';
		$read_line_counter = 0;
		while(!feof($hd) && !$stop){
			$buff = fgets($hd, $buff_size);
			$break_count = substr_count($buff, "\n");
			if($break_count){
				$tmp = explode("\n", $buff);
				$c = count($tmp);
				for($i=0; $i<$c; $i++){
					//tail
					if($i == ($c-1)){
						$line_buff = $tmp[$i];
					} else {
						//start
						if($i == 0){
							$line_buff .= $tmp[$i];
						}
						//middle
						else {
							$line_buff = $tmp[$i];
						}
						$read_line_counter++;
						if($handle($line_buff, $read_line_counter) === false){
							return false;
						}
					}
				}
			} else {
				$line_buff .= $buff;
			}
		}
		fclose($hd);
		return true;
	}

	/**
	 * 递归查询文件夹大小
	 * @param $path
	 * @return int
	 */
	function get_folder_size($path) {
		$total_size = 0;
		$files = scandir($path);
		foreach ($files as $t) {
			if(is_dir(rtrim($path, '/') . '/' . $t)) {
				if($t <> "." && $t <> "..") {
					$size = get_folder_size(rtrim($path, '/') . '/' . $t);
					$total_size += $size;
				}
			} else {
				$size = filesize(rtrim($path, '/') . '/' . $t);
				$total_size += $size;
			}
		}
		return $total_size;
	}
}