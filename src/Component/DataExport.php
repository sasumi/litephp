<?php
namespace Lite\Component;

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
}