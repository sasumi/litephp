<?php
namespace Lite\Component;
use Lite\DB\Model;
use function Lite\func\is_assoc_array;

/**
 * 数据输出处理
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
abstract class DataExport {
	/**
	 * 输出csv格式数据
	 * @param array $data
	 * @param array $headers
	 * @param array $config
	 */
	public static function exportCsv(array $data, array $headers=array(), array $config=array()){
		$config = array_merge(array(
			'separator' => ',',
			'filename' => date('YmdHis').'.csv',
			'from_encoding' => 'utf-8',
			'to_encoding' => 'gb2312'
		), $config);

		if(empty($headers)){
			$tmp = array_slice($data, 0, 1);
			$vals = array_keys(array_pop($tmp));
			foreach($vals as $val){
				$headers[$val] = $val;
			}
		}

		header("Content-type:text/csv");
		header("Content-Disposition:attachment;filename=".$config['filename']);
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
		header('Expires:0');
		header('Pragma:public');

		$str = implode($config['separator'], $headers)."\r\n";
		foreach($data as $item){
			$com = '';
			foreach($headers as $idx=>$hd){
				$str .= $com.$item[$idx];
				$com = $config['separator'];
			}
			$str .= "\r\n";
		}
		echo mb_convert_encoding($str, $config['to_encoding'], $config['from_encoding']);
		exit;
	}

	/**
	 * 读取csv内容
	 * @param string $content
	 * @param array $field_name 输出字段名，array('fieldA', 'fieldB')
	 * @param array $config
	 * @return array
	 */
	public static function readCsv($content, array $field_name=array(), array $config=array()){
		$config = array_merge(array(
			'separator' => ',',
			'start_offset' => 0,
			'from_encoding' => 'gb2312',
			'to_encoding' => 'utf-8'
		), $config);

		$content = mb_convert_encoding($content, $config['to_encoding'], $config['from_encoding']);
		$result = array();
		$lines = explode("\r\n", $content) ?: array();
		$lines = array_slice($lines, $config['start_offset']);

		foreach($lines as $line){
			$tmp = !empty($line) ? explode($config['separator'], $line) : null; //避免空行
			if(!empty($tmp)){
				$item = array();
				if(!empty($field_name)){
					foreach($field_name as $k=>$field){
						$item[$field] = $tmp[$k];
					}
				}
				$result[] = $item;
			}
		}
		return $result;
	}

	/**
	 * 输出excel数据
	 * @param array $data
	 * @param array $headers
	 * @param array $config
	 */
	public static function exportExcel(array $data, array $headers=array(), array $config=array()) {
		$config = array_merge(array(
			'filename' => date('YmdHis').'.xls',
		), $config);

		if(empty($headers)){
			$tmp = array_slice($data, 0, 1);
			$vals = array_keys(array_pop($tmp));
			foreach($vals as $val){
				$headers[$val] = $val;
			}
		}

		$xls = array();
		$xls[] = "<html><meta http-equiv=content-type content=\"text ml; charset=UTF-8\"><body><table border='1'>";
		$xls[] = "<tr><td>" . implode("</td><td>", array_values($headers)) . '</td></tr>';
		foreach($data As $o) {
			$line = array();
			foreach($headers AS $k=>$v) {
				$line[] = $o[$k];
			}
			$xls[] = '<tr><td style="vnd.ms-excel.numberformat:@">'. implode("</td><td style=\"vnd.ms-excel.numberformat:@\">", $line) . '</td></tr>';
		}
		$xls[] = '</table></body>< ml>';
		$xls = join("\r\n", $xls);
		header('Content-Disposition: attachment; filename="'.$config['filename'].'"');
		echo $xls;
		exit;
	}

	/**
	 * 分块输出CSV文件
	 * 该方法会记录上次调用文件句柄，因此仅允许单个进程执行单个输出。
	 * @param $data
	 * @param array $fields 字段列表，格式如：['id','name'] 或  ['id'=>'编号', 'name'=>'名称'] 暂不支持其他方式
	 * @param $file_name
	 * @return bool
	 */
	public static function exportCSVChunk($data, $fields, $file_name){
		static $_csv_chunk_fp;
		$fields = is_assoc_array($fields) ? $fields : array_combine($fields, $fields);
		if(!isset($_csv_chunk_fp)){
			header('Content-Type: application/csv');
			header('Content-Disposition: attachment; filename='.$file_name);
			$_csv_chunk_fp = fopen('php://output', 'a');
			$head = null;
			foreach($fields as $i => $v){
				$head[$i] = iconv('utf-8', 'gbk', $v);
			}
			fputcsv($_csv_chunk_fp, $head);
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
			foreach($fields as $f=>$n){
				$row[] = mb_convert_encoding($data[$t][$f], 'gbk', 'utf-8');
			}
			fputcsv($_csv_chunk_fp, $row);
			unset($row);
		}
		return true;
	}
	
	/**
	 * 根据DBModel自动分块导出CSV文件
	 * @param \Lite\DB\Model $query_model
	 * @param array $fields 字段列表，格式如：['id','name'] 或  ['id'=>'编号', 'name'=>'名称'] 暂不支持其他方式
	 * @param $file_name
	 * @example 例：<p>
	 * DataExport::exportCSVChunkByModel(User::find('status=1'), [], 'user.csv');
	 * </p>
	 */
	public static function exportCSVChunkByModel(Model $query_model, $fields = [], $file_name){
		$entity_fields = $query_model->getEntityFieldAliasMap();
		$spec_fields = [];
		if(!$fields){
			$spec_fields = $entity_fields;
		} else {
			$has_label = is_assoc_array($fields);
			if(!$has_label){
				foreach($fields as $k){
					$spec_fields[$k] = $entity_fields[$k];
				}
			} else {
				$spec_fields = $fields;
			}
		}
		$query_model->chunk(100, function($data) use ($spec_fields, $file_name){
			self::exportCSVChunk($data, $spec_fields, $file_name);
		}, true);
	}
}