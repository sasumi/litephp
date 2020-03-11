<?php
namespace Lite\Core;
use Exception;

/**
 * 框架标准返回结果封装，所有Controller的执行结果返回建议使用。
 * 尽量避免在controller中echo、die出字符串
 * User: sasumi
 * Date: 2014/11/18
 * Time: 9:49
 */
class Result implements \JsonSerializable {
	private $code;
	private $message;
	private $data;
	private $jump_url;
	
	/**
	 * 对象构造函数
	 * @param string $message
	 * @param int $code
	 * @param null $data
	 * @param string $jump_url
	 */
	public function __construct($message='', $code=1, $data=null, $jump_url=''){
		$this->data = $data;
		$this->code = is_bool($code) ? ($code ? 0 : 1) : (int)$code;
		$this->message = $message;
		$this->jump_url = $jump_url;
	}

	/**
	 * 快速转换
	 * @param $mix
	 * @param int $code
	 * @param null $data
	 * @param string $jump_url
	 * @return self
	 */
	public static function convert($mix, $code=1, $data=null, $jump_url=''){
		if($mix instanceof Result){
			return $mix;
		}
		else if(is_array($mix)){
			$r = new Result();
			$r->setData($mix);
			return $r;
		}
		else {
			return new self($mix, $code, $data, $jump_url);
		}
	}

	/**
	 * 获取编码
	 * @return int
	 */
	public function getCode(){
		return $this->code;
	}

	/**
	 * 检测是否成功
	 * @return boolean
	 */
	public function isSuccess(){
		return 0 === $this->code;
	}

	/**
	 * 获取消息
	 * @return string
	 */
	public function getMessage(){
		return $this->message;
	}

	/**
	 * 设置消息
	 * @param string $msg
	 */
	public function setMessage($msg){
		$this->message = $msg;
	}

	/**
	 * 设置数据项
	 * @param string $key
	 * @param $val
	 */
	public function setItem($key, $val){
		$this->data[$key] = $val;
	}

	/**
	 * 获取数据
	 * @return mixed
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * 设置数据
	 * @param $data
	 */
	public function setData($data){
		$this->data = $data;
	}

	/**
	 * 添加数据
	 * @param $data
	 * @return string
	 */
	public function addData($data){
		$this->data = array_merge($this->data, $data);
	}

	/**
	 * 获取跳转路径
	 * @return string
	 */
	public function getJumpUrl(){
		return $this->jump_url;
	}

	/**
	 * 设置跳转路径
	 * @param $url
	 */
	public function setJumpUrl($url){
		$this->jump_url = $url;
	}

	/**
	 * 转化当前对象为数组
	 * @return array
	 */
	public function getObject(){
		return array(
			'code' => $this->code,
			'message' => $this->message,
			'data' => $this->data,
			'jump_url' => $this->jump_url
		);
	}

	/**
	 * 转换当前对象为字符串
	 * @return string
	 */
	public function __toString(){
		return $this->message.'['.$this->code.']';
	}

	/**
	 * 转换当前对象为JSON
	 * @return string
	 */
	public function getJSON(){
		$data = $this->getObject();
		if($this->getData()){
			$p_data = json_decode(json_encode($this->getData()));
			$data['data'] = $p_data;
		}
		return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 转换当前对象为JSONP
	 * @param string $callback
	 * @return string
	 */
	public function getJSONP($callback='_callback'){
		$data = $this->getObject();
		return $callback.'('.json_encode($data).');';
	}

	/**
	 * 转换当前对象为iframe相应格式
	 * @param string $callback
	 * @return string
	 */
	public function getIframeResponse($callback = '_callback'){
		$data = $this->getObject();

		//avoid Exception no parse in json_encode
		//避免Exception对象在json_encode中失败
		if($data['data'] instanceof Exception){
			$data['data'] .= '';
		}

		$data_str = json_encode($data, JSON_UNESCAPED_UNICODE);
		$html = <<<EOT
			<!doctype html><html lang="en"><head><meta charset="UTF-8"/><title></title>
			<script>
				var tryFrame = function(){
					var frame = window.frameElement;
					if(!frame){
						var try_domains = function(domain){
							console.log('Trying domain:',domain);
							try {
								document.domain = domain;
								if(!window.frameElement){
									throw("window frameElement access deny.");
								}
								return window.frameElement;
							} catch (ex){
								console.warn(ex);
								var tmp = domain.split('.');
								if(tmp.length > 1){
									return try_domains(tmp.slice(1).join('.'));
								}
								throw("window frameElement try fail"+tmp.join('.'));
							}
						};
						frame = try_domains(location.host);
					}
					return frame;
				};
				var frame = tryFrame();
			</script>
			<script>frame.$callback($data_str);</script>
			</head><body></body></html>;
EOT;
		return $html;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize(){
		return $this->getObject();
	}
}
