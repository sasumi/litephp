<?php
namespace Lite\Component;

/**
 * Http协议辅助类
 * User: sasumi
 * Date: 2016/6/21
 * Time: 9:10
 */
abstract class Http{
	//HTTP状态码类型归类
	const TYPE_INFO = 'information'; //信息(1xx系列)
	const TYPE_SUCCESS = 'success'; //成功(2xx系列)
	const TYPE_REDIRECT = 'redirect'; //跳转(3xx系列)
	const TYPE_CLIENT_ERROR = 'client error'; //客户端错误(4xx系列)
	const TYPE_SERVER_ERROR = 'server error'; //服务端错误(5xx系列)

	//information
	const STATUS_CONTINUE = 100;
	const STATUS_SWITCHING_PROTOCOLS = 101;

	//success
	const STATUS_OK = 200;
	const STATUS_CREATED = 201;
	const STATUS_ACCEPTED = 202;
	const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
	const STATUS_NO_CONTENT = 204;
	const STATUS_RESET_CONTENT = 205;
	const STATUS_PARTIAL_CONTENT = 206;

	//redirect
	const STATUS_MULTIPLE_CHOICES = 300;
	const STATUS_MOVED_PERMANENTLY = 301;
	const STATUS_MOVED_TEMPORARILY = 302;
	const STATUS_SEE_OTHER = 303;
	const STATUS_NOT_MODIFIED = 304;
	const STATUS_USE_PROXY = 305;
	const STATUS_TEMPORARY_REDIRECT = 307;

	//client error
	const STATUS_BAD_REQUEST = 400;
	const STATUS_UNAUTHORIZED = 401;
	const STATUS_PAYMENT_REQUIRED = 402;
	const STATUS_FORBIDDEN = 403;
	const STATUS_NOT_FOUND = 404;
	const STATUS_METHOD_NOT_ALLOWED = 405;
	const STATUS_NOT_ACCEPTABLE = 406;
	const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
	const STATUS_REQUEST_TIMEOUT = 408;
	const STATUS_CONFLICT = 409;
	const STATUS_GONE = 410;
	const STATUS_LENGTH_REQUIRED = 411;
	const STATUS_PRECONDITION_FAILED = 412;
	const STATUS_REQUEST_ENTITY_TOO_LARGE = 413;
	const STATUS_REQUEST_URI_TOO_LONG = 414;
	const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
	const STATUS_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const STATUS_EXPECTATION_FAILED = 417;

	//server error
	const STATUS_INTERNAL_SERVER_ERROR = 500;
	const STATUS_NOT_IMPLEMENTED = 501;
	const STATUS_BAD_GATEWAY = 502;
	const STATUS_SERVICE_UNAVAILABLE = 503;
	const STATUS_GATEWAY_TIMEOUT = 504;
	const STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;
	const STATUS_BANDWIDTH_LIMIT_EXCEEDED = 509;

	//基本状态码文字说明映射
	const STATUS_MESSAGE = array(
		self::STATUS_CONTINUE                        => 'Continue',
		self::STATUS_SWITCHING_PROTOCOLS             => 'Switching Protocols',
		self::STATUS_OK                              => 'OK',
		self::STATUS_CREATED                         => 'Created',
		self::STATUS_ACCEPTED                        => 'Accepted',
		self::STATUS_NON_AUTHORITATIVE_INFORMATION   => 'Non-Authoritative Information',
		self::STATUS_NO_CONTENT                      => 'No Content',
		self::STATUS_RESET_CONTENT                   => 'Reset Content',
		self::STATUS_PARTIAL_CONTENT                 => 'Partial Content',
		self::STATUS_MULTIPLE_CHOICES                => 'Multiple Choices',
		self::STATUS_MOVED_PERMANENTLY               => 'Moved Permanently',
		self::STATUS_MOVED_TEMPORARILY               => 'Moved Temporarily ',
		self::STATUS_SEE_OTHER                       => 'See Other',
		self::STATUS_NOT_MODIFIED                    => 'Not Modified',
		self::STATUS_USE_PROXY                       => 'Use Proxy',
		self::STATUS_TEMPORARY_REDIRECT              => 'Temporary Redirect',
		self::STATUS_BAD_REQUEST                     => 'Bad Request',
		self::STATUS_UNAUTHORIZED                    => 'Unauthorized',
		self::STATUS_PAYMENT_REQUIRED                => 'Payment Required',
		self::STATUS_FORBIDDEN                       => 'Forbidden',
		self::STATUS_NOT_FOUND                       => 'Not Found',
		self::STATUS_METHOD_NOT_ALLOWED              => 'Method Not Allowed',
		self::STATUS_NOT_ACCEPTABLE                  => 'Not Acceptable',
		self::STATUS_PROXY_AUTHENTICATION_REQUIRED   => 'Proxy Authentication Required',
		self::STATUS_REQUEST_TIMEOUT                 => 'Request Timeout',
		self::STATUS_CONFLICT                        => 'Conflict',
		self::STATUS_GONE                            => 'Gone',
		self::STATUS_LENGTH_REQUIRED                 => 'Length Required',
		self::STATUS_PRECONDITION_FAILED             => 'Precondition Failed',
		self::STATUS_REQUEST_ENTITY_TOO_LARGE        => 'Request Entity Too Large',
		self::STATUS_REQUEST_URI_TOO_LONG            => 'Request-URI Too Long',
		self::STATUS_UNSUPPORTED_MEDIA_TYPE          => 'Unsupported Media Type',
		self::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
		self::STATUS_EXPECTATION_FAILED              => 'Expectation Failed',
		self::STATUS_INTERNAL_SERVER_ERROR           => 'Internal Server Error',
		self::STATUS_NOT_IMPLEMENTED                 => 'Not Implemented',
		self::STATUS_BAD_GATEWAY                     => 'Bad Gateway',
		self::STATUS_SERVICE_UNAVAILABLE             => 'Service Unavailable',
		self::STATUS_GATEWAY_TIMEOUT                 => 'Gateway Timeout',
		self::STATUS_HTTP_VERSION_NOT_SUPPORTED      => 'HTTP Version Not Supported',
		self::STATUS_BANDWIDTH_LIMIT_EXCEEDED        => 'Bandwidth Limit Exceeded'
	);

	/**
	 * 输出HTTP状态
	 * @param $code
	 * @return bool
	 */
	public static function sendHttpStatus($code){
		$messages = self::STATUS_MESSAGE;
		$message = $messages[$code];
		if(!headers_sent() && $message){
			header('HTTP/1.1 ' . $code . ' ' . $message);
			header('Status:' . $code . ' ' . $message);        //确保FastCGI模式下正常
			return true;
		}
		return false;
	}

	/**
	 * 获取状态码归类
	 * @param $status_code
	 * @return mixed|null
	 */
	public static function getStatusType($status_code){
		$status_code = (string)$status_code;
		$type_map = [
			'1' => self::TYPE_INFO,
			'2' => self::TYPE_SUCCESS,
			'3' => self::TYPE_REDIRECT,
			'4' => self::TYPE_CLIENT_ERROR,
			'5' => self::TYPE_SERVER_ERROR,
		];
		$first_letter = $status_code[0];
		if($type_map[$first_letter]){
			return $type_map[$first_letter];
		}
		return null;
	}

	/**
	 * http跳转
	 * @param $url
	 * @param int $status 状态码，可选redirect相关状态码
	 */
	public static function redirect($url, $status = self::STATUS_MOVED_TEMPORARILY){
		self::sendHttpStatus($status);
		header('Location:' . $url);
	}

	/**
	 * 输出下载文件Header
	 * @param $file_name
	 */
	public static function headerDownloadFile($file_name){
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=" . $file_name);
		header("Content-Transfer-Encoding: binary");
	}
}