<?php
/**
 * 文件相关操作函数
 * User: sasumi
 * Date: 2015/5/8
 * Time: 14:07
 */
namespace Lite\func;
use Lite\Component\Server;

/**
 * 递归的glob
 * Does not support flag GLOB_BRACE
 * @param $pattern
 * @param int $flags
 * @return array
 */
function glob_recursive($pattern, $flags = 0){
	$files = glob($pattern, $flags);
	foreach(glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir){
		$files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
	}
	
	//修正目录分隔符
	array_walk($files, function(&$file){
		$file = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $file);
	});
	return $files;
}

/**
 * 检查文件是否存在，且名称严格匹配大小写
 * @param $file
 * @return bool|null
 * true：文件存在，false：文件不存在，null：文件存在但大小写不一致
 */
function file_exists_case_sensitive($file){
	//Linux文件系统严格匹配大小写，因此直接使用is_file判断即可
	if(!Server::inWindows()){
		return !!is_file($file);
	}

	//windows如果文件不存在，不需要检查
	if(!is_file($file)){
		return false;
	}
	$r_file = str_replace('\\', '/', $file);
	$realpath = str_replace('\\', '/', realpath($r_file));
	return strcmp($r_file, $realpath) == 0 ? true : null;
}

/**
 * 解析路径字符串真实路径，去除相对路径信息
 * 相对于realpath，该函数不需要检查文件是否存在
 * <pre>
 * 调用格式：resolve_absolute_path("c:/a/b/./../../windows/system32");
 * 返回：c:/windows/system32
 * @param string $path 路径字符串
 * @return string
 */
function resolve_absolute_path($path) {
	$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
	$absolutes = array();
	foreach ($parts as $part) {
		if ('.' == $part) continue;
		if ('..' == $part) {
			array_pop($absolutes);
		} else {
			$absolutes[] = $part;
		}
	}
	return implode(DIRECTORY_SEPARATOR, $absolutes);
}

/**
 * 解析文件扩展名
 * @param $filename
 * @return string
 */
function resolve_file_extension($filename){
	if(strpos($filename, '.') <= 0){
		return '';
	}
	$tmp = explode('.', $filename);
	return strtolower(end($tmp));
}

/**
 * 检查文件是否存在，且名称允许大小写混淆
 * @param $file
 * @param null $parent
 * @return bool
 */
function file_exists_case_insensitive($file, $parent=null){
	if(is_file($file)){
		return $file;
	}

	$file = str_replace('\\', '/', $file);
	if($parent){
		$parent = str_replace('\\', '/', $parent);
		$parent = rtrim($parent, '/');
	} else {
		$tmp = explode('/', $file);
		array_pop($tmp);
		$parent = join('/', $tmp);
	}

	static $fs = [];
	if(!$fs[$parent]){
		$fs[$parent] = glob_recursive($parent.'/*', GLOB_NOSORT);
	}
	foreach($fs[$parent] as $f){
		if(strcasecmp($f, $file) === 0){
			return $f;
		}
	}
	return false;
}

/**
 * 递归拷贝目录
 * @param $src
 * @param $dst
 * @throw Exception
 */
function copy_recursive($src, $dst){
	$dir = opendir($src);
	mkdir($dst);
	while(false !== ($file = readdir($dir))){
		if(($file != '.') && ($file != '..')){
			if(is_dir($src.'/'.$file)){
				copy_recursive($src.'/'.$file, $dst.'/'.$file);
			} else{
				copy($src.'/'.$file, $dst.'/'.$file);
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
function get_dirs($dir){
	$dir_list = array();
	if(false != ($handle = opendir($dir))){
		$i = 0;
		while(false !== ($file = readdir($handle))){
			if($file != "." && $file != ".." && is_dir($dir.DIRECTORY_SEPARATOR.$file)){
				$dir_list[$i] = $dir.DIRECTORY_SEPARATOR.$file;
				$i++;
			}
		}
		closedir($handle);
	}
	return $dir_list;
}

/**
 * 开启session一次
 * 如原session状态未开启，则读取完session自动关闭，避免session锁定
 * @return bool
 */
function session_start_once(){
	if(php_sapi_name() === 'cli' ||
		session_status() === PHP_SESSION_DISABLED ||
		headers_sent()){
		return false;
	}
	$initialized = session_status() === PHP_SESSION_ACTIVE;
	if(!$initialized && !headers_sent()){
		session_start();
		session_write_close();
	};
	return true;
}

/**
 * 自动判断当前session状态，将$_SESSION写入数据到session中
 * 如原session状态时未开启，则写入操作完毕自动关闭session避免session锁定，否则保持不变
 * 调用方法：
 * session_write_scope(function(){
 *      $_SESSION['hello'] = 'world';
 *      unset($_SESSION['info']);
 * });
 * @param $handler
 * @return bool
 */
function session_write_scope(callable $handler){
	if(php_sapi_name() === 'cli' || session_status() === PHP_SESSION_DISABLED){
		call_user_func($handler);
		return false;
	}
	$initialized = session_status() === PHP_SESSION_ACTIVE;
	if(!$initialized && !headers_sent()){
		$exists_session = $_SESSION; //原PHP session_start()方法会覆盖 $_SESSION 变量，这里需要做一次恢复。
		session_start();
		$_SESSION = $exists_session;
	}
	call_user_func($handler);
	if(!$initialized){
		session_write_close();
	}
	return true;
}

/**
 * 立即提交session数据，同时根据上下文环境，选择性关闭session
 */
function session_write_once(){
	session_write_scope(function(){});
}

/**
 * 获取文件行数
 * @param string|resource $file 文件路径或文件句柄
 * @param string $line_separator 换行符
 * @return int
 */
function file_lines($file, $line_separator = "\n"){
	if(is_string($file)){
		$fp = fopen($file, 'rb');
	} else {
		$fp = $file;
	}
	$lines = 0;
	while(!feof($fp)){
		$lines += substr_count(fread($fp, 8192), $line_separator);
	}
	if(is_string($file)){
		fclose($fp);
	}
	return $lines;
}

/**
 * 回溯读取文件
 * @param string $file 文件
 * @param callable $callback 行处理函数
 * @param int $line_limit
 * @param string $line_separator 换行符
 */
function tail($file, callable $callback, $line_limit = 0, $line_separator = "\n"){
	$file_size = filesize($file);
	$fp = fopen($file, 'rb');
	$offset = 0;
	$text = '';
	$line_count = 0;
	while(($offset++) < $file_size){
		if(fseek($fp, -$offset, SEEK_END) === -1){
			break;
		}
		$t = fgetc($fp);
		if($t === $line_separator){
			//中断支持
			if($callback($text) === false){
				break;
			};
			$text = '';

			//行数限制
			if($line_limit && $line_count++ > $line_limit){
				break;
			}
		}else{
			$text = $t.$text;
		}
	}
	fclose($fp);
}

/**
 * read file by line
 * @param callable $handle
 * @param string $file
 * @param int $buff_size
 * @return bool
 */
function read_line(callable $handle, $file, $buff_size = 1024){
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
			for($i = 0; $i<$c; $i++){
				//tail
				if($i == ($c-1)){
					$line_buff = $tmp[$i];
				} else{
					//start
					if($i == 0){
						$line_buff .= $tmp[$i];
					} //middle
					else{
						$line_buff = $tmp[$i];
					}
					$read_line_counter++;
					if($handle($line_buff, $read_line_counter) === false){
						return false;
					}
				}
			}
		} else{
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
function get_folder_size($path){
	$total_size = 0;
	$files = scandir($path);
	foreach($files as $t){
		if(is_dir(rtrim($path, '/').'/'.$t)){
			if($t <> "." && $t <> ".."){
				$size = get_folder_size(rtrim($path, '/').'/'.$t);
				$total_size += $size;
			}
		} else{
			$size = filesize(rtrim($path, '/').'/'.$t);
			$total_size += $size;
		}
	}
	return $total_size;
}

/**
 * log 记录到文件
 * @param string $file 文件
 * @param mixed $content 记录内容
 * @param float|int $max_size 单文件最大尺寸，默认
 * @param int $max_files 最大记录文件数
 * @param string|null $pad_str 记录文件名追加字符串
 * @return bool|int 文件是否记录成功
 */
function log($file, $content, $max_size = 10*1024*1024, $max_files = 5, $pad_str = null){
	if(!is_string($content)){
		$content = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}
	$content = date('Y-m-d H:i:s')."  ".$content."\n";
	$pad_str = isset($pad_str) ? $pad_str : '-'.date('YmdHis');
	
	if(is_file($file) && $max_size && $max_size<filesize($file)){
		rename($file, $file.$pad_str);
		if($max_files>1){
			$fs = glob($file.'*');
			if(count($fs)>=$max_files){
				usort($fs, function($a, $b){
					return filemtime($a)>filemtime($b) ? 1 : -1;
				});
				foreach($fs as $k => $f){
					if($k<(count($fs)-$max_files+1)){
						unlink($f);
					}
				}
			}
		}
	}
	if(!is_file($file)){
		$dir = dirname($file);
		if(!is_dir($dir)){
			mkdir($dir, 0777, true);
		}
		touch($file);
	}
	return file_put_contents($file, $content, FILE_APPEND);
}

/**
 * log in temporary directory
 * @param string $filename
 * @param mixed $content
 * @param float|int $max_size
 * @param int $max_files
 * @param string|null $pad_str
 * @return bool|int
 */
function log_tmp_file($filename, $content, $max_size = 10*1024*1024, $max_files = 5, $pad_str = null){
	$tmp_dir = sys_get_temp_dir();
	$file = $tmp_dir.'/'.$filename;
	return log($file, $content, $max_size, $max_files, $pad_str);
}
