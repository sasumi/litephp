<?php
namespace Lite\Component\File;

use Lite\Component\Upload\Exception\UploadException;
use Lite\Component\Upload\UploadLocal;
use Lite\Exception\BizException;
use Lite\Exception\Exception;
use function Lite\func\_tl;
use function Lite\func\array_push_by_path;

class SpreadsheetImporter {
	//全维度错误索引,用于记录当前行、当前文件错误信息
	const FULL_DIMENSION_ERROR_INDEX = -1;

	//tmp下缓存文件夹名称
	public static $tmp_fold_name = 'upload_import';

	/**
	 * 缓存用户上传excel文件
	 * @param $tmp_name
	 * @return string $file_name 根据文件内容MD5生成
	 */
	public static function dumpUploadExcelFile($tmp_name){
		$tmp_fold = sys_get_temp_dir().'/'.self::$tmp_fold_name;

		try{
			UploadLocal::checkUploadFile($tmp_name, [
				'allow_mimes' => MimeInfo::getMimesByExtensions(['xls', 'xlsx']),
			]);
		}catch(UploadException $e){
			throw new BizException($e->getMessage(), $e->getCode(), $e->getData(), $e->getPrevious());
		}

		$raw_map = Spreadsheet::parseExcelAsAssoc($tmp_name);
		if(!$raw_map){
			throw new BizException(_tl('文件数据为空，请检查后上传'));
		}

		if(!is_dir($tmp_fold)){
			mkdir($tmp_fold, 0777, true);
		}

		$file_content = serialize($raw_map);
		$file_name = md5($file_content);

		if(!is_file("$tmp_fold/$file_name")){
			file_put_contents("$tmp_fold/$file_name", $file_content);
		}
		return $file_name;
	}

	/**
	 * 清理缓存
	 */
	public static function cleanCache(){
		$tmp_fold = sys_get_temp_dir().'/'.self::$tmp_fold_name;
		if(is_file($tmp_fold)){
			unlink($tmp_fold);
		}
	}

	/**
	 * 从文件缓存中获取数据
	 * @param $file_name
	 * @return array
	 */
	public static function fetchFile($file_name){
		$tmp_fold = sys_get_temp_dir().'/'.self::$tmp_fold_name;
		$file = "$tmp_fold/$file_name";
		//文件安全检查
		if(dirname(realpath($file)) != realpath($tmp_fold)){
			throw new Exception('File access deny');
		}
		$str = file_get_contents($file);
		return unserialize($str);
	}

	/**
	 * 数据检查过滤
	 * @param $raw_map
	 * @param array $row_rules
	 * <pre>规则格式：
	 * [字段名1 => [数据组装格式, 处理函数], ...]
	 * [字段名1 => 数据组装格式, ...]
	 * 处理函数可为空
	 * </pre>
	 * @param array $errors
	 * @return array
	 */
	public static function filterData($raw_map, array $row_rules, &$errors = []){
		$errors = [];
		$available_data = [];
		foreach($raw_map as $row_idx=>$row){
			$row_data = [];
			foreach($row_rules as $col_name => $mixed){
				$val = trim($row[$col_name]);
				if(is_string($mixed)){
					$field = $mixed;
					$handler = null;
				} else {
					list($field, $handler) = $mixed;
				}

				//对接处理函数
				if($handler){
					try{
						$val = $handler($val, $row);
					}catch(\Exception $e){
						$errors[$row_idx][$col_name] = $e->getMessage();
						continue;
					}
					//返回null，表示忽略该项数据
					if(!isset($val)){
						continue;
					}
				}
				array_push_by_path($row_data, $field, $val);
			}
			$available_data[$row_idx] = $row_data;
		}
		return $available_data;
	}

	/**
	 * 数据处理异常封装
	 * @param array $available_data
	 * @param callable $handler
	 * @param bool $break_on_error
	 * @return array
	 */
	public static function dataSaveWrapper(array $available_data, callable $handler, $break_on_error = false){
		$success_list = [];
		$error_list = [];
		foreach($available_data as $row_index=>$row){
			try {
				$handler($row);
				$success_list[$row_index] = $row_index;
			} catch(\Exception $e){
				$row_no = $row_index+1;
				$error_list[$row_index] = "第 {$row_no} 行发生错误：".$e->getMessage();
				if($break_on_error){
					break;
				}
			}
		}
		return [$success_list, $error_list];
	}
}