<?php
/**
 * 字符串相关操作函数
 * User: sasumi
 * Date: 2015/3/30
 * Time: 11:19
 */

namespace Lite\func {
	/**
	 * utf-8中英文截断（两个英文一个数量单位）
	 * @param $string
	 * @param $length
	 * @param $tail
	 * @return string
	 */
	function substr_utf8($string, $length, $tail = '...') {
		$chars = $string;
		$i = 0;
		$n = 0;
		$m = 0;
		do {
			if(preg_match("/[0-9a-zA-Z]/", $chars[$i])) {
				$m++;
			} else {
				$n++;
			}
			//非英文字节,
			$k = $n / 3 + $m / 2;
			$l = $n / 3 + $m;
			$i++;
		} while ($k < $length);
		$str1 = mb_substr($string, 0, $l, 'utf-8');
		if($str1 != $string && $tail) {
			$str1 .= $tail;
		}
		return $str1;
	}

	/**
	 * 输出html变量
	 * @param array|string $str
	 * @param $len
	 * @param null|string $tail
	 * @return string
	 */
	function h($str, $len = null, $tail = '...') {
		if(is_array($str)) {
			$ret = array();
			foreach ($str as $k => $s) {
				$ret[$k] = h($s);
			}
			return $ret;
		}
		if($len) {
			$str = substr_utf8($str, $len, $tail);
		}
		return htmlspecialchars($str);
	}

	/**
	 * 随机字符串
	 * @param int $len
	 * @param string $source
	 * @return string
	 */
	function rand_string($len = 6, $source = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPRSTUVWXYZ23456789') {
		$randCode = '';
		for ($i = 0; $i < $len; $i++) {
			$randCode .= substr($source, mt_rand(0, strlen($source) - 1), 1);
		}
		return $randCode;
	}

	/**
	 * @param $size
	 * @param int $dot
	 * @return string
	 */
	function format_size($size, $dot = 2) {
		$mod = 1024;
		$units = explode(' ', 'B KB MB GB TB PB');
		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}
		return round($size, $dot) . '' . $units[$i];
	}

	/*
	PHP URL encoding/decoding functions for Javascript interaction V3.0
	(C) 2006 www.captain.at - all rights reserved
	License: GPL
	*/
	function encodeURIComponent($string) {
		$result = "";
		for ($i = 0; $i < strlen($string); $i++) {
			$result .= encodeURIComponentByCharacter(urlencode($string[$i]));
		}
		return $result;
	}

	function encodeURIComponentByCharacter($char) {
		$map = array(
			"+" => "%20",
			"%21" => "!",
			"%27" => '"',
			"%28" => "(",
			"%29" => ")",
			"%2A" => "*",
			"%7E" => "~",
			"%80" => "%E2%82%AC",
			"%81" => "%C2%81",
			"%82" => "%E2%80%9A",
			"%83" => "%C6%92",
			"%84" => "%E2%80%9E",
			"%85" => "%E2%80%A6",
			"%86" => "%E2%80%A0",
			"%87" => "%E2%80%A1",
			"%88" => "%CB%86",
			"%89" => "%E2%80%B0",
			"%8A" => "%C5%A0",
			"%8B" => "%E2%80%B9",
			"%8C" => "%C5%92",
			"%8D" => "%C2%8D",
			"%8E" => "%C5%BD",
			"%8F" => "%C2%8F",
			"%90" => "%C2%90",
			"%91" => "%E2%80%98",
			"%92" => "%E2%80%99",
			"%93" => "%E2%80%9C",
			"%94" => "%E2%80%9D",
			"%95" => "%E2%80%A2",
			"%96" => "%E2%80%93",
			"%97" => "%E2%80%94",
			"%98" => "%CB%9C",
			"%99" => "%E2%84%A2",
			"%9A" => "%C5%A1",
			"%9B" => "%E2%80%BA",
			"%9C" => "%C5%93",
			"%9D" => "%C2%9D",
			"%9E" => "%C5%BE",
			"%9F" => "%C5%B8",
			"%A0" => "%C2%A0",
			"%A1" => "%C2%A1",
			"%A2" => "%C2%A2",
			"%A3" => "%C2%A3",
			"%A4" => "%C2%A4",
			"%A5" => "%C2%A5",
			"%A6" => "%C2%A6",
			"%A7" => "%C2%A7",
			"%A8" => "%C2%A8",
			"%A9" => "%C2%A9",
			"%AA" => "%C2%AA",
			"%AB" => "%C2%AB",
			"%AC" => "%C2%AC",
			"%AD" => "%C2%AD",
			"%AE" => "%C2%AE",
			"%AF" => "%C2%AF",
			"%B0" => "%C2%B0",
			"%B1" => "%C2%B1",
			"%B2" => "%C2%B2",
			"%B3" => "%C2%B3",
			"%B4" => "%C2%B4",
			"%B5" => "%C2%B5",
			"%B6" => "%C2%B6",
			"%B7" => "%C2%B7",
			"%B8" => "%C2%B8",
			"%B9" => "%C2%B9",
			"%BA" => "%C2%BA",
			"%BB" => "%C2%BB",
			"%BC" => "%C2%BC",
			"%BD" => "%C2%BD",
			"%BE" => "%C2%BE",
			"%BF" => "%C2%BF",
			"%C0" => "%C3%80",
			"%C1" => "%C3%81",
			"%C2" => "%C3%82",
			"%C3" => "%C3%83",
			"%C4" => "%C3%84",
			"%C5" => "%C3%85",
			"%C6" => "%C3%86",
			"%C7" => "%C3%87",
			"%C8" => "%C3%88",
			"%C9" => "%C3%89",
			"%CA" => "%C3%8A",
			"%CB" => "%C3%8B",
			"%CC" => "%C3%8C",
			"%CD" => "%C3%8D",
			"%CE" => "%C3%8E",
			"%CF" => "%C3%8F",
			"%D0" => "%C3%90",
			"%D1" => "%C3%91",
			"%D2" => "%C3%92",
			"%D3" => "%C3%93",
			"%D4" => "%C3%94",
			"%D5" => "%C3%95",
			"%D6" => "%C3%96",
			"%D7" => "%C3%97",
			"%D8" => "%C3%98",
			"%D9" => "%C3%99",
			"%DA" => "%C3%9A",
			"%DB" => "%C3%9B",
			"%DC" => "%C3%9C",
			"%DD" => "%C3%9D",
			"%DE" => "%C3%9E",
			"%DF" => "%C3%9F",
			"%E0" => "%C3%A0",
			"%E1" => "%C3%A1",
			"%E2" => "%C3%A2",
			"%E3" => "%C3%A3",
			"%E4" => "%C3%A4",
			"%E5" => "%C3%A5",
			"%E6" => "%C3%A6",
			"%E7" => "%C3%A7",
			"%E8" => "%C3%A8",
			"%E9" => "%C3%A9",
			"%EA" => "%C3%AA",
			"%EB" => "%C3%AB",
			"%EC" => "%C3%AC",
			"%ED" => "%C3%AD",
			"%EE" => "%C3%AE",
			"%EF" => "%C3%AF",
			"%F0" => "%C3%B0",
			"%F1" => "%C3%B1",
			"%F2" => "%C3%B2",
			"%F3" => "%C3%B3",
			"%F4" => "%C3%B4",
			"%F5" => "%C3%B5",
			"%F6" => "%C3%B6",
			"%F7" => "%C3%B7",
			"%F8" => "%C3%B8",
			"%F9" => "%C3%B9",
			"%FA" => "%C3%BA",
			"%FB" => "%C3%BB",
			"%FC" => "%C3%BC",
			"%FD" => "%C3%BD",
			"%FE" => "%C3%BE",
			"%FF" => "%C3%BF"
		);
		return $map[$char] ?: $char;
	}

	function decodeURIComponent($string) {
		$result = "";
		for ($i = 0; $i < strlen($string); $i++) {
			$decstr = "";
			for ($p = 0; $p <= 8; $p++) {
				$decstr .= $string[$i + $p];
			}
			list($decodedstr, $num) = decodeURIComponentByCharacter($decstr);
			$result .= urldecode($decodedstr);
			$i += $num;
		}
		return $result;
	}

	function decodeURIComponentByCharacter($str) {
		$char = $str;
		if($char == "%E2%82%AC") {
			return array("%80", 8);
		}
		if($char == "%E2%80%9A") {
			return array("%82", 8);
		}
		if($char == "%E2%80%9E") {
			return array("%84", 8);
		}
		if($char == "%E2%80%A6") {
			return array("%85", 8);
		}
		if($char == "%E2%80%A0") {
			return array("%86", 8);
		}
		if($char == "%E2%80%A1") {
			return array("%87", 8);
		}
		if($char == "%E2%80%B0") {
			return array("%89", 8);
		}
		if($char == "%E2%80%B9") {
			return array("%8B", 8);
		}
		if($char == "%E2%80%98") {
			return array("%91", 8);
		}
		if($char == "%E2%80%99") {
			return array("%92", 8);
		}
		if($char == "%E2%80%9C") {
			return array("%93", 8);
		}
		if($char == "%E2%80%9D") {
			return array("%94", 8);
		}
		if($char == "%E2%80%A2") {
			return array("%95", 8);
		}
		if($char == "%E2%80%93") {
			return array("%96", 8);
		}
		if($char == "%E2%80%94") {
			return array("%97", 8);
		}
		if($char == "%E2%84%A2") {
			return array("%99", 8);
		}
		if($char == "%E2%80%BA") {
			return array("%9B", 8);
		}

		$char = substr($str, 0, 6);

		if($char == "%C2%81") {
			return array("%81", 5);
		}
		if($char == "%C6%92") {
			return array("%83", 5);
		}
		if($char == "%CB%86") {
			return array("%88", 5);
		}
		if($char == "%C5%A0") {
			return array("%8A", 5);
		}
		if($char == "%C5%92") {
			return array("%8C", 5);
		}
		if($char == "%C2%8D") {
			return array("%8D", 5);
		}
		if($char == "%C5%BD") {
			return array("%8E", 5);
		}
		if($char == "%C2%8F") {
			return array("%8F", 5);
		}
		if($char == "%C2%90") {
			return array("%90", 5);
		}
		if($char == "%CB%9C") {
			return array("%98", 5);
		}
		if($char == "%C5%A1") {
			return array("%9A", 5);
		}
		if($char == "%C5%93") {
			return array("%9C", 5);
		}
		if($char == "%C2%9D") {
			return array("%9D", 5);
		}
		if($char == "%C5%BE") {
			return array("%9E", 5);
		}
		if($char == "%C5%B8") {
			return array("%9F", 5);
		}
		if($char == "%C2%A0") {
			return array("%A0", 5);
		}
		if($char == "%C2%A1") {
			return array("%A1", 5);
		}
		if($char == "%C2%A2") {
			return array("%A2", 5);
		}
		if($char == "%C2%A3") {
			return array("%A3", 5);
		}
		if($char == "%C2%A4") {
			return array("%A4", 5);
		}
		if($char == "%C2%A5") {
			return array("%A5", 5);
		}
		if($char == "%C2%A6") {
			return array("%A6", 5);
		}
		if($char == "%C2%A7") {
			return array("%A7", 5);
		}
		if($char == "%C2%A8") {
			return array("%A8", 5);
		}
		if($char == "%C2%A9") {
			return array("%A9", 5);
		}
		if($char == "%C2%AA") {
			return array("%AA", 5);
		}
		if($char == "%C2%AB") {
			return array("%AB", 5);
		}
		if($char == "%C2%AC") {
			return array("%AC", 5);
		}
		if($char == "%C2%AD") {
			return array("%AD", 5);
		}
		if($char == "%C2%AE") {
			return array("%AE", 5);
		}
		if($char == "%C2%AF") {
			return array("%AF", 5);
		}
		if($char == "%C2%B0") {
			return array("%B0", 5);
		}
		if($char == "%C2%B1") {
			return array("%B1", 5);
		}
		if($char == "%C2%B2") {
			return array("%B2", 5);
		}
		if($char == "%C2%B3") {
			return array("%B3", 5);
		}
		if($char == "%C2%B4") {
			return array("%B4", 5);
		}
		if($char == "%C2%B5") {
			return array("%B5", 5);
		}
		if($char == "%C2%B6") {
			return array("%B6", 5);
		}
		if($char == "%C2%B7") {
			return array("%B7", 5);
		}
		if($char == "%C2%B8") {
			return array("%B8", 5);
		}
		if($char == "%C2%B9") {
			return array("%B9", 5);
		}
		if($char == "%C2%BA") {
			return array("%BA", 5);
		}
		if($char == "%C2%BB") {
			return array("%BB", 5);
		}
		if($char == "%C2%BC") {
			return array("%BC", 5);
		}
		if($char == "%C2%BD") {
			return array("%BD", 5);
		}
		if($char == "%C2%BE") {
			return array("%BE", 5);
		}
		if($char == "%C2%BF") {
			return array("%BF", 5);
		}
		if($char == "%C3%80") {
			return array("%C0", 5);
		}
		if($char == "%C3%81") {
			return array("%C1", 5);
		}
		if($char == "%C3%82") {
			return array("%C2", 5);
		}
		if($char == "%C3%83") {
			return array("%C3", 5);
		}
		if($char == "%C3%84") {
			return array("%C4", 5);
		}
		if($char == "%C3%85") {
			return array("%C5", 5);
		}
		if($char == "%C3%86") {
			return array("%C6", 5);
		}
		if($char == "%C3%87") {
			return array("%C7", 5);
		}
		if($char == "%C3%88") {
			return array("%C8", 5);
		}
		if($char == "%C3%89") {
			return array("%C9", 5);
		}
		if($char == "%C3%8A") {
			return array("%CA", 5);
		}
		if($char == "%C3%8B") {
			return array("%CB", 5);
		}
		if($char == "%C3%8C") {
			return array("%CC", 5);
		}
		if($char == "%C3%8D") {
			return array("%CD", 5);
		}
		if($char == "%C3%8E") {
			return array("%CE", 5);
		}
		if($char == "%C3%8F") {
			return array("%CF", 5);
		}
		if($char == "%C3%90") {
			return array("%D0", 5);
		}
		if($char == "%C3%91") {
			return array("%D1", 5);
		}
		if($char == "%C3%92") {
			return array("%D2", 5);
		}
		if($char == "%C3%93") {
			return array("%D3", 5);
		}
		if($char == "%C3%94") {
			return array("%D4", 5);
		}
		if($char == "%C3%95") {
			return array("%D5", 5);
		}
		if($char == "%C3%96") {
			return array("%D6", 5);
		}
		if($char == "%C3%97") {
			return array("%D7", 5);
		}
		if($char == "%C3%98") {
			return array("%D8", 5);
		}
		if($char == "%C3%99") {
			return array("%D9", 5);
		}
		if($char == "%C3%9A") {
			return array("%DA", 5);
		}
		if($char == "%C3%9B") {
			return array("%DB", 5);
		}
		if($char == "%C3%9C") {
			return array("%DC", 5);
		}
		if($char == "%C3%9D") {
			return array("%DD", 5);
		}
		if($char == "%C3%9E") {
			return array("%DE", 5);
		}
		if($char == "%C3%9F") {
			return array("%DF", 5);
		}
		if($char == "%C3%A0") {
			return array("%E0", 5);
		}
		if($char == "%C3%A1") {
			return array("%E1", 5);
		}
		if($char == "%C3%A2") {
			return array("%E2", 5);
		}
		if($char == "%C3%A3") {
			return array("%E3", 5);
		}
		if($char == "%C3%A4") {
			return array("%E4", 5);
		}
		if($char == "%C3%A5") {
			return array("%E5", 5);
		}
		if($char == "%C3%A6") {
			return array("%E6", 5);
		}
		if($char == "%C3%A7") {
			return array("%E7", 5);
		}
		if($char == "%C3%A8") {
			return array("%E8", 5);
		}
		if($char == "%C3%A9") {
			return array("%E9", 5);
		}
		if($char == "%C3%AA") {
			return array("%EA", 5);
		}
		if($char == "%C3%AB") {
			return array("%EB", 5);
		}
		if($char == "%C3%AC") {
			return array("%EC", 5);
		}
		if($char == "%C3%AD") {
			return array("%ED", 5);
		}
		if($char == "%C3%AE") {
			return array("%EE", 5);
		}
		if($char == "%C3%AF") {
			return array("%EF", 5);
		}
		if($char == "%C3%B0") {
			return array("%F0", 5);
		}
		if($char == "%C3%B1") {
			return array("%F1", 5);
		}
		if($char == "%C3%B2") {
			return array("%F2", 5);
		}
		if($char == "%C3%B3") {
			return array("%F3", 5);
		}
		if($char == "%C3%B4") {
			return array("%F4", 5);
		}
		if($char == "%C3%B5") {
			return array("%F5", 5);
		}
		if($char == "%C3%B6") {
			return array("%F6", 5);
		}
		if($char == "%C3%B7") {
			return array("%F7", 5);
		}
		if($char == "%C3%B8") {
			return array("%F8", 5);
		}
		if($char == "%C3%B9") {
			return array("%F9", 5);
		}
		if($char == "%C3%BA") {
			return array("%FA", 5);
		}
		if($char == "%C3%BB") {
			return array("%FB", 5);
		}
		if($char == "%C3%BC") {
			return array("%FC", 5);
		}
		if($char == "%C3%BD") {
			return array("%FD", 5);
		}
		if($char == "%C3%BE") {
			return array("%FE", 5);
		}
		if($char == "%C3%BF") {
			return array("%FF", 5);
		}

		$char = substr($str, 0, 3);
		if($char == "%20") {
			return array("+", 2);
		}

		$char = substr($str, 0, 1);

		if($char == "!") {
			return array("%21", 0);
		}
		if($char == "\"") {
			return array("%27", 0);
		}
		if($char == "(") {
			return array("%28", 0);
		}
		if($char == ")") {
			return array("%29", 0);
		}
		if($char == "*") {
			return array("%2A", 0);
		}
		if($char == "~") {
			return array("%7E", 0);
		}

		if($char == "%") {
			return array(substr($str, 0, 3), 2);
		} else {
			return array($char, 0);
		}
	}

	function encodeURI($string) {
		$result = "";
		for ($i = 0; $i < strlen($string); $i++) {
			$result .= encodeURIByCharacter(urlencode($string[$i]));
		}
		return $result;
	}

	function encodeURIByCharacter($char) {
		if($char == "+") {
			return "%20";
		}
		if($char == "%21") {
			return "!";
		}
		if($char == "%23") {
			return "#";
		}
		if($char == "%24") {
			return "$";
		}
		if($char == "%26") {
			return "&";
		}
		if($char == "%27") {
			return "\"";
		}
		if($char == "%28") {
			return "(";
		}
		if($char == "%29") {
			return ")";
		}
		if($char == "%2A") {
			return "*";
		}
		if($char == "%2B") {
			return "+";
		}
		if($char == "%2C") {
			return ",";
		}
		if($char == "%2F") {
			return "/";
		}
		if($char == "%3A") {
			return ":";
		}
		if($char == "%3B") {
			return ";";
		}
		if($char == "%3D") {
			return "=";
		}
		if($char == "%3F") {
			return "?";
		}
		if($char == "%40") {
			return "@";
		}
		if($char == "%7E") {
			return "~";
		}
		if($char == "%80") {
			return "%E2%82%AC";
		}
		if($char == "%81") {
			return "%C2%81";
		}
		if($char == "%82") {
			return "%E2%80%9A";
		}
		if($char == "%83") {
			return "%C6%92";
		}
		if($char == "%84") {
			return "%E2%80%9E";
		}
		if($char == "%85") {
			return "%E2%80%A6";
		}
		if($char == "%86") {
			return "%E2%80%A0";
		}
		if($char == "%87") {
			return "%E2%80%A1";
		}
		if($char == "%88") {
			return "%CB%86";
		}
		if($char == "%89") {
			return "%E2%80%B0";
		}
		if($char == "%8A") {
			return "%C5%A0";
		}
		if($char == "%8B") {
			return "%E2%80%B9";
		}
		if($char == "%8C") {
			return "%C5%92";
		}
		if($char == "%8D") {
			return "%C2%8D";
		}
		if($char == "%8E") {
			return "%C5%BD";
		}
		if($char == "%8F") {
			return "%C2%8F";
		}
		if($char == "%90") {
			return "%C2%90";
		}
		if($char == "%91") {
			return "%E2%80%98";
		}
		if($char == "%92") {
			return "%E2%80%99";
		}
		if($char == "%93") {
			return "%E2%80%9C";
		}
		if($char == "%94") {
			return "%E2%80%9D";
		}
		if($char == "%95") {
			return "%E2%80%A2";
		}
		if($char == "%96") {
			return "%E2%80%93";
		}
		if($char == "%97") {
			return "%E2%80%94";
		}
		if($char == "%98") {
			return "%CB%9C";
		}
		if($char == "%99") {
			return "%E2%84%A2";
		}
		if($char == "%9A") {
			return "%C5%A1";
		}
		if($char == "%9B") {
			return "%E2%80%BA";
		}
		if($char == "%9C") {
			return "%C5%93";
		}
		if($char == "%9D") {
			return "%C2%9D";
		}
		if($char == "%9E") {
			return "%C5%BE";
		}
		if($char == "%9F") {
			return "%C5%B8";
		}
		if($char == "%A0") {
			return "%C2%A0";
		}
		if($char == "%A1") {
			return "%C2%A1";
		}
		if($char == "%A2") {
			return "%C2%A2";
		}
		if($char == "%A3") {
			return "%C2%A3";
		}
		if($char == "%A4") {
			return "%C2%A4";
		}
		if($char == "%A5") {
			return "%C2%A5";
		}
		if($char == "%A6") {
			return "%C2%A6";
		}
		if($char == "%A7") {
			return "%C2%A7";
		}
		if($char == "%A8") {
			return "%C2%A8";
		}
		if($char == "%A9") {
			return "%C2%A9";
		}
		if($char == "%AA") {
			return "%C2%AA";
		}
		if($char == "%AB") {
			return "%C2%AB";
		}
		if($char == "%AC") {
			return "%C2%AC";
		}
		if($char == "%AD") {
			return "%C2%AD";
		}
		if($char == "%AE") {
			return "%C2%AE";
		}
		if($char == "%AF") {
			return "%C2%AF";
		}
		if($char == "%B0") {
			return "%C2%B0";
		}
		if($char == "%B1") {
			return "%C2%B1";
		}
		if($char == "%B2") {
			return "%C2%B2";
		}
		if($char == "%B3") {
			return "%C2%B3";
		}
		if($char == "%B4") {
			return "%C2%B4";
		}
		if($char == "%B5") {
			return "%C2%B5";
		}
		if($char == "%B6") {
			return "%C2%B6";
		}
		if($char == "%B7") {
			return "%C2%B7";
		}
		if($char == "%B8") {
			return "%C2%B8";
		}
		if($char == "%B9") {
			return "%C2%B9";
		}
		if($char == "%BA") {
			return "%C2%BA";
		}
		if($char == "%BB") {
			return "%C2%BB";
		}
		if($char == "%BC") {
			return "%C2%BC";
		}
		if($char == "%BD") {
			return "%C2%BD";
		}
		if($char == "%BE") {
			return "%C2%BE";
		}
		if($char == "%BF") {
			return "%C2%BF";
		}
		if($char == "%C0") {
			return "%C3%80";
		}
		if($char == "%C1") {
			return "%C3%81";
		}
		if($char == "%C2") {
			return "%C3%82";
		}
		if($char == "%C3") {
			return "%C3%83";
		}
		if($char == "%C4") {
			return "%C3%84";
		}
		if($char == "%C5") {
			return "%C3%85";
		}
		if($char == "%C6") {
			return "%C3%86";
		}
		if($char == "%C7") {
			return "%C3%87";
		}
		if($char == "%C8") {
			return "%C3%88";
		}
		if($char == "%C9") {
			return "%C3%89";
		}
		if($char == "%CA") {
			return "%C3%8A";
		}
		if($char == "%CB") {
			return "%C3%8B";
		}
		if($char == "%CC") {
			return "%C3%8C";
		}
		if($char == "%CD") {
			return "%C3%8D";
		}
		if($char == "%CE") {
			return "%C3%8E";
		}
		if($char == "%CF") {
			return "%C3%8F";
		}
		if($char == "%D0") {
			return "%C3%90";
		}
		if($char == "%D1") {
			return "%C3%91";
		}
		if($char == "%D2") {
			return "%C3%92";
		}
		if($char == "%D3") {
			return "%C3%93";
		}
		if($char == "%D4") {
			return "%C3%94";
		}
		if($char == "%D5") {
			return "%C3%95";
		}
		if($char == "%D6") {
			return "%C3%96";
		}
		if($char == "%D7") {
			return "%C3%97";
		}
		if($char == "%D8") {
			return "%C3%98";
		}
		if($char == "%D9") {
			return "%C3%99";
		}
		if($char == "%DA") {
			return "%C3%9A";
		}
		if($char == "%DB") {
			return "%C3%9B";
		}
		if($char == "%DC") {
			return "%C3%9C";
		}
		if($char == "%DD") {
			return "%C3%9D";
		}
		if($char == "%DE") {
			return "%C3%9E";
		}
		if($char == "%DF") {
			return "%C3%9F";
		}
		if($char == "%E0") {
			return "%C3%A0";
		}
		if($char == "%E1") {
			return "%C3%A1";
		}
		if($char == "%E2") {
			return "%C3%A2";
		}
		if($char == "%E3") {
			return "%C3%A3";
		}
		if($char == "%E4") {
			return "%C3%A4";
		}
		if($char == "%E5") {
			return "%C3%A5";
		}
		if($char == "%E6") {
			return "%C3%A6";
		}
		if($char == "%E7") {
			return "%C3%A7";
		}
		if($char == "%E8") {
			return "%C3%A8";
		}
		if($char == "%E9") {
			return "%C3%A9";
		}
		if($char == "%EA") {
			return "%C3%AA";
		}
		if($char == "%EB") {
			return "%C3%AB";
		}
		if($char == "%EC") {
			return "%C3%AC";
		}
		if($char == "%ED") {
			return "%C3%AD";
		}
		if($char == "%EE") {
			return "%C3%AE";
		}
		if($char == "%EF") {
			return "%C3%AF";
		}
		if($char == "%F0") {
			return "%C3%B0";
		}
		if($char == "%F1") {
			return "%C3%B1";
		}
		if($char == "%F2") {
			return "%C3%B2";
		}
		if($char == "%F3") {
			return "%C3%B3";
		}
		if($char == "%F4") {
			return "%C3%B4";
		}
		if($char == "%F5") {
			return "%C3%B5";
		}
		if($char == "%F6") {
			return "%C3%B6";
		}
		if($char == "%F7") {
			return "%C3%B7";
		}
		if($char == "%F8") {
			return "%C3%B8";
		}
		if($char == "%F9") {
			return "%C3%B9";
		}
		if($char == "%FA") {
			return "%C3%BA";
		}
		if($char == "%FB") {
			return "%C3%BB";
		}
		if($char == "%FC") {
			return "%C3%BC";
		}
		if($char == "%FD") {
			return "%C3%BD";
		}
		if($char == "%FE") {
			return "%C3%BE";
		}
		if($char == "%FF") {
			return "%C3%BF";
		}
		return $char;
	}

	function decodeURI($string) {
		$result = "";
		for ($i = 0; $i < strlen($string); $i++) {
			$decstr = "";
			for ($p = 0; $p <= 8; $p++) {
				$decstr .= $string[$i + $p];
			}
			list($decodedstr, $num) = decodeURIByCharacter($decstr);
			$result .= urldecode($decodedstr);
			$i += $num;
		}
		return $result;
	}

	function decodeURIByCharacter($str) {
		$char = $str;
		if($char == "%E2%82%AC") {
			return array("%80", 8);
		}
		if($char == "%E2%80%9A") {
			return array("%82", 8);
		}
		if($char == "%E2%80%9E") {
			return array("%84", 8);
		}
		if($char == "%E2%80%A6") {
			return array("%85", 8);
		}
		if($char == "%E2%80%A0") {
			return array("%86", 8);
		}
		if($char == "%E2%80%A1") {
			return array("%87", 8);
		}
		if($char == "%E2%80%B0") {
			return array("%89", 8);
		}
		if($char == "%E2%80%B9") {
			return array("%8B", 8);
		}
		if($char == "%E2%80%98") {
			return array("%91", 8);
		}
		if($char == "%E2%80%99") {
			return array("%92", 8);
		}
		if($char == "%E2%80%9C") {
			return array("%93", 8);
		}
		if($char == "%E2%80%9D") {
			return array("%94", 8);
		}
		if($char == "%E2%80%A2") {
			return array("%95", 8);
		}
		if($char == "%E2%80%93") {
			return array("%96", 8);
		}
		if($char == "%E2%80%94") {
			return array("%97", 8);
		}
		if($char == "%E2%84%A2") {
			return array("%99", 8);
		}
		if($char == "%E2%80%BA") {
			return array("%9B", 8);
		}

		$char = substr($str, 0, 6);

		if($char == "%C2%81") {
			return array("%81", 5);
		}
		if($char == "%C6%92") {
			return array("%83", 5);
		}
		if($char == "%CB%86") {
			return array("%88", 5);
		}
		if($char == "%C5%A0") {
			return array("%8A", 5);
		}
		if($char == "%C5%92") {
			return array("%8C", 5);
		}
		if($char == "%C2%8D") {
			return array("%8D", 5);
		}
		if($char == "%C5%BD") {
			return array("%8E", 5);
		}
		if($char == "%C2%8F") {
			return array("%8F", 5);
		}
		if($char == "%C2%90") {
			return array("%90", 5);
		}
		if($char == "%CB%9C") {
			return array("%98", 5);
		}
		if($char == "%C5%A1") {
			return array("%9A", 5);
		}
		if($char == "%C5%93") {
			return array("%9C", 5);
		}
		if($char == "%C2%9D") {
			return array("%9D", 5);
		}
		if($char == "%C5%BE") {
			return array("%9E", 5);
		}
		if($char == "%C5%B8") {
			return array("%9F", 5);
		}
		if($char == "%C2%A0") {
			return array("%A0", 5);
		}
		if($char == "%C2%A1") {
			return array("%A1", 5);
		}
		if($char == "%C2%A2") {
			return array("%A2", 5);
		}
		if($char == "%C2%A3") {
			return array("%A3", 5);
		}
		if($char == "%C2%A4") {
			return array("%A4", 5);
		}
		if($char == "%C2%A5") {
			return array("%A5", 5);
		}
		if($char == "%C2%A6") {
			return array("%A6", 5);
		}
		if($char == "%C2%A7") {
			return array("%A7", 5);
		}
		if($char == "%C2%A8") {
			return array("%A8", 5);
		}
		if($char == "%C2%A9") {
			return array("%A9", 5);
		}
		if($char == "%C2%AA") {
			return array("%AA", 5);
		}
		if($char == "%C2%AB") {
			return array("%AB", 5);
		}
		if($char == "%C2%AC") {
			return array("%AC", 5);
		}
		if($char == "%C2%AD") {
			return array("%AD", 5);
		}
		if($char == "%C2%AE") {
			return array("%AE", 5);
		}
		if($char == "%C2%AF") {
			return array("%AF", 5);
		}
		if($char == "%C2%B0") {
			return array("%B0", 5);
		}
		if($char == "%C2%B1") {
			return array("%B1", 5);
		}
		if($char == "%C2%B2") {
			return array("%B2", 5);
		}
		if($char == "%C2%B3") {
			return array("%B3", 5);
		}
		if($char == "%C2%B4") {
			return array("%B4", 5);
		}
		if($char == "%C2%B5") {
			return array("%B5", 5);
		}
		if($char == "%C2%B6") {
			return array("%B6", 5);
		}
		if($char == "%C2%B7") {
			return array("%B7", 5);
		}
		if($char == "%C2%B8") {
			return array("%B8", 5);
		}
		if($char == "%C2%B9") {
			return array("%B9", 5);
		}
		if($char == "%C2%BA") {
			return array("%BA", 5);
		}
		if($char == "%C2%BB") {
			return array("%BB", 5);
		}
		if($char == "%C2%BC") {
			return array("%BC", 5);
		}
		if($char == "%C2%BD") {
			return array("%BD", 5);
		}
		if($char == "%C2%BE") {
			return array("%BE", 5);
		}
		if($char == "%C2%BF") {
			return array("%BF", 5);
		}
		if($char == "%C3%80") {
			return array("%C0", 5);
		}
		if($char == "%C3%81") {
			return array("%C1", 5);
		}
		if($char == "%C3%82") {
			return array("%C2", 5);
		}
		if($char == "%C3%83") {
			return array("%C3", 5);
		}
		if($char == "%C3%84") {
			return array("%C4", 5);
		}
		if($char == "%C3%85") {
			return array("%C5", 5);
		}
		if($char == "%C3%86") {
			return array("%C6", 5);
		}
		if($char == "%C3%87") {
			return array("%C7", 5);
		}
		if($char == "%C3%88") {
			return array("%C8", 5);
		}
		if($char == "%C3%89") {
			return array("%C9", 5);
		}
		if($char == "%C3%8A") {
			return array("%CA", 5);
		}
		if($char == "%C3%8B") {
			return array("%CB", 5);
		}
		if($char == "%C3%8C") {
			return array("%CC", 5);
		}
		if($char == "%C3%8D") {
			return array("%CD", 5);
		}
		if($char == "%C3%8E") {
			return array("%CE", 5);
		}
		if($char == "%C3%8F") {
			return array("%CF", 5);
		}
		if($char == "%C3%90") {
			return array("%D0", 5);
		}
		if($char == "%C3%91") {
			return array("%D1", 5);
		}
		if($char == "%C3%92") {
			return array("%D2", 5);
		}
		if($char == "%C3%93") {
			return array("%D3", 5);
		}
		if($char == "%C3%94") {
			return array("%D4", 5);
		}
		if($char == "%C3%95") {
			return array("%D5", 5);
		}
		if($char == "%C3%96") {
			return array("%D6", 5);
		}
		if($char == "%C3%97") {
			return array("%D7", 5);
		}
		if($char == "%C3%98") {
			return array("%D8", 5);
		}
		if($char == "%C3%99") {
			return array("%D9", 5);
		}
		if($char == "%C3%9A") {
			return array("%DA", 5);
		}
		if($char == "%C3%9B") {
			return array("%DB", 5);
		}
		if($char == "%C3%9C") {
			return array("%DC", 5);
		}
		if($char == "%C3%9D") {
			return array("%DD", 5);
		}
		if($char == "%C3%9E") {
			return array("%DE", 5);
		}
		if($char == "%C3%9F") {
			return array("%DF", 5);
		}
		if($char == "%C3%A0") {
			return array("%E0", 5);
		}
		if($char == "%C3%A1") {
			return array("%E1", 5);
		}
		if($char == "%C3%A2") {
			return array("%E2", 5);
		}
		if($char == "%C3%A3") {
			return array("%E3", 5);
		}
		if($char == "%C3%A4") {
			return array("%E4", 5);
		}
		if($char == "%C3%A5") {
			return array("%E5", 5);
		}
		if($char == "%C3%A6") {
			return array("%E6", 5);
		}
		if($char == "%C3%A7") {
			return array("%E7", 5);
		}
		if($char == "%C3%A8") {
			return array("%E8", 5);
		}
		if($char == "%C3%A9") {
			return array("%E9", 5);
		}
		if($char == "%C3%AA") {
			return array("%EA", 5);
		}
		if($char == "%C3%AB") {
			return array("%EB", 5);
		}
		if($char == "%C3%AC") {
			return array("%EC", 5);
		}
		if($char == "%C3%AD") {
			return array("%ED", 5);
		}
		if($char == "%C3%AE") {
			return array("%EE", 5);
		}
		if($char == "%C3%AF") {
			return array("%EF", 5);
		}
		if($char == "%C3%B0") {
			return array("%F0", 5);
		}
		if($char == "%C3%B1") {
			return array("%F1", 5);
		}
		if($char == "%C3%B2") {
			return array("%F2", 5);
		}
		if($char == "%C3%B3") {
			return array("%F3", 5);
		}
		if($char == "%C3%B4") {
			return array("%F4", 5);
		}
		if($char == "%C3%B5") {
			return array("%F5", 5);
		}
		if($char == "%C3%B6") {
			return array("%F6", 5);
		}
		if($char == "%C3%B7") {
			return array("%F7", 5);
		}
		if($char == "%C3%B8") {
			return array("%F8", 5);
		}
		if($char == "%C3%B9") {
			return array("%F9", 5);
		}
		if($char == "%C3%BA") {
			return array("%FA", 5);
		}
		if($char == "%C3%BB") {
			return array("%FB", 5);
		}
		if($char == "%C3%BC") {
			return array("%FC", 5);
		}
		if($char == "%C3%BD") {
			return array("%FD", 5);
		}
		if($char == "%C3%BE") {
			return array("%FE", 5);
		}
		if($char == "%C3%BF") {
			return array("%FF", 5);
		}

		$char = substr($str, 0, 3);
		if($char == "%20") {
			return array("+", 2);
		}

		$char = substr($str, 0, 1);

		if($char == "!") {
			return array("%21", 0);
		}
		if($char == "#") {
			return array("%23", 0);
		}
		if($char == "$") {
			return array("%24", 0);
		}
		if($char == "&") {
			return array("%26", 0);
		}
		if($char == "\"") {
			return array("%27", 0);
		}
		if($char == "(") {
			return array("%28", 0);
		}
		if($char == ")") {
			return array("%29", 0);
		}
		if($char == "*") {
			return array("%2A", 0);
		}
		if($char == "+") {
			return array("%2B", 0);
		}
		if($char == ",") {
			return array("%2C", 0);
		}
		if($char == "/") {
			return array("%2F", 0);
		}
		if($char == ":") {
			return array("%3A", 0);
		}
		if($char == ";") {
			return array("%3B", 0);
		}
		if($char == "=") {
			return array("%3D", 0);
		}
		if($char == "?") {
			return array("%3F", 0);
		}
		if($char == "@") {
			return array("%40", 0);
		}
		if($char == "~") {
			return array("%7E", 0);
		}

		if($char == "%") {
			return array(substr($str, 0, 3), 2);
		} else {
			return array($char, 0);
		}
	}

	function escape($string) {
		$result = "";
		for ($i = 0; $i < strlen($string); $i++) {
			$result .= escapeByCharacter(urlencode($string[$i]));
		}
		return $result;
	}

	function escapeByCharacter($char) {
		if($char == '+') {
			return '%20';
		}
		if($char == '%2A') {
			return '*';
		}
		if($char == '%2B') {
			return '+';
		}
		if($char == '%2F') {
			return '/';
		}
		if($char == '%40') {
			return '@';
		}
		if($char == '%80') {
			return '%u20AC';
		}
		if($char == '%82') {
			return '%u201A';
		}
		if($char == '%83') {
			return '%u0192';
		}
		if($char == '%84') {
			return '%u201E';
		}
		if($char == '%85') {
			return '%u2026';
		}
		if($char == '%86') {
			return '%u2020';
		}
		if($char == '%87') {
			return '%u2021';
		}
		if($char == '%88') {
			return '%u02C6';
		}
		if($char == '%89') {
			return '%u2030';
		}
		if($char == '%8A') {
			return '%u0160';
		}
		if($char == '%8B') {
			return '%u2039';
		}
		if($char == '%8C') {
			return '%u0152';
		}
		if($char == '%8E') {
			return '%u017D';
		}
		if($char == '%91') {
			return '%u2018';
		}
		if($char == '%92') {
			return '%u2019';
		}
		if($char == '%93') {
			return '%u201C';
		}
		if($char == '%94') {
			return '%u201D';
		}
		if($char == '%95') {
			return '%u2022';
		}
		if($char == '%96') {
			return '%u2013';
		}
		if($char == '%97') {
			return '%u2014';
		}
		if($char == '%98') {
			return '%u02DC';
		}
		if($char == '%99') {
			return '%u2122';
		}
		if($char == '%9A') {
			return '%u0161';
		}
		if($char == '%9B') {
			return '%u203A';
		}
		if($char == '%9C') {
			return '%u0153';
		}
		if($char == '%9E') {
			return '%u017E';
		}
		if($char == '%9F') {
			return '%u0178';
		}
		return $char;
	}

	function unescape($string) {
		$result = "";
		for ($i = 0; $i < strlen($string); $i++) {
			$decstr = "";
			for ($p = 0; $p <= 5; $p++) {
				$decstr .= $string[$i + $p];
			}
			list($decodedstr, $num) = unEscapeByCharacter($decstr);
			$result .= urldecode($decodedstr);
			$i += $num;
		}
		return $result;
	}

	function unEscapeByCharacter($str) {
		$char = $str;
		if($char == '%u20AC') {
			return array("%80", 5);
		}
		if($char == '%u201A') {
			return array("%82", 5);
		}
		if($char == '%u0192') {
			return array("%83", 5);
		}
		if($char == '%u201E') {
			return array("%84", 5);
		}
		if($char == '%u2026') {
			return array("%85", 5);
		}
		if($char == '%u2020') {
			return array("%86", 5);
		}
		if($char == '%u2021') {
			return array("%87", 5);
		}
		if($char == '%u02C6') {
			return array("%88", 5);
		}
		if($char == '%u2030') {
			return array("%89", 5);
		}
		if($char == '%u0160') {
			return array("%8A", 5);
		}
		if($char == '%u2039') {
			return array("%8B", 5);
		}
		if($char == '%u0152') {
			return array("%8C", 5);
		}
		if($char == '%u017D') {
			return array("%8E", 5);
		}
		if($char == '%u2018') {
			return array("%91", 5);
		}
		if($char == '%u2019') {
			return array("%92", 5);
		}
		if($char == '%u201C') {
			return array("%93", 5);
		}
		if($char == '%u201D') {
			return array("%94", 5);
		}
		if($char == '%u2022') {
			return array("%95", 5);
		}
		if($char == '%u2013') {
			return array("%96", 5);
		}
		if($char == '%u2014') {
			return array("%97", 5);
		}
		if($char == '%u02DC') {
			return array("%98", 5);
		}
		if($char == '%u2122') {
			return array("%99", 5);
		}
		if($char == '%u0161') {
			return array("%9A", 5);
		}
		if($char == '%u203A') {
			return array("%9B", 5);
		}
		if($char == '%u0153') {
			return array("%9C", 5);
		}
		if($char == '%u017E') {
			return array("%9E", 5);
		}
		if($char == '%u0178') {
			return array("%9F", 5);
		}

		$char = substr($str, 0, 3);
		if($char == "%20") {
			return array("+", 2);
		}

		$char = substr($str, 0, 1);

		if($char == '*') {
			return array("%2A", 0);
		}
		if($char == '+') {
			return array("%2B", 0);
		}
		if($char == '/') {
			return array("%2F", 0);
		}
		if($char == '@') {
			return array("%40", 0);
		}

		if($char == "%") {
			return array(substr($str, 0, 3), 2);
		} else {
			return array($char, 0);
		}
	}
}