<?php
namespace Lite\Api;
use Lite\Core\Router;

/**
 * Class ApiFactoryJson
 * Json Api调用方式，请求路径 /api/user/info，请求参数以JSON格式放置在HTTP BODY，响应结果以JSON方式返回。
 * @package Lite\Api
 */
class ApiFactoryJson extends ApiFactory{
	/**
	 * 解析数据
	 * @return mixed
	 */
	protected function resolveData(){
		$raw = Router::readInputData();
		return $raw ?: json_decode($raw, true);
	}
	
	/**
	 * 异常处理
	 * @param \Exception $ex
	 * @return bool|string
	 */
	protected function onException(\Exception $ex){
		return json_encode(['message' => $ex->getMessage()]);
	}
	
	/**
	 * 格式化调用结果数据，返回到客户端
	 * @param mixed $response
	 * @return mixed
	 */
	protected function formatResponse($response){
		return json_encode($response);
	}
}