<?php
namespace Lite\Component\TPService;
use Lite\Exception\Exception;

/**
 * 大地在线短信服务
 * Class DD106SmsService
 * @package oms\business
 */
abstract class DD106SmsService{
	private static $api_username;
	private static $api_password;
	private static $api_gwid;
	private static $api_signature;
	private static $api_host = 'http://jk.106api.cn/smsUTF8.aspx';

	const RET_CODE_MAP = [
		'0'   => 'Success',
		'-1'  => '账户名为空',
		'-2'  => '帐户密码错误',
		'-3'  => '账户不存在',
		'-4'  => '账户密码错误',
		'-5'  => '发送手机号码为空',
		'-6'  => '发送短信内容为空',
		'-7'  => '短信签名为空',
		'-8'  => '手机号码格式错误',
		'-9'  => '短信内容仅能包含一个【】这种符号，请用其它符号代替',
		'-10' => '指定网关ID错误',
		'-11' => '账户余额不足',
		'-12' => '账户没有充值',
		'-13' => '106营销短信必须末尾加退订回T，供用户选择',
		'-14' => '短信内容包含屏蔽词，请登录平台检测',
		'-15' => '账户冻结',
		'-16' => 'IP没有权限',
		'-17' => '多号码格式错误',
		'-18' => '下发短信长度超限，最多是350个字符，其中空格标点符号都算作一个字符',
		'-19' => '网关ID为空',
		'-20' => '不存在应用签名或者签名为审核，请登录平台查看',
		'-21' => '定时发送时间格式化错误',
		'-22' => '短信下发格式错误，正确格式：【签名】+短信内容，签名符号【】 只能出现一次！',
		'-23' => '密码修改失败',
		'-24' => '密码修改失败,新密码包含汉子',
		'-30' => '定时时间必须大于当前系统时间！',
		'-97' => '提交短信失败',
		'-98' => '系统繁忙',
		'-99' => '未知异常',
	];

	//singleton
	private function __construct(){}
	private function __clone(){}

	public static function instance($config = []){
		static $instance;
		if(!$instance){
			$instance = new static();
			$config = $config ?: static::getConfig();
			static::$api_username = $config['username'];
			static::$api_password = $config['password'];
			static::$api_gwid = $config['gwid'];
			static::$api_signature = $config['signature'];

		}
		return $instance;
	}

	/**
	 * Get config method for override
	 * @return array
	 */
	public static function getConfig(){
		return [
			'username'  => '', //账号
			'password'  => '', //密码
			'gwid'      => '', //网关ID
			'signature' => '', //签名
		];
	}

	/**
	 * 发送短信
	 * @param $mobile
	 * @param $message
	 * @param string $ret_msg
	 * @return bool
	 */
	public function sendSms($mobile, $message, &$ret_msg = ''){
		$postData = array(
			'type'    => 'send',
			'gwid'    => static::$api_gwid,
			'mobile'  => $mobile,
			'message' => $message
		);
		$ret = static::request($postData);
		$ret = $ret ? json_decode($ret, true) : $ret;
		$ret_msg = static::RET_CODE_MAP[$ret['code']];
		$success = $ret['code'] == 0 ? true : false;

		$log_str = $success ? '-成功-' : '【失败】';
		$log_str .= ' 发送内容：'.$message.' | 发送号码：'.$mobile.' | 返回：'.json_encode($ret);
		static::log($log_str);
		return $success;
	}

	private static function log($str){
		$log_dir = sys_get_temp_dir().'/dd106_send_log/';
		if(!is_dir($log_dir)){
			$ret = mkdir($log_dir);
			if(!$ret){
				throw new \Exception('sms log directory create fail:'.$log_dir);
			}
		}

		$str = date('Y-m-d H:i:s')." $str\n";
		$log_fn = date('Ym').'.log';
		$ret = file_put_contents($log_dir.$log_fn, $str, FILE_APPEND);
		if(!$ret){
			throw new Exception('sms log file write fail:'.$log_dir.$log_fn);
		}
		return true;
	}

	/**
	 * @param $param
	 * @return string
	 */
	private static function request($param){
		$row = parse_url(static::$api_host);
		$host = $row['host'];
		$port = isset($row['port']) ? $row['port'] : 80;
		$file = $row['path'];

		$param['rece'] = 'json';
		$param['username'] = static::$api_username;
		$param['password'] = static::$api_password;

		$post_str = "";
		while(list($k, $v) = each($param)){
			$post_str .= rawurlencode($k)."=".rawurlencode($v)."&";
		}
		$post_str = substr($post_str, 0, -1);
		$len = strlen($post_str);
		$fp = @fsockopen($host, $port, $errno, $errstr, 10);
		if(!$fp){
			return "$errstr ($errno)\n";
		} else{
			$receive = '';
			$out = "POST $file HTTP/1.1\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Content-type: application/x-www-form-urlencoded\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Content-Length: $len\r\n\r\n";
			$out .= $post_str;
			fwrite($fp, $out);
			while(!feof($fp)){
				$receive .= fgets($fp, 128);
			}
			fclose($fp);
			$receive = explode("\r\n\r\n", $receive);
			unset($receive[0]);
			return implode("", $receive);
		}
	}

	/**
	 * 根据模板ID获取短信内容（包含签名）
	 * @param $template_idx
	 * @param $param
	 * @return string
	 */
	public static function getContentByTemplateIdx($template_idx, $param){
		$cfg = static::getConfig();
		$content = $cfg['template'][$template_idx];
		$signature = $cfg['signature'];
		$pattern = '/\$\{([^\}]+)\}/i';
		$str = preg_replace_callback($pattern, function($matches) use ($param){
			return $param[$matches[1]];
		}, $content);
		return $signature.$str;
	}
}