<?php
namespace Lite\Api;

class ApiFactoryPost extends ApiFactory{
	/**
	 * 解析数据
	 * @return mixed
	 */
	protected function resolveData(){
		return $_POST;
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
