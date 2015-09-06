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