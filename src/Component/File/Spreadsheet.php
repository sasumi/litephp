<?php
namespace Lite\Component\File;

use Lite\Component\Net\Http;
use Lite\DB\Model;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_NumberFormat;
use function Lite\func\array_clear_empty;
use function Lite\func\array_trim_fields;
use function Lite\func\get_spreadsheet_column;
use function Lite\func\is_assoc_array;

/**
 * 数据文件输出处理
 */
abstract class Spreadsheet{
	/**
	 * 输出csv格式数据
	 * @param array $data 二维数组数据
	 * @param array $headers 指定显示字段以及转换后标题，格式如：['id'=>'编号','name'=>'名称']，缺省为数据所有字段
	 * @param array $config 其他控制配置
	 */
	public static function exportCsv(array $data, array $headers = array(), array $config = array()){
		$config = array_merge(array(
			'separator'     => ',', 					//分隔符
			'filename'      => date('YmdHis').'.csv', 	//输出文件名
			'from_encoding' => 'utf-8',					//输入字符编码
			'to_encoding'   => 'gb2312'					//输入字符编码（默认为gb2312，中文windows Excel使用）
		), $config);

		if(empty($headers)){
			$tmp = array_slice($data, 0, 1);
			$values = array_keys(array_pop($tmp));
			foreach($values as $val){
				$headers[$val] = $val;
			}
		}
		$str = implode($config['separator'], $headers)."\r\n";
		foreach($data as $item){
			$com = '';
			foreach($headers as $idx => $hd){
				$str .= $com.$item[$idx];
				$com = $config['separator'];
			}
			$str .= "\r\n";
		}
		Http::headerDownloadFile($config['filename']);
		echo mb_convert_encoding($str, $config['to_encoding'], $config['from_encoding']);
		exit;
	}

	/**
	 * 读取csv内容
	 * @param string $content
	 * @param array $config
	 * @return array
	 */
	public static function readCsv($content, array $config = array()){
		$config = array_merge(array(
			'start_offset'     => 1,        //数据开始行（如果首行为下标，start_offset必须大于0）
			'first_row_as_key' => true,     //是否首行作为数据下标返回（如果是，start_offset必须大于0）
			'fields'           => [],       //指定返数据下标（按顺序对应）
			'from_encoding'    => 'gb2312',
			'to_encoding'      => 'utf-8',
			'delimiter'        => ',',
		), $config);

		$data = [];
		$header = [];
		$content = iconv($config['from_encoding'], $config['to_encoding'], $content) ?: $content;
		$raws = explode("\n", $content);
		foreach($raws as $row_idx=>$row_str){
			//由于str_getcsv针对编码在不同系统环境中存在较大差异化，因此这里简单实用delimiter进行切割。
			//切割过程未考虑转移字符问题。
			$row = explode($config['delimiter'], $row_str);
			$row = array_map('trim', $row);
			if($row_idx == 0){
				if($config['first_row_as_key']){
					$header = $row;
					continue;
				}
				if($config['fields']){
					$header = $config['fields'];
				}
			}
			if($row_idx >= $config['start_offset']){
				self::dataSyncLen($header, $row);
				$tmp = $config['first_row_as_key'] ? array_combine($header, $row) : $row;
				$tmp = array_clear_empty($tmp);
				if($tmp){
					$data[] = $tmp;
				}
			}
		}
		return $data;
	}

	/**
	 * 读取CSV格式文件
	 * @param $file
	 * @param array $config
	 * @return array
	 */
	public static function readCsvFile($file, array $config = []){
		$config = array_merge(array(
			'start_offset'     => 1,        //数据开始行（如果首行为下标，start_offset必须大于0）
			'first_row_as_key' => true,     //是否首行作为数据下标返回（如果是，start_offset必须大于0）
			'fields'           => [],       //指定返数据下标（按顺序对应）
			'delimiter'        => ',',      //分隔符
			'from_encoding'    => 'gbk',    //来源编码
			'to_encoding'      => 'utf-8',  //目标编码
		), $config);

		$data = [];
		$header = [];
		self::readCsvFileChunk($file, function($row, $row_idx)use(&$data, &$header, $config){
			if($row_idx == 0){
				if($config['first_row_as_key']){
					$header = $row;
					return;
				}
				if($config['fields']){
					$header = $config['fields'];
				}
			}
			if($row_idx >= $config['start_offset']){
				self::dataSyncLen($header, $row);
				$data[] = $config['first_row_as_key'] ? array_combine($header, $row) : $row;
			}
		}, $config);
		return $data;
	}

	/**
	 * 分块读取CSV文件
	 * @param string $file CSV文件名
	 * @param callable $row_handler 行处理器，传参为：(array $row, int row_index)
	 * @param array $config 选项
	 * @return array
	 */
	public static function readCsvFileChunk($file, callable $row_handler, $config = []){
		$config = array_merge(array(
			'delimiter'        => ',',      //分隔符
			'from_encoding'    => 'gbk',    //来源编码
			'to_encoding'      => 'utf-8',  //目标编码
		), $config);

		$data = [];
		$row_idx = 0;
		$fp = fopen($file, 'r');
		while(($row = fgetcsv($fp, 0, $config['delimiter'])) !== false){
			$row = array_map('utf8_encode', $row);
			$row = array_map(function($str) use ($config){
				$str = trim($str);
				return $str ? (iconv($config['from_encoding'], $config['to_encoding'], $str) ?: $str) : $str;
			}, $row);
			$row_handler($row, $row_idx);
			$row_idx++;
		}
		return $data;
	}

	/**
	 * 同步头部与数据长度
	 * @param $header
	 * @param $row
	 */
	private static function dataSyncLen(&$header, &$row){
		$head_len = count($header);
		$row_len = count($row);
		if($head_len > $row_len){
			$row = array_pad($row, $head_len, '');
		}
		else if($head_len < $row_len){
			for($i=0; $i<($row_len - $head_len); $i++){
				$header[] = 'Row'.($head_len+$i);
			}
		}
	}

	/**
	 * 输出excel数据
	 * @param array $data
	 * @param array $headers
	 * @param array $config
	 */
	public static function exportExcelViaHtml(array $data, array $headers = array(), array $config = array()){
		$config = array_merge(array(
			'filename' => date('YmdHis').'.xls',
		), $config);

		if(empty($headers)){
			$tmp = array_slice($data, 0, 1);
			$values = array_keys(array_pop($tmp));
			foreach($values as $val){
				$headers[$val] = $val;
			}
		}

		$xls = array();
		$xls[] = "<html><meta http-equiv=content-type content=\"text ml; charset=UTF-8\"><body><table border='1'>";
		$xls[] = "<tr><td>".implode("</td><td>", array_values($headers)).'</td></tr>';
		foreach($data As $o){
			$line = array();
			foreach($headers AS $k => $v){
				$line[] = $o[$k];
			}
			$xls[] = '<tr><td style="vnd.ms-excel.numberformat:@">'.implode("</td><td style=\"vnd.ms-excel.numberformat:@\">", $line).'</td></tr>';
		}
		$xls[] = '</table></body></html>';
		$xls = join("\r\n", $xls);
		Http::headerDownloadFile($config['filename']);
		echo $xls;
		exit;
	}

	/**
	 * 导出excel并下载
	 * @param array $data 数据
	 * @param array $header 头部
	 * @param string $file
	 * @param array $meta
	 * @return mixed
	 * @throws \Exception
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Reader_Exception
	 */
	public static function exportExcel($data, $header, $file = '', $meta = []){
		$excel = new PHPExcel();
		$meta = array_merge([
			'Creator'        => '',
			'LastModifiedBy' => '',
			'Title'          => '',
			'Subject'        => '',
			'Description'    => '',
			'Category'       => '',
		], $meta);
		$excel->getProperties();

		foreach($meta as $field => $val){
			if($val){
				$excel->{'set'.$meta}($val);
			}
		}

		$sheet = $excel->setActiveSheetIndex(0);
		//设置头部
		foreach($header as $key => $head_name){
			$cell = get_spreadsheet_column($key+1).'1';
			$sheet->setCellValue($cell, $head_name);
		}
		//设置数据
		foreach($data as $j => $row){
			foreach($row as $x => $val){
				$cell = get_spreadsheet_column($x+1).($j+2);
				if(is_numeric($val) && strlen($val.'')>8){
					$val = " ".$val;
				}
				$sheet->setCellValue($cell, $val);
				$sheet->getStyle($cell)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
			}
		}
		$sheet->setTitle("Sheet1");

		if(!$file){
			$file = tmpfile();
		}
		$writer = PHPExcel_IOFactory::createWriter($excel, "Excel2007");
		$writer->save($file);
		return $file;
	}

	/**
	 * 分块输出CSV文件
	 * 该方法会记录上次调用文件句柄，因此仅允许单个进程执行单个输出。
	 * @see self::exportCSVPlainChunk
	 * @param $data
	 * @param array $fields 字段列表，格式如：['id','name'] 或  ['id'=>'编号', 'name'=>'名称'] 暂不支持其他方式
	 * @param $file_name
	 * @return bool
	 */
	public static function exportCSVChunk($data, $fields, $file_name){
		static $csv_file_fp;
		$fields = is_assoc_array($fields) ? $fields : array_combine($fields, $fields);
		if(!isset($csv_file_fp)){
			Http::headerDownloadFile($file_name);
			$csv_file_fp = fopen('php://output', 'a');
			$head = [];
			foreach($fields as $i => $v){
				$head[$i] = iconv('utf-8', 'gbk', $v);
			}
			fputcsv($csv_file_fp, $head);
		}

		$cnt = 0;   // 计数器
		$limit = 1000;  // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
		$count = count($data);  // 逐行取出数据，不浪费内存

		for($t = 0; $t<$count; $t++){
			$cnt++;
			if($limit == $cnt){ //刷新一下输出buffer，防止由于数据过多造成问题
				ob_flush();
				flush();
				$cnt = 0;
			}
			$row = [];
			foreach($fields as $f => $n){
				$row[] = mb_convert_encoding($data[$t][$f], 'gbk', 'utf-8');
			}
			fputcsv($csv_file_fp, $row);
			unset($row);
		}
		return true;
	}

	/**
	 * 动态平铺输出CSV文件，动态列，表头与数据不需要一一对应
	 * @see self::exportCSVChunk
	 * @param array $data 二维数据
	 * @param array $headers 头部列名，格式如：['姓名','性别','年龄','编号',...]
	 * @param $file_name
	 * @return bool
	 */
	public static function exportCSVPlainChunk($data, $headers = [], $file_name = ''){
		$file_name = $file_name ?: date('YmdHi').'.csv';
		static $csv_file_fp;
		if(!isset($csv_file_fp)){
			Http::headerDownloadFile($file_name);
			$csv_file_fp = fopen('php://output', 'a');
			if($headers){
				fputcsv($csv_file_fp, $headers);
			}
		}

		$cnt = 0;               //计数器
		$limit = 1000;          //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
		$count = count($data);  // 逐行取出数据，不浪费内存

		for($t = 0; $t<$count; $t++){
			$cnt++;
			if($limit == $cnt){ //刷新一下输出buffer，防止由于数据过多造成问题
				ob_flush();
				flush();
				$cnt = 0;
			}
			$row = [];
			foreach($data[$t] as $val){
				$row[] = mb_convert_encoding($val, 'gbk', 'utf-8');
			}
			fputcsv($csv_file_fp, $row);
			unset($row);
		}
		return true;
	}
	
	/**
	 * 根据DBModel自动分块导出CSV文件
	 * @param \Lite\DB\Model $query_model
	 * @param array $fields 字段列表，格式如：['id','name'] 或  ['id'=>'编号', 'name'=>'名称'] 暂不支持其他方式
	 * @param $file_name
	 * @param null $on_exporting 导出时数据处理函数
	 * @example 例：<p>
	 * DataExport::exportCSVChunkByModel(User::find('status=1'), [], 'user.csv');
	 * </p>
	 */
	public static function exportCSVChunkByModel(Model $query_model, $fields, $file_name, $on_exporting = null){
		$entity_fields = $query_model->getEntityFieldAliasMap();
		$spec_fields = [];
		if(!$fields){
			$spec_fields = $entity_fields;
		} else{
			$has_label = is_assoc_array($fields);
			if(!$has_label){
				foreach($fields as $k){
					$spec_fields[$k] = $entity_fields[$k];
				}
			} else{
				$spec_fields = $fields;
			}
		}
		$query_model->chunk(100, function($data) use ($spec_fields, $file_name, $on_exporting){
			if(is_callable($on_exporting)){
				foreach($data as $k => $item){
					$on_exporting($item);
					$data[$k] = $item;
				}
			}
			self::exportCSVChunk($data, $spec_fields, $file_name);
		}, true);
	}

	/**
	 * Excel 文件读取
	 * 代码会处理空白字符（trim），以及去除空白行
	 * @param string $file 文件路径
	 * @param int $sheet_index 工作表序号（0开始）或工作表名称
	 * @param int $start_row
	 * @return array
	 */
	public static function parseExcel($file, $sheet_index = 0, $start_row = 1){
		$eid = PHPExcel_IOFactory::identify($file);
		$reader = PHPExcel_IOFactory::createReader($eid);
		$php_excel = $reader->load($file);
		if(is_numeric($sheet_index)){
			$sheet = $php_excel->getSheet($sheet_index);
		} else {
			$sheet = $php_excel->getSheetByName($sheet_index);
		}

		if(!$sheet){
			return [];
		}

		$hr = $sheet->getHighestRow();
		$hc = $sheet->getHighestColumn();
		$row_data = [];

		//对所有单元格trim，去除空白行
		for($row = $start_row; $row <= $hr; $row++){
			$tmp = $sheet->rangeToArray("A{$row}:{$hc}{$row}", NULL, TRUE, FALSE)[0];
			$tmp = array_trim_fields($tmp);
			$all_empty = true;
			foreach($tmp as $val){
				if(strlen($val)){
					$all_empty = false;
					break;
				}
			}
			if(!$all_empty){
				$row_data[] = $tmp;
			}
		}
		return $row_data;
	}

	/**
	 * Excel 文件读取，以第一行标题作为关联数组下表方式返回
	 * @description 由于列名可能重复，该方法慎用！
	 * @param $file
	 * @param int $sheet
	 * @return array
	 */
	public static function parseExcelAsAssoc($file, $sheet = 0){
		$data = self::parseExcel($file, $sheet, 1);
		if(!$data){
			return [];
		}
		$first_row = array_shift($data);
		$ret = [];
		foreach($data as $k=>$row){
			$ret[$k] = array_combine(array_values($first_row), array_values($row));
		}
		return $ret;
	}
}
