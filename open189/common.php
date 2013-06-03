<?php
/**
 * http请求
 * @param string $url 请求URL
 * @param string $method 请求方法：GET, POST
 * @param array $data 数据
 * @param array $option
 * @return string
 **/
function http($url, $method, array $data=array(), $option=array()) {
	$option = array_merge(array(
		'useragent' => 'MSIE',				//模拟代理
		'connecttimeout' => 10,			//请求超时时间
		'timeout' => 10,				//超时时间
		'ssl_verifypeer' => false,		//是否进行ssl验证
		'headers' => null				//头部控制
	), $option);
	
	$ci = curl_init();
	curl_setopt($ci, CURLOPT_USERAGENT, $option['useragent']);
	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $option['connecttimeout']);
	curl_setopt($ci, CURLOPT_TIMEOUT, $option['timeout']);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $option['ssl_verifypeer']);
	curl_setopt($ci, CURLOPT_HEADER, FALSE);
	
	switch ($method) {
		case 'POST':
			curl_setopt($ci, CURLOPT_POST, true);
			if (!empty($data)) {
				$str = http_build_query($data);
				curl_setopt($ci, CURLOPT_POSTFIELDS, $str);
			}
			break;
        case 'DELETE':
			curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
			if (!empty($data)) {
				$url = "{$url}?{$data}";
			}
		
		case 'GET':
			$url = add_to_url($url, $data);
			break;
	}
	
	if($option['headers']){
		$tmp = array();
		foreach($option['headers'] as $k=>$v){
			array_push($tmp, $k.':'.$v);
		}
		curl_setopt($ci, CURLOPT_HTTPHEADER, $tmp);
	}
	curl_setopt($ci, CURLINFO_HEADER_OUT, true);
	curl_setopt($ci, CURLOPT_URL, $url);
	
	$response = curl_exec($ci);
	curl_close ($ci);
	return $response;
}

/**
 * 参数检查
 * @param array $params 参数
 * @param string $keys 检查的keys，以逗号分隔
**/
function open189_params_check($params, $keys){
	$keys = explode(',', $keys);
	foreach($keys as $key){
		if(!$params[$key]){
			throw new Exception('PARAMS NEED: '.$key);
			break;
		}
	}
}

/**
 * debug
 **/
function dump(){
	$args = func_get_args();
	echo '<pre>';
	call_user_func_array('var_dump', $args);
	if(array_pop($args) == 1){
		die();
	}
}

/**
 * current page url
 * @return string
 **/
function this_url(){
	$host = $_SERVER['HTTP_HOST'];
	$protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') ? 'https://' : 'http://';
	$port = $_SERVER['SERVER_PORT'] == 80 ? null : $_SERVER['SERVER_PORT'];
	$uri = $_SERVER['REQUEST_URI'];
	return $protocol.$host.($port ? ':'.$port : '').$uri;
}

/**
 * remove data from url
 * @param stirng $url
 * @param string $key
 * @return string
 **/
function remove_from_url($url, $key){
	$reg = '/(\?|&)'.$key.'=([^?&]*)(&|$)/';
	return preg_replace($reg, '$1', $url);
}

/**
 * 添加到url
 * @param string $url
 * @param mix $data
 * @return string
 **/
function add_to_url($url, $data){
	return $url.(strpos($url, '?') === false ? '?' : '&').http_build_query($data);
}