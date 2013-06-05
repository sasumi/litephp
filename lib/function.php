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
