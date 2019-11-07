<?php
/**
 * 时间相关操作函数
 * User: sasumi
 * Date: 17/7/17
 * Time: 22:13
 */
namespace Lite\func;

use DateTime;
use Lite\Exception\Exception;

const DATETIME_FMT = 'Y-m-d H:i:s';
const ONE_DAY = 86400;
const ONE_HOUR = 3600;
const ONE_WEEK = 604800;

/**
 * 获取制定开始时间、结束时间的上中下旬分段数组
 * @param $start_str
 * @param $end_str
 * @return array [[period_th, start_time, end_time],...]
 * @throws \Lite\Exception\Exception
 */
function time_get_month_period_ranges($start_str, $end_str){
	$start = strtotime($start_str);
	$end = strtotime($end_str);
	$ranges = [];
	$period_map = ['-01 00:00:00', '-11 00:00:00', '-21 00:00:00'];

	if($start > $end){
		throw new Exception('time range parameter error: '.$start_str.'-'.$end_str);
	}

	$start_d = date('d', $start);
	$end_d = date('d', $end);
	$start_period = $start_d > 20 ? 2 : ($start_d > 10 ? 1 : 0);
	$end_period = $end_d > 20 ? 2 : ($end_d > 10 ? 1 : 0);

	//in same month
	if(date('Y-m', $start) == date('Y-m', $end)){
		$ym_str = date('Y-m', $end);
		for($i = $start_period; $i<=$end_period; $i++){
			$s = max(strtotime($ym_str . $period_map[$i]), $start);
			$e = $i == $end_period ? $end : min(strtotime($ym_str . $period_map[$i+1])-1, $end);
			$ranges[] = [$i, date(DATETIME_FMT, $s), date(DATETIME_FMT, $e)];
		}
		return $ranges;
	}

	//in next month
	else if(date('Y-m', strtotime('+1 month', strtotime(date('Y-m-01',$start)))) == date('Y-m', $end)){
		$st_ym_str = date('Y-m', $start);
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-d H:i:s',$start), $st_ym_str.'-'.date("t",strtotime($st_ym_str.'-01')).' 23:59:59'));
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-01 00:00:00', $end), date('Y-m-d H:i:s',$end)));
	}

	//sep by months
	else {
		//start of first month
		$st_ym_str = date('Y-m', $start);
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-d H:i:s',$start), $st_ym_str.'-'.date("t",strtotime($st_ym_str.'-01')).' 23:59:59'));

		//middle months
		$s = new DateTime();
		$s->setTimestamp($start);
		$e = new DateTime();
		$e->setTimestamp($end);
		$months = $s->diff($e)->m + $s->diff($e)->y*12;
		for($m = 1; $m<$months; $m++){
			$tmp = strtotime("+$m month", strtotime(date('Y-m-01', $start)));
			$month_st = date('Y-m-01 00:00:00', $tmp);
			$month_ed = date('Y-m-'.date('t', $tmp).' 23:59:59', $tmp);

			if(strtotime($month_ed) > $end){
				break;
			}
			$ranges = array_merge($ranges, time_get_month_period_ranges($month_st, $month_ed));
		}

		//end of last month
		$ranges = array_merge($ranges, time_get_month_period_ranges(date('Y-m-01 00:00:00', $end), date('Y-m-d H:i:s',$end)));
	}
	return $ranges;
}

/**
 * 过滤时间范围，补充上时分秒
 * @param array $ranges 时间范围（开始，结束）
 * @param string|int $default_start 默认开始时间
 * @param string|int $default_end 默认结束时间
 * @param bool $as_datetime 是否以日期+时间形式返回
 * @return array [开始时间,结束时间]
 */
function filter_date_range($ranges, $default_start = null, $default_end = null, $as_datetime = false){
	list($start, $end) = $ranges ?: [];
	if(!isset($start) && $default_start){
		$start = is_numeric($default_start) ? date('Y-m-d', $default_start) : $default_start;
	}
	if($as_datetime && $start){
		$start .= ' 00:00:00';
	}
	if(!isset($end) && $default_end){
		$end = is_numeric($default_end) ? date('Y-m-d', $default_end) : $default_end;
	}
	if($as_datetime && $end){
		$end .= ' 23:59:59';
	}
	return [$start, $end];
}

/**
 * Calculate a precise time difference.
 * @param string $start result of microtime()
 * @param string $end result of microtime(); if NULL/FALSE/0/'' then it's now
 * @return float difference in seconds, calculated with minimum precision loss
 */
function microtime_diff($start, $end = null){
	if (!$end) {
		$end = microtime();
	}
	list($start_usec, $start_sec) = explode(" ", $start);
	list($end_usec, $end_sec) = explode(" ", $end);
	$diff_sec = intval($end_sec) - intval($start_sec);
	$diff_usec = floatval($end_usec) - floatval($start_usec);
	return floatval($diff_sec) + $diff_usec;
}

/**
 * convert microtime format to date
 * @param $microtime
 * @param string $format
 * @return false|string
 */
function microtime_to_date($microtime, $format='Y-m-d H:i:s'){
	return date($format, explode(' ', $microtime)[1]);
}

/**
 * check time string is empty (cmp to 1970)
 * @param $time_str
 * @return bool
 */
function time_empty($time_str){
	return !$time_str || date('Ymd', strtotime($time_str)) === '19700101';
}

/**
 * 格式化友好显示时间
 * @param $timestamp
 * @param bool $as_html 是否使用span包裹
 * @return string
 */
function pretty_time($timestamp, $as_html = false){
	$str = '';
	$offset = time()-$timestamp;
	$before = $offset>0;
	$offset = abs($offset);
	$unit_cal = array(
		'年'  => 31104000,
		'个月' => 2592000,
		'天'  => 86400,
		'小时' => 3600,
		'分钟' => 60,
	);
	if($offset>30 && $offset<60){
		$str = $before ? '刚才' : '等下';
	} else if($offset<=30){
		$str = $before ? '刚刚' : '马上';
	} else{
		$us = array();
		foreach($unit_cal as $u){
			$tmp = $offset>=$u ? floor($offset/$u) : 0;
			$offset -= $tmp ? $u : 0;
			$us[] = $tmp;
		}
		foreach($us as $k => $u){
			if($u){
				$str = $u.array_keys($unit_cal)[$k].($before ? '前' : '后');
				break;
			}
		}
	}
	return $as_html ? '<span title="'.date('Y-m-d H:i:s', $timestamp).'">'.$str.'</span>' : $str;
}

/**
 * 补充日期范围，填充中间空白天数
 * @param $start
 * @param $end
 * @param string $format
 * @return array
 */
function make_date_ranges($start, $end = '', $format = 'Y-m-d'){
	$end = $end ?: time();
	$start = is_string($start) ? strtotime($start) : $start;
	$end = is_string($end) ? strtotime($end) : $end;

	$ret = [];
	for($i = $start; $i <= $end; $i += ONE_DAY){
		$ret[] = date($format, $i);
	}
	return $ret;
}

/**
 * 获取从$start开始经过$days个工作日后的日期
 * 实际日期 = 工作日数 + 周末天数 -1
 * @param string $start 开始日期
 * @param int $days 工作日天数 正数为往后，负数为往前
 * @return string Y-m-d 日期
 */
function calc_actual_date($start, $days){
	$t = date('N', strtotime($start));
	if($days == 0){
		return $start;
	}
	if($days > 0){
		//正推
		$thisWeekWork = (6-$t)>0 ? (6-$t) : 0;//本周的工作日
		$weeks = ($days-$thisWeekWork)%5 ? floor(($days-$thisWeekWork)/5)*2 : ((($days-$thisWeekWork)/5)-1)*2;//从下周一开始算的总周末数
		$diff_days = $weeks+$days+1;//周末数+工作日+加上本周末-1
		$expect = date("Y-m-d", strtotime($start)+$diff_days*24*3600);
	} else{
		$days = abs($days);
		//逆推
		$thisWeekWork = $t>5 ? 5 : $t;//本周的工作日
		$thisWeekends = $t>5 ? ($t-5) : 0;//本周周末天数
		$weeks = ceil(($days-$thisWeekWork)/5)*2;//剩下的周末数
		$diff_days = $thisWeekends+$weeks+$days-1;//本周周末天数+剩余周末天数+工作日-1
		$expect = date("Y-m-d", strtotime($start)-$diff_days*24*3600);
	}
	return $expect;
}

/**
 * 计算时间差到文本
 * @param string $start
 * @param string $end
 * @return string
 */
function time_range($start, $end){
	return time_range_v(strtotime($end)-strtotime($start));
}

/**
 * 转化时间长度到字符串
 * <pre>
 * $str = time_range_v(3601);
 * //1H 0M 1S
 * </pre>
 * @param int $seconds
 * @return string
 */
function time_range_v($seconds){
	$d = floor($seconds/86400);
	$seconds = $seconds-$d*86400;
	$h = floor($seconds/3600);
	$seconds = $seconds-$h*3600;
	$m = floor($seconds/60);
	$seconds = $seconds-$m*60;
	$s = (int)$seconds;
	$str = '';
	$str .= $d ? $d.'d' : '';
	$str .= $h ? $h.'h' : ($str ? '0h' : '');
	$str .= $m ? $m.'m' : ($str ? '0m' : '');
	$str .= $s ? $s.'s' : ($str ? '0s' : '');
	$str = $str ?: '0';
	return $str;
}

function mk_utc($timestamp = null, $short = false){
	$timestamp = $timestamp ?: time();
	if(!$short){
		$str = date('Y-m-d H:i:s', $timestamp);
		$str = str_replace(' ', 'T', $str).'.000Z';
	} else{
		$str = date('Y-m-d H:i', $timestamp);
		$str = str_replace(' ', 'T', $str);
	}
	return $str;
}