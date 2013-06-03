<?php
/**
 * 中国电信·天翼开放平台 <http://open.189.cn/>
 *
 * 长地址查询API调用DEMO
 * http://open.189.cn/index.php?m=api&c=index&a=show&id=401
 * 
 * @package		shorturl
 * @author		FFCS Chensj<chensj@ffcs.cn>
 * @copyright	Copyright (c) 2012, FFCS, Inc.
 * @license		http://open.189.cn/
 * @link		http://open.189.cn/
 * @since		Version 1.0
 * @filesource
 */
header("Content-type: text/html; charset=utf-8");
	
$shorturl		= '';	//需要查询的短地址
$access_token	= '';	//访问令牌，即通过调用令牌接口获取的访问能力接口的通行证
$app_id			= '';	//应用ID，创建应用时，由EMP分配

//请求地址
$url='http://api.189.cn/EMP/shorturl/short2long';

//设置header信息
$header=array(
	'Content-Type:text/xml;charset=UTF-8',
);

//设置接口请求参数
$postfields = array(
	'access_token'	=> $access_token,
	'app_id'		=> $app_id,
	'shorturl'		=> $shorturl
);
$url .= '?'.http_build_query($postfields);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header );

$response = curl_exec($ch);
curl_close($ch);

if($response){
	$xml	= simplexml_load_string($response);
	print_r($xml);
}else{
	echo '调用失败';
}
?>