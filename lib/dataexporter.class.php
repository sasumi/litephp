<?php
class DataExporter{
	public static function export($data, $headers=null, $format='csv'){
		$filename = date('YmdHis').".csv";
		$sep = ",";

		header("Content-type:text/csv");
		header("Content-Disposition:attachment;filename=".$filename);
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
		header('Expires:0');
		header('Pragma:public');

		if($headers){
			$str = implode($sep, $headers)."\r\n";
		}

		foreach($data as $key=>$item){
			$com = '';
			foreach($headers as $idx=>$hd){
				$str .= $com.$item[$idx];
				$com = $sep;
			}
			$str .= "\r\n";
		}

		echo mb_convert_encoding($str, 'gb2312', 'utf-8');
		//echo iconv('utf-8', 'gb2312', $str);
		exit;
	}

	public static function excel($data, $headers) {
		$filename = date('YmdHis').".xls";
		$xls[] = "<html><meta http-equiv=content-type content=\"text ml; charset=UTF-8\"><body><table border='1'>";
		$xls[] = "<tr><td>ID</td><td>" . implode("</td><td>", array_values($headers)) . '</td></tr>';
		foreach($data As $o) {
		$line = array(++$index);
		foreach($headers AS $k=>$v) {
		$line[] = $o[$k];
		}
		$xls[] = '<tr><td>'. implode("</td><td>", $line) . '</td></tr>';
		}
		$xls[] = '</table></body>< ml>';
		$xls = join("\r\n", $xls);
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		echo $xls;
		exit;
	}
}