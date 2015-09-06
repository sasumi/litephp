<?php
namespace Lite\Api;

/**
 * Created in litephp.
 * File: server.class.php
 * User: sasumi
 * Date: 14-9-3
 * Time: 下午11:10
 */
abstract class Server{
	/**
	 * current api server version
	 * @return mixed
	 */
	abstract public function version();        //版本

	/**
	 * lowest api version supported
	 * @return mixed
	 */
	abstract public function supportVersion(); //最低支持版本

	/**
	 * request parameter format
	 * @return array
	 */
	abstract public function requestFormat();

	/**
	 * api response data format
	 * @return array
	 */
	abstract public function responseFormat();

	/**
	 * execute api server
	 * @param array $param
	 * @return mixed
	 */
	abstract public function execute(array $param = array());

	/**
	 * certify interface
	 * @param $param
	 * @return bool
	 */
	abstract public function certify(array $param);

	/**
	 * check request format
	 * @param $data
	 * @param array $formats
	 * @return bool
	 */
	public function checkRequestFormat($data, $formats = array()){
		return false;
	}

	/**
	 * before api execute
	 * @param $api_instance
	 * @param $param
	 * @return bool
	 */
	public function beforeExecute($api_instance, $param = null){
		return true;
	}

	/**
	 * after api execute
	 * @param $api_instance
	 * @param null $param
	 * @param null $result
	 */
	public function afterExecute($api_instance, $param = null, $result = null){

	}
}
