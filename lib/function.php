<?php
/**
 * Array group by function
 * group array(); by keystr
 *
 * @author sasumi
 * @param array $array
 * @param string $keystr
 * @param boolean $limit
 * @return $array handle result
 */
function array_group($array, $keystr, $limit = false) {
	if (empty ($array) || !is_array($array)){
		return $array;
	}

	$tmp = $_result = array ();
	foreach ($array as $key => $item) {
		$sub_keys = array_keys($item);

		if (in_array($keystr, $sub_keys)) {
			$tmp = $item;
			$_result[$item[$keystr]][] = $item;
		} else {
			$_result[count($_result)][] = $item;
		}
	}
	if (!$limit) {
		return $_result;
	}

	$result = array ();
	foreach ($_result as $key => $item) {
		$result[$key] = $item[0];
	}
	return $result;
}

function alert($msg_content, $msg_url = null, $msg_expired = 0, $msg_flag = 0, $ext=null) {
	if (empty ($msg_url)) {
		$msg_url = !empty ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : (!empty ($_REQUEST['referer']) ? $_REQUEST['referer'] : '?');
	}
	$msg_content = trim($msg_content);
	$msg_expired = intval($msg_expired);
	$msg_flag = intval($msg_flag);
	$msg_ext = $ext;

	$tpl = !empty($GLOBALS['JUMP_FILE_PATH']) ? $GLOBALS['JUMP_FILE_PATH'] : dirname(__FILE__) . '/jumper.html';

	if (!file_exists($tpl)) {
		die('JUMPER NO FOUND :' . $tpl . 'jumper.php');
	} else {
		include $tpl;
	}
	die();
}

/**
 * check is function, string is excluded
 * @param mix $fun
 * @return boolean
 */
function is_function($fun){
	return is_callable($fun) && getType($fun) == 'object';
};

/**
* Array insert
* insert $data after specified position($insert_pos)
*/
function array_insert($src_array, $data, $insert_pos) {
	if (!in_array($insert_pos, array_keys($src_array))) {
		return array_push($src_array, $data);
	} else {
		$tmp_array = array ();
		$len = 0;

		foreach ($src_array as $key => $src) {
			$tmp_array[$key] = $src;
			$len++;
			if ($insert_pos === $key) {
				break;
			}
		}
		$tmp_array[] = $data;
		return array_merge($tmp_array, array_slice($src_array, $len));
	}
}


/**
 * 检测数组是否为递增下标数组
 * @param  array  $array
 * @return boolean
 */
function isAssocArray($array){
	return array_values($array) == $array;
}

/**
 * debug command
 * @global $GLOABLS['APP_CFG"]['DEBUG_IPS'] array('192.168.*.*', '172.13.*.*')
 */
function debug() {
	if(!$GLOBALS['APP_CFG']){
		$GLOBALS['APP_CFG'] = array('DEBUG_IPS'=>null);
	}
	$allow_ips = $GLOBALS['APP_CFG']['DEBUG_IPS'];
	if(!empty($allow_ips)){
		$c_ip = explode('.',get_ip());
		$in = false;
		foreach($allow_ips as $key=>$ip){
			$a_ip = explode('.', $ip);
			if(($a_ip[0] == '*' || $a_ip[0] == $c_ip[0]) &&
				($a_ip[1] == '*' || $a_ip[1] == $c_ip[1]) &&
				($a_ip[2] == '*' || $a_ip[2] == $c_ip[2]) &&
				($a_ip[3] == '*' || $a_ip[3] == $c_ip[3])){
				$in = true;
				break;
			}
		}
		if(!$in){ return false;}
	}

	$params = func_get_args();
	$tmp = $params;

	//normal debug
	if(count($params)>0){
		$act = array_pop($tmp) === 1;
		$params = $act ? array_slice($params, 0, -1) : $params;

		echo '<pre style="font-size:12px; background-color:#eee; color:green; margin:0 0 10px 0; padding:0.5em; border-bottom:1px solid gray; width:100%; left:0; top:0">';
		$comma = '';
		foreach($params as $var){
			echo $comma;
			var_dump($var);
			$trace = debug_backtrace();
			echo "File: <b style=\"color:gray\"> ".$trace[0]['file']."</b><br/>Line : <b>".$trace[0]['line']."</b><br/>";
			$comma = '<div style="height:0px; line-height:1px; font-size:1px; border-bottom:1px solid white; border-top:1px solid #ccc; margin:10px 0"></div>';
		}
		echo '</pre>';
		if ($act){
			die();
		}
	}

	//for tick debug
	else {
		if(++$GLOBALS['ONLY_FOR_DEBUG_INDEX'] >= $GLOBALS['TICK_DEBUG_START_INDEX']){
			$trace = debug_backtrace();
			echo '<pre style="diplay:block; font-size:12px; color:green; padding:2px 0; border-bottom:1px solid #ddd; clear:both;">'.
				'['.($GLOBALS['ONLY_FOR_DEBUG_INDEX']).'] <b>'.
				$trace[0]['file'].'</b> line:'.$trace[0]['line'].'</pre>';
		}
	}
}

/**
 * tick debug
 * @param string $step_offset "$start, $step" or $step
 */
function tick_debug($step_offset=1){
	$step_offset = (string) $step_offset;
	if(strstr($step_offset, ',') !== false){
		list($start, $step) = array_map('intval', explode(',', $step_offset));
	} else {
		$start = 0;
		$step = intval($step_offset);
	}
	$GLOBALS['TICK_DEBUG_START_INDEX'] = $start;
	register_tick_function('debug');
	eval("declare(ticks = $step);");
}

/**
 * check string is encode in UTF-8
 * @param string $string
 * @return  boolean
 **/
function is_utf8($string) {
	$is_utf8 = preg_match('%^(?:
		         [\x09\x0A\x0D\x20-\x7E]			# ASCII
		       | [\xC2-\xDF][\x80-\xBF]				# non-overlong 2-byte
		       |  \xE0[\xA0-\xBF][\x80-\xBF]		# excluding overlongs
		       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}	# straight 3-byte
		       |  \xED[\x80-\x9F][\x80-\xBF]		# excluding surrogates
		       |  \xF0[\x90-\xBF][\x80-\xBF]{2}		# planes 1-3
		       | [\xF1-\xF3][\x80-\xBF]{3}			# planes 4-15
		       |  \xF4[\x80-\x8F][\x80-\xBF]{2}		# plane 16
		   )*$%xs', $string);
	return $is_utf8;
}

/**
 *
 * @return string
 */
function get_ip(){
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"),  "unknown"))
		$ip = getenv("HTTP_CLIENT_IP");
	else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
		$ip = getenv("REMOTE_ADDR");
	else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
		$ip = $_SERVER['REMOTE_ADDR'];
	else
		$ip = "unknown";
   return($ip);
}

/**
 * 通过IP获取真实地址
 *
 * @param string $ip ip地址
 * @return string 返回地址字符串
 */
function get_location($ip=null) {
	//检测ip数据文件是否存在
	$ip_file = dirname(__FILE__).'/qqwrt.dat';
	if(!is_file($ip_file)){
		return false;
	}

	$ip = $ip ? $ip : get_ip();


	//检查IP地址
	if (!preg_match("/^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$/", $ip)) {
		return 'IP Address Error';
	}
	//打开IP数据文件
	if (!$fd = @ fopen($ip_file, 'rb')) {
		return 'IP date file not exists or access denied';
	}

	//分解IP进行运算，得出整形数
	$ip = explode('.', $ip);
	$ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

	//获取IP数据索引开始和结束位置
	$DataBegin = fread($fd, 4);
	$DataEnd = fread($fd, 4);
	$ipbegin = implode('', unpack('L', $DataBegin));
	if ($ipbegin < 0)
		$ipbegin += pow(2, 32);
	$ipend = implode('', unpack('L', $DataEnd));
	if ($ipend < 0)
		$ipend += pow(2, 32);
	$ipAllNum = ($ipend - $ipbegin) / 7 + 1;

	$BeginNum = 0;
	$EndNum = $ipAllNum;

	//使用二分查找法从索引记录中搜索匹配的IP记录
	while ($ip1num>$ipNum || $ip2num<$ipNum) {
		$Middle = intval(($EndNum + $BeginNum) / 2);

		//偏移指针到索引位置读取4个字节
		fseek($fd, $ipbegin +7 * $Middle);
		$ipData1 = fread($fd, 4);
		if (strlen($ipData1) < 4) {
			fclose($fd);
			return 'System Error';
		}
		//提取出来的数据转换成长整形，如果数据是负数则加上2的32次幂
		$ip1num = implode('', unpack('L', $ipData1));
		if ($ip1num < 0)
			$ip1num += pow(2, 32);

		//提取的长整型数大于我们IP地址则修改结束位置进行下一次循环
		if ($ip1num > $ipNum) {
			$EndNum = $Middle;
			continue;
		}

		//取完上一个索引后取下一个索引
		$DataSeek = fread($fd, 3);
		if (strlen($DataSeek) < 3) {
			fclose($fd);
			return 'System Error';
		}
		$DataSeek = implode('', unpack('L', $DataSeek . chr(0)));
		fseek($fd, $DataSeek);
		$ipData2 = fread($fd, 4);
		if (strlen($ipData2) < 4) {
			fclose($fd);
			return 'System Error';
		}
		$ip2num = implode('', unpack('L', $ipData2));
		if ($ip2num < 0)
			$ip2num += pow(2, 32);

		//没找到提示未知
		if ($ip2num < $ipNum) {
			if ($Middle == $BeginNum) {
				fclose($fd);
				return 'Unknown';
			}
			$BeginNum = $Middle;
		}
	}

	$ipFlag = fread($fd, 1);
	if ($ipFlag == chr(1)) {
		$ipSeek = fread($fd, 3);
		if (strlen($ipSeek) < 3) {
			fclose($fd);
			return 'System Error';
		}
		$ipSeek = implode('', unpack('L', $ipSeek . chr(0)));
		fseek($fd, $ipSeek);
		$ipFlag = fread($fd, 1);
	}

	if ($ipFlag == chr(2)) {
		$AddrSeek = fread($fd, 3);
		if (strlen($AddrSeek) < 3) {
			fclose($fd);
			return 'System Error';
		}
		$ipFlag = fread($fd, 1);
		if ($ipFlag == chr(2)) {
			$AddrSeek2 = fread($fd, 3);
			if (strlen($AddrSeek2) < 3) {
				fclose($fd);
				return 'System Error';
			}
			$AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
			fseek($fd, $AddrSeek2);
		} else {
			fseek($fd, -1, SEEK_CUR);
		}

		while (($char = fread($fd, 1)) != chr(0))
			$ipAddr2 .= $char;

		$AddrSeek = implode('', unpack('L', $AddrSeek . chr(0)));
		fseek($fd, $AddrSeek);

		while (($char = fread($fd, 1)) != chr(0))
			$ipAddr1 .= $char;
	} else {
		fseek($fd, -1, SEEK_CUR);
		while (($char = fread($fd, 1)) != chr(0))
			$ipAddr1 .= $char;

		$ipFlag = fread($fd, 1);
		if ($ipFlag == chr(2)) {
			$AddrSeek2 = fread($fd, 3);
			if (strlen($AddrSeek2) < 3) {
				fclose($fd);
				return 'System Error';
			}
			$AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
			fseek($fd, $AddrSeek2);
		} else {
			fseek($fd, -1, SEEK_CUR);
		}
		while (($char = fread($fd, 1)) != chr(0)) {
			$ipAddr2 .= $char;
		}
	}
	fclose($fd);

	//最后做相应的替换操作后返回结果
	if (preg_match('/http/i', $ipAddr2)) {
		$ipAddr2 = '';
	}
	$ipaddr = "$ipAddr1 $ipAddr2";
	$ipaddr = preg_replace('/CZ88.NET/is', '', $ipaddr);
	$ipaddr = preg_replace('/^s*/is', '', $ipaddr);
	$ipaddr = preg_replace('/s*$/is', '', $ipaddr);
	if (preg_match('/http/i', $ipaddr) || $ipaddr == '') {
		$ipaddr = 'Unknown';
	}

	return $ipaddr;
}

/**
 * 中文字符截断（不含HTML标记处理）
 * @param string $str 要截断的字符串
 * @param integer $len 长度
 * @param integer $start 开始位置
 * @param string $tail_fix 后置字符
 * @param string $encode 编码
 * @return string 处理过后的字符串
 */
function csubstr($str, $len, $start = 0, $tail_fix = '...', $encode = '') {
	$str = trim(str_ireplace('&nbsp;', ' ', strip_tags($str)));
	$str_length = strlen($str);
	$i = $n = 0;

	if ($str_length <= $len) {
		return $str;
	}

	//UTF-8 模式
	if (strtolower($encode) == 'utf-8' || is_utf8($str)) {
		$return_str = '';
		while (($n < $len) and ($start <= $str_length -1)) {
			$ascnum = Ord(substr($str, $start, 1)); //ascii码
			if ($ascnum >= 224) {
				$return_str = $return_str . substr($str, $start, 3);
				$start += 3;
				$n++;
			}
			elseif ($ascnum >= 192) {
				$return_str = $return_str . substr($str, $start, 2);
				$start += 2;
				$n++;
			} else {
				$return_str = $return_str . substr($str, $start, 1);
				$start += 1;
				$n = $n +0.5;
			}
		}

		if ($start >= $str_length -1) {
			$tail_fix = '';
		}
	}

	//其他编码gbk, gb2312
	else {
		for ($i = 0; $i < $str_length; $i++, $n++) {
			if (ord(substr($str, $i, 1)) > 0xa0) {
				$return_str .= $n >= $start ? substr($str, $i, 2) : '';
				$i++;
			} else if ($n >= $start) {
				$return_str .= substr($str, $i, 1);
			}
			if ($n >= $start + $len) {
				break;
			}
		}

		if ($i >= $str_length) {
			$tail_fix = '';
		}
	}
	return $return_str . $tail_fix;
}


/*
PHP URL encoding/decoding functions for Javascript interaction V3.0
(C) 2006 www.captain.at - all rights reserved
License: GPL
*/
function encodeURIComponent($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
      $result .= encodeURIComponentbycharacter(urlencode($string[$i]));
   }
   return $result;
}

function encodeURIComponentbycharacter($char) {
   if ($char == "+") { return "%20"; }
   if ($char == "%21") { return "!"; }
   if ($char == "%27") { return '"'; }
   if ($char == "%28") { return "("; }
   if ($char == "%29") { return ")"; }
   if ($char == "%2A") { return "*"; }
   if ($char == "%7E") { return "~"; }
   if ($char == "%80") { return "%E2%82%AC"; }
   if ($char == "%81") { return "%C2%81"; }
   if ($char == "%82") { return "%E2%80%9A"; }
   if ($char == "%83") { return "%C6%92"; }
   if ($char == "%84") { return "%E2%80%9E"; }
   if ($char == "%85") { return "%E2%80%A6"; }
   if ($char == "%86") { return "%E2%80%A0"; }
   if ($char == "%87") { return "%E2%80%A1"; }
   if ($char == "%88") { return "%CB%86"; }
   if ($char == "%89") { return "%E2%80%B0"; }
   if ($char == "%8A") { return "%C5%A0"; }
   if ($char == "%8B") { return "%E2%80%B9"; }
   if ($char == "%8C") { return "%C5%92"; }
   if ($char == "%8D") { return "%C2%8D"; }
   if ($char == "%8E") { return "%C5%BD"; }
   if ($char == "%8F") { return "%C2%8F"; }
   if ($char == "%90") { return "%C2%90"; }
   if ($char == "%91") { return "%E2%80%98"; }
   if ($char == "%92") { return "%E2%80%99"; }
   if ($char == "%93") { return "%E2%80%9C"; }
   if ($char == "%94") { return "%E2%80%9D"; }
   if ($char == "%95") { return "%E2%80%A2"; }
   if ($char == "%96") { return "%E2%80%93"; }
   if ($char == "%97") { return "%E2%80%94"; }
   if ($char == "%98") { return "%CB%9C"; }
   if ($char == "%99") { return "%E2%84%A2"; }
   if ($char == "%9A") { return "%C5%A1"; }
   if ($char == "%9B") { return "%E2%80%BA"; }
   if ($char == "%9C") { return "%C5%93"; }
   if ($char == "%9D") { return "%C2%9D"; }
   if ($char == "%9E") { return "%C5%BE"; }
   if ($char == "%9F") { return "%C5%B8"; }
   if ($char == "%A0") { return "%C2%A0"; }
   if ($char == "%A1") { return "%C2%A1"; }
   if ($char == "%A2") { return "%C2%A2"; }
   if ($char == "%A3") { return "%C2%A3"; }
   if ($char == "%A4") { return "%C2%A4"; }
   if ($char == "%A5") { return "%C2%A5"; }
   if ($char == "%A6") { return "%C2%A6"; }
   if ($char == "%A7") { return "%C2%A7"; }
   if ($char == "%A8") { return "%C2%A8"; }
   if ($char == "%A9") { return "%C2%A9"; }
   if ($char == "%AA") { return "%C2%AA"; }
   if ($char == "%AB") { return "%C2%AB"; }
   if ($char == "%AC") { return "%C2%AC"; }
   if ($char == "%AD") { return "%C2%AD"; }
   if ($char == "%AE") { return "%C2%AE"; }
   if ($char == "%AF") { return "%C2%AF"; }
   if ($char == "%B0") { return "%C2%B0"; }
   if ($char == "%B1") { return "%C2%B1"; }
   if ($char == "%B2") { return "%C2%B2"; }
   if ($char == "%B3") { return "%C2%B3"; }
   if ($char == "%B4") { return "%C2%B4"; }
   if ($char == "%B5") { return "%C2%B5"; }
   if ($char == "%B6") { return "%C2%B6"; }
   if ($char == "%B7") { return "%C2%B7"; }
   if ($char == "%B8") { return "%C2%B8"; }
   if ($char == "%B9") { return "%C2%B9"; }
   if ($char == "%BA") { return "%C2%BA"; }
   if ($char == "%BB") { return "%C2%BB"; }
   if ($char == "%BC") { return "%C2%BC"; }
   if ($char == "%BD") { return "%C2%BD"; }
   if ($char == "%BE") { return "%C2%BE"; }
   if ($char == "%BF") { return "%C2%BF"; }
   if ($char == "%C0") { return "%C3%80"; }
   if ($char == "%C1") { return "%C3%81"; }
   if ($char == "%C2") { return "%C3%82"; }
   if ($char == "%C3") { return "%C3%83"; }
   if ($char == "%C4") { return "%C3%84"; }
   if ($char == "%C5") { return "%C3%85"; }
   if ($char == "%C6") { return "%C3%86"; }
   if ($char == "%C7") { return "%C3%87"; }
   if ($char == "%C8") { return "%C3%88"; }
   if ($char == "%C9") { return "%C3%89"; }
   if ($char == "%CA") { return "%C3%8A"; }
   if ($char == "%CB") { return "%C3%8B"; }
   if ($char == "%CC") { return "%C3%8C"; }
   if ($char == "%CD") { return "%C3%8D"; }
   if ($char == "%CE") { return "%C3%8E"; }
   if ($char == "%CF") { return "%C3%8F"; }
   if ($char == "%D0") { return "%C3%90"; }
   if ($char == "%D1") { return "%C3%91"; }
   if ($char == "%D2") { return "%C3%92"; }
   if ($char == "%D3") { return "%C3%93"; }
   if ($char == "%D4") { return "%C3%94"; }
   if ($char == "%D5") { return "%C3%95"; }
   if ($char == "%D6") { return "%C3%96"; }
   if ($char == "%D7") { return "%C3%97"; }
   if ($char == "%D8") { return "%C3%98"; }
   if ($char == "%D9") { return "%C3%99"; }
   if ($char == "%DA") { return "%C3%9A"; }
   if ($char == "%DB") { return "%C3%9B"; }
   if ($char == "%DC") { return "%C3%9C"; }
   if ($char == "%DD") { return "%C3%9D"; }
   if ($char == "%DE") { return "%C3%9E"; }
   if ($char == "%DF") { return "%C3%9F"; }
   if ($char == "%E0") { return "%C3%A0"; }
   if ($char == "%E1") { return "%C3%A1"; }
   if ($char == "%E2") { return "%C3%A2"; }
   if ($char == "%E3") { return "%C3%A3"; }
   if ($char == "%E4") { return "%C3%A4"; }
   if ($char == "%E5") { return "%C3%A5"; }
   if ($char == "%E6") { return "%C3%A6"; }
   if ($char == "%E7") { return "%C3%A7"; }
   if ($char == "%E8") { return "%C3%A8"; }
   if ($char == "%E9") { return "%C3%A9"; }
   if ($char == "%EA") { return "%C3%AA"; }
   if ($char == "%EB") { return "%C3%AB"; }
   if ($char == "%EC") { return "%C3%AC"; }
   if ($char == "%ED") { return "%C3%AD"; }
   if ($char == "%EE") { return "%C3%AE"; }
   if ($char == "%EF") { return "%C3%AF"; }
   if ($char == "%F0") { return "%C3%B0"; }
   if ($char == "%F1") { return "%C3%B1"; }
   if ($char == "%F2") { return "%C3%B2"; }
   if ($char == "%F3") { return "%C3%B3"; }
   if ($char == "%F4") { return "%C3%B4"; }
   if ($char == "%F5") { return "%C3%B5"; }
   if ($char == "%F6") { return "%C3%B6"; }
   if ($char == "%F7") { return "%C3%B7"; }
   if ($char == "%F8") { return "%C3%B8"; }
   if ($char == "%F9") { return "%C3%B9"; }
   if ($char == "%FA") { return "%C3%BA"; }
   if ($char == "%FB") { return "%C3%BB"; }
   if ($char == "%FC") { return "%C3%BC"; }
   if ($char == "%FD") { return "%C3%BD"; }
   if ($char == "%FE") { return "%C3%BE"; }
   if ($char == "%FF") { return "%C3%BF"; }
   return $char;
}

function decodeURIComponent($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
       $decstr = "";
       for ($p = 0; $p <= 8; $p++) {
          $decstr .= $string[$i+$p];
       }
       list($decodedstr, $num) = decodeURIComponentbycharacter($decstr);
       $result .= urldecode($decodedstr);
       $i += $num ;
   }
   return $result;
}

function decodeURIComponentbycharacter($str) {

   $char = $str;

   if ($char == "%E2%82%AC") { return array("%80", 8); }
   if ($char == "%E2%80%9A") { return array("%82", 8); }
   if ($char == "%E2%80%9E") { return array("%84", 8); }
   if ($char == "%E2%80%A6") { return array("%85", 8); }
   if ($char == "%E2%80%A0") { return array("%86", 8); }
   if ($char == "%E2%80%A1") { return array("%87", 8); }
   if ($char == "%E2%80%B0") { return array("%89", 8); }
   if ($char == "%E2%80%B9") { return array("%8B", 8); }
   if ($char == "%E2%80%98") { return array("%91", 8); }
   if ($char == "%E2%80%99") { return array("%92", 8); }
   if ($char == "%E2%80%9C") { return array("%93", 8); }
   if ($char == "%E2%80%9D") { return array("%94", 8); }
   if ($char == "%E2%80%A2") { return array("%95", 8); }
   if ($char == "%E2%80%93") { return array("%96", 8); }
   if ($char == "%E2%80%94") { return array("%97", 8); }
   if ($char == "%E2%84%A2") { return array("%99", 8); }
   if ($char == "%E2%80%BA") { return array("%9B", 8); }

   $char = substr($str, 0, 6);

   if ($char == "%C2%81") { return array("%81", 5); }
   if ($char == "%C6%92") { return array("%83", 5); }
   if ($char == "%CB%86") { return array("%88", 5); }
   if ($char == "%C5%A0") { return array("%8A", 5); }
   if ($char == "%C5%92") { return array("%8C", 5); }
   if ($char == "%C2%8D") { return array("%8D", 5); }
   if ($char == "%C5%BD") { return array("%8E", 5); }
   if ($char == "%C2%8F") { return array("%8F", 5); }
   if ($char == "%C2%90") { return array("%90", 5); }
   if ($char == "%CB%9C") { return array("%98", 5); }
   if ($char == "%C5%A1") { return array("%9A", 5); }
   if ($char == "%C5%93") { return array("%9C", 5); }
   if ($char == "%C2%9D") { return array("%9D", 5); }
   if ($char == "%C5%BE") { return array("%9E", 5); }
   if ($char == "%C5%B8") { return array("%9F", 5); }
   if ($char == "%C2%A0") { return array("%A0", 5); }
   if ($char == "%C2%A1") { return array("%A1", 5); }
   if ($char == "%C2%A2") { return array("%A2", 5); }
   if ($char == "%C2%A3") { return array("%A3", 5); }
   if ($char == "%C2%A4") { return array("%A4", 5); }
   if ($char == "%C2%A5") { return array("%A5", 5); }
   if ($char == "%C2%A6") { return array("%A6", 5); }
   if ($char == "%C2%A7") { return array("%A7", 5); }
   if ($char == "%C2%A8") { return array("%A8", 5); }
   if ($char == "%C2%A9") { return array("%A9", 5); }
   if ($char == "%C2%AA") { return array("%AA", 5); }
   if ($char == "%C2%AB") { return array("%AB", 5); }
   if ($char == "%C2%AC") { return array("%AC", 5); }
   if ($char == "%C2%AD") { return array("%AD", 5); }
   if ($char == "%C2%AE") { return array("%AE", 5); }
   if ($char == "%C2%AF") { return array("%AF", 5); }
   if ($char == "%C2%B0") { return array("%B0", 5); }
   if ($char == "%C2%B1") { return array("%B1", 5); }
   if ($char == "%C2%B2") { return array("%B2", 5); }
   if ($char == "%C2%B3") { return array("%B3", 5); }
   if ($char == "%C2%B4") { return array("%B4", 5); }
   if ($char == "%C2%B5") { return array("%B5", 5); }
   if ($char == "%C2%B6") { return array("%B6", 5); }
   if ($char == "%C2%B7") { return array("%B7", 5); }
   if ($char == "%C2%B8") { return array("%B8", 5); }
   if ($char == "%C2%B9") { return array("%B9", 5); }
   if ($char == "%C2%BA") { return array("%BA", 5); }
   if ($char == "%C2%BB") { return array("%BB", 5); }
   if ($char == "%C2%BC") { return array("%BC", 5); }
   if ($char == "%C2%BD") { return array("%BD", 5); }
   if ($char == "%C2%BE") { return array("%BE", 5); }
   if ($char == "%C2%BF") { return array("%BF", 5); }
   if ($char == "%C3%80") { return array("%C0", 5); }
   if ($char == "%C3%81") { return array("%C1", 5); }
   if ($char == "%C3%82") { return array("%C2", 5); }
   if ($char == "%C3%83") { return array("%C3", 5); }
   if ($char == "%C3%84") { return array("%C4", 5); }
   if ($char == "%C3%85") { return array("%C5", 5); }
   if ($char == "%C3%86") { return array("%C6", 5); }
   if ($char == "%C3%87") { return array("%C7", 5); }
   if ($char == "%C3%88") { return array("%C8", 5); }
   if ($char == "%C3%89") { return array("%C9", 5); }
   if ($char == "%C3%8A") { return array("%CA", 5); }
   if ($char == "%C3%8B") { return array("%CB", 5); }
   if ($char == "%C3%8C") { return array("%CC", 5); }
   if ($char == "%C3%8D") { return array("%CD", 5); }
   if ($char == "%C3%8E") { return array("%CE", 5); }
   if ($char == "%C3%8F") { return array("%CF", 5); }
   if ($char == "%C3%90") { return array("%D0", 5); }
   if ($char == "%C3%91") { return array("%D1", 5); }
   if ($char == "%C3%92") { return array("%D2", 5); }
   if ($char == "%C3%93") { return array("%D3", 5); }
   if ($char == "%C3%94") { return array("%D4", 5); }
   if ($char == "%C3%95") { return array("%D5", 5); }
   if ($char == "%C3%96") { return array("%D6", 5); }
   if ($char == "%C3%97") { return array("%D7", 5); }
   if ($char == "%C3%98") { return array("%D8", 5); }
   if ($char == "%C3%99") { return array("%D9", 5); }
   if ($char == "%C3%9A") { return array("%DA", 5); }
   if ($char == "%C3%9B") { return array("%DB", 5); }
   if ($char == "%C3%9C") { return array("%DC", 5); }
   if ($char == "%C3%9D") { return array("%DD", 5); }
   if ($char == "%C3%9E") { return array("%DE", 5); }
   if ($char == "%C3%9F") { return array("%DF", 5); }
   if ($char == "%C3%A0") { return array("%E0", 5); }
   if ($char == "%C3%A1") { return array("%E1", 5); }
   if ($char == "%C3%A2") { return array("%E2", 5); }
   if ($char == "%C3%A3") { return array("%E3", 5); }
   if ($char == "%C3%A4") { return array("%E4", 5); }
   if ($char == "%C3%A5") { return array("%E5", 5); }
   if ($char == "%C3%A6") { return array("%E6", 5); }
   if ($char == "%C3%A7") { return array("%E7", 5); }
   if ($char == "%C3%A8") { return array("%E8", 5); }
   if ($char == "%C3%A9") { return array("%E9", 5); }
   if ($char == "%C3%AA") { return array("%EA", 5); }
   if ($char == "%C3%AB") { return array("%EB", 5); }
   if ($char == "%C3%AC") { return array("%EC", 5); }
   if ($char == "%C3%AD") { return array("%ED", 5); }
   if ($char == "%C3%AE") { return array("%EE", 5); }
   if ($char == "%C3%AF") { return array("%EF", 5); }
   if ($char == "%C3%B0") { return array("%F0", 5); }
   if ($char == "%C3%B1") { return array("%F1", 5); }
   if ($char == "%C3%B2") { return array("%F2", 5); }
   if ($char == "%C3%B3") { return array("%F3", 5); }
   if ($char == "%C3%B4") { return array("%F4", 5); }
   if ($char == "%C3%B5") { return array("%F5", 5); }
   if ($char == "%C3%B6") { return array("%F6", 5); }
   if ($char == "%C3%B7") { return array("%F7", 5); }
   if ($char == "%C3%B8") { return array("%F8", 5); }
   if ($char == "%C3%B9") { return array("%F9", 5); }
   if ($char == "%C3%BA") { return array("%FA", 5); }
   if ($char == "%C3%BB") { return array("%FB", 5); }
   if ($char == "%C3%BC") { return array("%FC", 5); }
   if ($char == "%C3%BD") { return array("%FD", 5); }
   if ($char == "%C3%BE") { return array("%FE", 5); }
   if ($char == "%C3%BF") { return array("%FF", 5); }

   $char = substr($str, 0, 3);
   if ($char == "%20") { return array("+", 2); }

   $char = substr($str, 0, 1);

   if ($char == "!") { return array("%21", 0); }
   if ($char == "\"") { return array("%27", 0); }
   if ($char == "(") { return array("%28", 0); }
   if ($char == ")") { return array("%29", 0); }
   if ($char == "*") { return array("%2A", 0); }
   if ($char == "~") { return array("%7E", 0); }

   if ($char == "%") {
      return array(substr($str, 0, 3), 2);
   } else {
      return array($char, 0);
   }
}

function encodeURI($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
      $result .= encodeURIbycharacter(urlencode($string[$i]));
   }
   return $result;
}

function encodeURIbycharacter($char) {
   if ($char == "+") { return "%20"; }
   if ($char == "%21") { return "!"; }
   if ($char == "%23") { return "#"; }
   if ($char == "%24") { return "$"; }
   if ($char == "%26") { return "&"; }
   if ($char == "%27") { return "\""; }
   if ($char == "%28") { return "("; }
   if ($char == "%29") { return ")"; }
   if ($char == "%2A") { return "*"; }
   if ($char == "%2B") { return "+"; }
   if ($char == "%2C") { return ","; }
   if ($char == "%2F") { return "/"; }
   if ($char == "%3A") { return ":"; }
   if ($char == "%3B") { return ";"; }
   if ($char == "%3D") { return "="; }
   if ($char == "%3F") { return "?"; }
   if ($char == "%40") { return "@"; }
   if ($char == "%7E") { return "~"; }
   if ($char == "%80") { return "%E2%82%AC"; }
   if ($char == "%81") { return "%C2%81"; }
   if ($char == "%82") { return "%E2%80%9A"; }
   if ($char == "%83") { return "%C6%92"; }
   if ($char == "%84") { return "%E2%80%9E"; }
   if ($char == "%85") { return "%E2%80%A6"; }
   if ($char == "%86") { return "%E2%80%A0"; }
   if ($char == "%87") { return "%E2%80%A1"; }
   if ($char == "%88") { return "%CB%86"; }
   if ($char == "%89") { return "%E2%80%B0"; }
   if ($char == "%8A") { return "%C5%A0"; }
   if ($char == "%8B") { return "%E2%80%B9"; }
   if ($char == "%8C") { return "%C5%92"; }
   if ($char == "%8D") { return "%C2%8D"; }
   if ($char == "%8E") { return "%C5%BD"; }
   if ($char == "%8F") { return "%C2%8F"; }
   if ($char == "%90") { return "%C2%90"; }
   if ($char == "%91") { return "%E2%80%98"; }
   if ($char == "%92") { return "%E2%80%99"; }
   if ($char == "%93") { return "%E2%80%9C"; }
   if ($char == "%94") { return "%E2%80%9D"; }
   if ($char == "%95") { return "%E2%80%A2"; }
   if ($char == "%96") { return "%E2%80%93"; }
   if ($char == "%97") { return "%E2%80%94"; }
   if ($char == "%98") { return "%CB%9C"; }
   if ($char == "%99") { return "%E2%84%A2"; }
   if ($char == "%9A") { return "%C5%A1"; }
   if ($char == "%9B") { return "%E2%80%BA"; }
   if ($char == "%9C") { return "%C5%93"; }
   if ($char == "%9D") { return "%C2%9D"; }
   if ($char == "%9E") { return "%C5%BE"; }
   if ($char == "%9F") { return "%C5%B8"; }
   if ($char == "%A0") { return "%C2%A0"; }
   if ($char == "%A1") { return "%C2%A1"; }
   if ($char == "%A2") { return "%C2%A2"; }
   if ($char == "%A3") { return "%C2%A3"; }
   if ($char == "%A4") { return "%C2%A4"; }
   if ($char == "%A5") { return "%C2%A5"; }
   if ($char == "%A6") { return "%C2%A6"; }
   if ($char == "%A7") { return "%C2%A7"; }
   if ($char == "%A8") { return "%C2%A8"; }
   if ($char == "%A9") { return "%C2%A9"; }
   if ($char == "%AA") { return "%C2%AA"; }
   if ($char == "%AB") { return "%C2%AB"; }
   if ($char == "%AC") { return "%C2%AC"; }
   if ($char == "%AD") { return "%C2%AD"; }
   if ($char == "%AE") { return "%C2%AE"; }
   if ($char == "%AF") { return "%C2%AF"; }
   if ($char == "%B0") { return "%C2%B0"; }
   if ($char == "%B1") { return "%C2%B1"; }
   if ($char == "%B2") { return "%C2%B2"; }
   if ($char == "%B3") { return "%C2%B3"; }
   if ($char == "%B4") { return "%C2%B4"; }
   if ($char == "%B5") { return "%C2%B5"; }
   if ($char == "%B6") { return "%C2%B6"; }
   if ($char == "%B7") { return "%C2%B7"; }
   if ($char == "%B8") { return "%C2%B8"; }
   if ($char == "%B9") { return "%C2%B9"; }
   if ($char == "%BA") { return "%C2%BA"; }
   if ($char == "%BB") { return "%C2%BB"; }
   if ($char == "%BC") { return "%C2%BC"; }
   if ($char == "%BD") { return "%C2%BD"; }
   if ($char == "%BE") { return "%C2%BE"; }
   if ($char == "%BF") { return "%C2%BF"; }
   if ($char == "%C0") { return "%C3%80"; }
   if ($char == "%C1") { return "%C3%81"; }
   if ($char == "%C2") { return "%C3%82"; }
   if ($char == "%C3") { return "%C3%83"; }
   if ($char == "%C4") { return "%C3%84"; }
   if ($char == "%C5") { return "%C3%85"; }
   if ($char == "%C6") { return "%C3%86"; }
   if ($char == "%C7") { return "%C3%87"; }
   if ($char == "%C8") { return "%C3%88"; }
   if ($char == "%C9") { return "%C3%89"; }
   if ($char == "%CA") { return "%C3%8A"; }
   if ($char == "%CB") { return "%C3%8B"; }
   if ($char == "%CC") { return "%C3%8C"; }
   if ($char == "%CD") { return "%C3%8D"; }
   if ($char == "%CE") { return "%C3%8E"; }
   if ($char == "%CF") { return "%C3%8F"; }
   if ($char == "%D0") { return "%C3%90"; }
   if ($char == "%D1") { return "%C3%91"; }
   if ($char == "%D2") { return "%C3%92"; }
   if ($char == "%D3") { return "%C3%93"; }
   if ($char == "%D4") { return "%C3%94"; }
   if ($char == "%D5") { return "%C3%95"; }
   if ($char == "%D6") { return "%C3%96"; }
   if ($char == "%D7") { return "%C3%97"; }
   if ($char == "%D8") { return "%C3%98"; }
   if ($char == "%D9") { return "%C3%99"; }
   if ($char == "%DA") { return "%C3%9A"; }
   if ($char == "%DB") { return "%C3%9B"; }
   if ($char == "%DC") { return "%C3%9C"; }
   if ($char == "%DD") { return "%C3%9D"; }
   if ($char == "%DE") { return "%C3%9E"; }
   if ($char == "%DF") { return "%C3%9F"; }
   if ($char == "%E0") { return "%C3%A0"; }
   if ($char == "%E1") { return "%C3%A1"; }
   if ($char == "%E2") { return "%C3%A2"; }
   if ($char == "%E3") { return "%C3%A3"; }
   if ($char == "%E4") { return "%C3%A4"; }
   if ($char == "%E5") { return "%C3%A5"; }
   if ($char == "%E6") { return "%C3%A6"; }
   if ($char == "%E7") { return "%C3%A7"; }
   if ($char == "%E8") { return "%C3%A8"; }
   if ($char == "%E9") { return "%C3%A9"; }
   if ($char == "%EA") { return "%C3%AA"; }
   if ($char == "%EB") { return "%C3%AB"; }
   if ($char == "%EC") { return "%C3%AC"; }
   if ($char == "%ED") { return "%C3%AD"; }
   if ($char == "%EE") { return "%C3%AE"; }
   if ($char == "%EF") { return "%C3%AF"; }
   if ($char == "%F0") { return "%C3%B0"; }
   if ($char == "%F1") { return "%C3%B1"; }
   if ($char == "%F2") { return "%C3%B2"; }
   if ($char == "%F3") { return "%C3%B3"; }
   if ($char == "%F4") { return "%C3%B4"; }
   if ($char == "%F5") { return "%C3%B5"; }
   if ($char == "%F6") { return "%C3%B6"; }
   if ($char == "%F7") { return "%C3%B7"; }
   if ($char == "%F8") { return "%C3%B8"; }
   if ($char == "%F9") { return "%C3%B9"; }
   if ($char == "%FA") { return "%C3%BA"; }
   if ($char == "%FB") { return "%C3%BB"; }
   if ($char == "%FC") { return "%C3%BC"; }
   if ($char == "%FD") { return "%C3%BD"; }
   if ($char == "%FE") { return "%C3%BE"; }
   if ($char == "%FF") { return "%C3%BF"; }
   return $char;
}

function decodeURI($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
       $decstr = "";
       for ($p = 0; $p <= 8; $p++) {
          $decstr .= $string[$i+$p];
       }
       list($decodedstr, $num) = decodeURIbycharacter($decstr);
       $result .= urldecode($decodedstr);
       $i += $num ;
   }
   return $result;
}

function decodeURIbycharacter($str) {

   $char = $str;

   if ($char == "%E2%82%AC") { return array("%80", 8); }
   if ($char == "%E2%80%9A") { return array("%82", 8); }
   if ($char == "%E2%80%9E") { return array("%84", 8); }
   if ($char == "%E2%80%A6") { return array("%85", 8); }
   if ($char == "%E2%80%A0") { return array("%86", 8); }
   if ($char == "%E2%80%A1") { return array("%87", 8); }
   if ($char == "%E2%80%B0") { return array("%89", 8); }
   if ($char == "%E2%80%B9") { return array("%8B", 8); }
   if ($char == "%E2%80%98") { return array("%91", 8); }
   if ($char == "%E2%80%99") { return array("%92", 8); }
   if ($char == "%E2%80%9C") { return array("%93", 8); }
   if ($char == "%E2%80%9D") { return array("%94", 8); }
   if ($char == "%E2%80%A2") { return array("%95", 8); }
   if ($char == "%E2%80%93") { return array("%96", 8); }
   if ($char == "%E2%80%94") { return array("%97", 8); }
   if ($char == "%E2%84%A2") { return array("%99", 8); }
   if ($char == "%E2%80%BA") { return array("%9B", 8); }

   $char = substr($str, 0, 6);

   if ($char == "%C2%81") { return array("%81", 5); }
   if ($char == "%C6%92") { return array("%83", 5); }
   if ($char == "%CB%86") { return array("%88", 5); }
   if ($char == "%C5%A0") { return array("%8A", 5); }
   if ($char == "%C5%92") { return array("%8C", 5); }
   if ($char == "%C2%8D") { return array("%8D", 5); }
   if ($char == "%C5%BD") { return array("%8E", 5); }
   if ($char == "%C2%8F") { return array("%8F", 5); }
   if ($char == "%C2%90") { return array("%90", 5); }
   if ($char == "%CB%9C") { return array("%98", 5); }
   if ($char == "%C5%A1") { return array("%9A", 5); }
   if ($char == "%C5%93") { return array("%9C", 5); }
   if ($char == "%C2%9D") { return array("%9D", 5); }
   if ($char == "%C5%BE") { return array("%9E", 5); }
   if ($char == "%C5%B8") { return array("%9F", 5); }
   if ($char == "%C2%A0") { return array("%A0", 5); }
   if ($char == "%C2%A1") { return array("%A1", 5); }
   if ($char == "%C2%A2") { return array("%A2", 5); }
   if ($char == "%C2%A3") { return array("%A3", 5); }
   if ($char == "%C2%A4") { return array("%A4", 5); }
   if ($char == "%C2%A5") { return array("%A5", 5); }
   if ($char == "%C2%A6") { return array("%A6", 5); }
   if ($char == "%C2%A7") { return array("%A7", 5); }
   if ($char == "%C2%A8") { return array("%A8", 5); }
   if ($char == "%C2%A9") { return array("%A9", 5); }
   if ($char == "%C2%AA") { return array("%AA", 5); }
   if ($char == "%C2%AB") { return array("%AB", 5); }
   if ($char == "%C2%AC") { return array("%AC", 5); }
   if ($char == "%C2%AD") { return array("%AD", 5); }
   if ($char == "%C2%AE") { return array("%AE", 5); }
   if ($char == "%C2%AF") { return array("%AF", 5); }
   if ($char == "%C2%B0") { return array("%B0", 5); }
   if ($char == "%C2%B1") { return array("%B1", 5); }
   if ($char == "%C2%B2") { return array("%B2", 5); }
   if ($char == "%C2%B3") { return array("%B3", 5); }
   if ($char == "%C2%B4") { return array("%B4", 5); }
   if ($char == "%C2%B5") { return array("%B5", 5); }
   if ($char == "%C2%B6") { return array("%B6", 5); }
   if ($char == "%C2%B7") { return array("%B7", 5); }
   if ($char == "%C2%B8") { return array("%B8", 5); }
   if ($char == "%C2%B9") { return array("%B9", 5); }
   if ($char == "%C2%BA") { return array("%BA", 5); }
   if ($char == "%C2%BB") { return array("%BB", 5); }
   if ($char == "%C2%BC") { return array("%BC", 5); }
   if ($char == "%C2%BD") { return array("%BD", 5); }
   if ($char == "%C2%BE") { return array("%BE", 5); }
   if ($char == "%C2%BF") { return array("%BF", 5); }
   if ($char == "%C3%80") { return array("%C0", 5); }
   if ($char == "%C3%81") { return array("%C1", 5); }
   if ($char == "%C3%82") { return array("%C2", 5); }
   if ($char == "%C3%83") { return array("%C3", 5); }
   if ($char == "%C3%84") { return array("%C4", 5); }
   if ($char == "%C3%85") { return array("%C5", 5); }
   if ($char == "%C3%86") { return array("%C6", 5); }
   if ($char == "%C3%87") { return array("%C7", 5); }
   if ($char == "%C3%88") { return array("%C8", 5); }
   if ($char == "%C3%89") { return array("%C9", 5); }
   if ($char == "%C3%8A") { return array("%CA", 5); }
   if ($char == "%C3%8B") { return array("%CB", 5); }
   if ($char == "%C3%8C") { return array("%CC", 5); }
   if ($char == "%C3%8D") { return array("%CD", 5); }
   if ($char == "%C3%8E") { return array("%CE", 5); }
   if ($char == "%C3%8F") { return array("%CF", 5); }
   if ($char == "%C3%90") { return array("%D0", 5); }
   if ($char == "%C3%91") { return array("%D1", 5); }
   if ($char == "%C3%92") { return array("%D2", 5); }
   if ($char == "%C3%93") { return array("%D3", 5); }
   if ($char == "%C3%94") { return array("%D4", 5); }
   if ($char == "%C3%95") { return array("%D5", 5); }
   if ($char == "%C3%96") { return array("%D6", 5); }
   if ($char == "%C3%97") { return array("%D7", 5); }
   if ($char == "%C3%98") { return array("%D8", 5); }
   if ($char == "%C3%99") { return array("%D9", 5); }
   if ($char == "%C3%9A") { return array("%DA", 5); }
   if ($char == "%C3%9B") { return array("%DB", 5); }
   if ($char == "%C3%9C") { return array("%DC", 5); }
   if ($char == "%C3%9D") { return array("%DD", 5); }
   if ($char == "%C3%9E") { return array("%DE", 5); }
   if ($char == "%C3%9F") { return array("%DF", 5); }
   if ($char == "%C3%A0") { return array("%E0", 5); }
   if ($char == "%C3%A1") { return array("%E1", 5); }
   if ($char == "%C3%A2") { return array("%E2", 5); }
   if ($char == "%C3%A3") { return array("%E3", 5); }
   if ($char == "%C3%A4") { return array("%E4", 5); }
   if ($char == "%C3%A5") { return array("%E5", 5); }
   if ($char == "%C3%A6") { return array("%E6", 5); }
   if ($char == "%C3%A7") { return array("%E7", 5); }
   if ($char == "%C3%A8") { return array("%E8", 5); }
   if ($char == "%C3%A9") { return array("%E9", 5); }
   if ($char == "%C3%AA") { return array("%EA", 5); }
   if ($char == "%C3%AB") { return array("%EB", 5); }
   if ($char == "%C3%AC") { return array("%EC", 5); }
   if ($char == "%C3%AD") { return array("%ED", 5); }
   if ($char == "%C3%AE") { return array("%EE", 5); }
   if ($char == "%C3%AF") { return array("%EF", 5); }
   if ($char == "%C3%B0") { return array("%F0", 5); }
   if ($char == "%C3%B1") { return array("%F1", 5); }
   if ($char == "%C3%B2") { return array("%F2", 5); }
   if ($char == "%C3%B3") { return array("%F3", 5); }
   if ($char == "%C3%B4") { return array("%F4", 5); }
   if ($char == "%C3%B5") { return array("%F5", 5); }
   if ($char == "%C3%B6") { return array("%F6", 5); }
   if ($char == "%C3%B7") { return array("%F7", 5); }
   if ($char == "%C3%B8") { return array("%F8", 5); }
   if ($char == "%C3%B9") { return array("%F9", 5); }
   if ($char == "%C3%BA") { return array("%FA", 5); }
   if ($char == "%C3%BB") { return array("%FB", 5); }
   if ($char == "%C3%BC") { return array("%FC", 5); }
   if ($char == "%C3%BD") { return array("%FD", 5); }
   if ($char == "%C3%BE") { return array("%FE", 5); }
   if ($char == "%C3%BF") { return array("%FF", 5); }

   $char = substr($str, 0, 3);
   if ($char == "%20") { return array("+", 2); }

   $char = substr($str, 0, 1);

   if ($char == "!") { return array("%21", 0); }
   if ($char == "#") { return array("%23", 0); }
   if ($char == "$") { return array("%24", 0); }
   if ($char == "&") { return array("%26", 0); }
   if ($char == "\"") { return array("%27", 0); }
   if ($char == "(") { return array("%28", 0); }
   if ($char == ")") { return array("%29", 0); }
   if ($char == "*") { return array("%2A", 0); }
   if ($char == "+") { return array("%2B", 0); }
   if ($char == ",") { return array("%2C", 0); }
   if ($char == "/") { return array("%2F", 0); }
   if ($char == ":") { return array("%3A", 0); }
   if ($char == ";") { return array("%3B", 0); }
   if ($char == "=") { return array("%3D", 0); }
   if ($char == "?") { return array("%3F", 0); }
   if ($char == "@") { return array("%40", 0); }
   if ($char == "~") { return array("%7E", 0); }

   if ($char == "%") {
      return array(substr($str, 0, 3), 2);
   } else {
      return array($char, 0);
   }
}

function escape($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
      $result .= escapebycharacter(urlencode($string[$i]));
   }
   return $result;
}

function escapebycharacter($char) {
   if ($char == '+') { return '%20'; }
   if ($char == '%2A') { return '*'; }
   if ($char == '%2B') { return '+'; }
   if ($char == '%2F') { return '/'; }
   if ($char == '%40') { return '@'; }
   if ($char == '%80') { return '%u20AC'; }
   if ($char == '%82') { return '%u201A'; }
   if ($char == '%83') { return '%u0192'; }
   if ($char == '%84') { return '%u201E'; }
   if ($char == '%85') { return '%u2026'; }
   if ($char == '%86') { return '%u2020'; }
   if ($char == '%87') { return '%u2021'; }
   if ($char == '%88') { return '%u02C6'; }
   if ($char == '%89') { return '%u2030'; }
   if ($char == '%8A') { return '%u0160'; }
   if ($char == '%8B') { return '%u2039'; }
   if ($char == '%8C') { return '%u0152'; }
   if ($char == '%8E') { return '%u017D'; }
   if ($char == '%91') { return '%u2018'; }
   if ($char == '%92') { return '%u2019'; }
   if ($char == '%93') { return '%u201C'; }
   if ($char == '%94') { return '%u201D'; }
   if ($char == '%95') { return '%u2022'; }
   if ($char == '%96') { return '%u2013'; }
   if ($char == '%97') { return '%u2014'; }
   if ($char == '%98') { return '%u02DC'; }
   if ($char == '%99') { return '%u2122'; }
   if ($char == '%9A') { return '%u0161'; }
   if ($char == '%9B') { return '%u203A'; }
   if ($char == '%9C') { return '%u0153'; }
   if ($char == '%9E') { return '%u017E'; }
   if ($char == '%9F') { return '%u0178'; }
   return $char;
}

function unescape($string) {
   $result = "";
   for ($i = 0; $i < strlen($string); $i++) {
       $decstr = "";
       for ($p = 0; $p <= 5; $p++) {
          $decstr .= $string[$i+$p];
       }
       list($decodedstr, $num) = unescapebycharacter($decstr);
       $result .= urldecode($decodedstr);
       $i += $num ;
   }
   return $result;
}

function unescapebycharacter($str) {
   $char = $str;
   if ($char == '%u20AC') { return array("%80", 5); }
   if ($char == '%u201A') { return array("%82", 5); }
   if ($char == '%u0192') { return array("%83", 5); }
   if ($char == '%u201E') { return array("%84", 5); }
   if ($char == '%u2026') { return array("%85", 5); }
   if ($char == '%u2020') { return array("%86", 5); }
   if ($char == '%u2021') { return array("%87", 5); }
   if ($char == '%u02C6') { return array("%88", 5); }
   if ($char == '%u2030') { return array("%89", 5); }
   if ($char == '%u0160') { return array("%8A", 5); }
   if ($char == '%u2039') { return array("%8B", 5); }
   if ($char == '%u0152') { return array("%8C", 5); }
   if ($char == '%u017D') { return array("%8E", 5); }
   if ($char == '%u2018') { return array("%91", 5); }
   if ($char == '%u2019') { return array("%92", 5); }
   if ($char == '%u201C') { return array("%93", 5); }
   if ($char == '%u201D') { return array("%94", 5); }
   if ($char == '%u2022') { return array("%95", 5); }
   if ($char == '%u2013') { return array("%96", 5); }
   if ($char == '%u2014') { return array("%97", 5); }
   if ($char == '%u02DC') { return array("%98", 5); }
   if ($char == '%u2122') { return array("%99", 5); }
   if ($char == '%u0161') { return array("%9A", 5); }
   if ($char == '%u203A') { return array("%9B", 5); }
   if ($char == '%u0153') { return array("%9C", 5); }
   if ($char == '%u017E') { return array("%9E", 5); }
   if ($char == '%u0178') { return array("%9F", 5); }

   $char = substr($str, 0, 3);
   if ($char == "%20") { return array("+", 2); }

   $char = substr($str, 0, 1);

   if ($char == '*') { return array("%2A", 0); }
   if ($char == '+') { return array("%2B", 0); }
   if ($char == '/') { return array("%2F", 0); }
   if ($char == '@') { return array("%40", 0); }

   if ($char == "%") {
      return array(substr($str, 0, 3), 2);
   } else {
      return array($char, 0);
   }
}
?>