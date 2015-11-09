<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/10/16
 * Time: 20:35
 */
namespace Lite\Component;

abstract class ParallelCurl {
	public static $SINGLE_DEFAULT_TIMEOUT = 10;
	public static $DEFAULT_USER_AGENT = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';
	private static $requests = array();

	/**
	 * add request to queue
	 * @param $param
	 * @param $callback
	 */
	public static function addRequest($param, $callback){
		self::$requests[] = array_merge(array(
			'url' => '',
			'data' => null,
			'method' => 'get',
			'max_redirect' => 2, //最大302次数
			'timeout' => self::$SINGLE_DEFAULT_TIMEOUT,
			'user_agent' => self::$DEFAULT_USER_AGENT,
			'callback' => $callback,
		), $param);
	}

	/**
	 * send all request
	 * @return mixed
	 */
	public static function send(){
		$mh = curl_multi_init();
		$ch_list = array();

		foreach(self::$requests as $req){
			$ch = curl_init();
			if(strtolower($req['method']) == 'post'){
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req['data']);
			} else {
				if($req['data']){
					$req['url'] = $req['url'].(strpos($req['url'], '?') ? '&':'?').http_build_query($req['data']);
				}
			}
			curl_setopt($ch, CURLOPT_URL, $req['url']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, $req['timeout']);
			curl_setopt($ch, CURLOPT_USERAGENT, $req['user_agent']);
			if($req['max_redirect']){
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_MAXREDIRS, $req['max_redirect']);
			}
			curl_multi_add_handle($mh, $ch);
			$ch_list[] = $ch;
		}

		$running=null;
		do {
			usleep(10);
			curl_multi_exec($mh,$running);
		} while ($running > 0);

		//remove curl handle
		foreach($ch_list as $k=>$ch){
			$con = curl_multi_getcontent($ch);
			curl_multi_remove_handle($mh, $ch);
			call_user_func(self::$requests[$k]['callback'], $con, self::$requests[$k]);
		}
		curl_multi_close($mh);
	}
}