<?php
namespace Lite\Core;


use Lite\Exception\Exception;
use Lite\Exception\RouterException;
use function Lite\func\array_keys_exists;
use function Lite\func\is_function;

/**
 * Class Rewrite
 * 路由重写，仅在 URL MODE = REWRITE下生效
 * 当前类做两件事情：
 * 1、监听 Router::parserCurrentRequest，发现命中规则路由，返回 uri、params
 * 2、监听 Router::getUrl函数，发现命中规则路由，返回重写后的URL
 * 默认读取配置 router/rewrites 下规则
 * @package Lite\Core
 */
final class Rewrite{
	/**
	 * @var array[[$resolver, $case],...]
	 */
	private $rules = [];
	
	const HOLDER_TYPE_ANY = '__ANYTHING__';
	const HOLDER_TYPE_DIG = '__DIGITAL__';
	const HOLDER_TYPE_WORD = '__WORD__';
	
	const HOLDER_REG = '/(\{\$[^\}]+\})/';
	const HOLDER_REG_ANY = '(.*?)';
	const HOLDER_REG_DIG = '(\d+)';
	const HOLDER_REG_WORD = '(\w+)';
	
	/**
	 * 占位符类型表
	 */
	const HOLDER_MAP = array(
		self::HOLDER_TYPE_DIG  => self::HOLDER_REG_DIG,
		self::HOLDER_TYPE_ANY  => self::HOLDER_REG_ANY,
		self::HOLDER_TYPE_WORD => self::HOLDER_REG_WORD,
	);
	
	/**
	 * 单例化
	 * @param array $rules
	 * @return Rewrite
	 * @throws \Lite\Exception\Exception
	 */
	private static function instance($rules = array()){
		static $ins;
		if(!$ins){
			$rules = Config::get('router/rewrites') ?: $rules;
			$ins = new self($rules);
		}
		return $ins;
	}
	
	/**
	 * launch router rewrite
	 * Rewrite constructor.
	 * @param $rules
	 */
	private function __construct($rules){
		$this->addRules($rules);
	}
	
	/**
	 * on parse current request
	 * @param $path_info
	 * @param $get
	 * @return array
	 * @throws \Lite\Exception\Exception
	 */
	public static function onParseRequest($path_info, $get){
		return self::instance()->parseRequest($path_info, $get);
	}
	
	/**
	 * 获取链接事件
	 * @param $uri
	 * @param $params
	 * @return array|mixed|null|string
	 * @throws RouterException
	 * @throws \Lite\Exception\Exception
	 */
	public static function onGetUrl($uri, $params){
		return self::instance()->buildUrl($uri, $params);
	}
	
	/**
	 * 根据规则构建URL链接
	 * @param $uri
	 * @param $params
	 * @return array|mixed|null|string
	 * @throws RouterException
	 * @throws \Lite\Exception\Exception
	 */
	public function buildUrl($uri, $params){
		if(!$this->rules){
			return null;
		}
		
		$app_url = Config::get('app/url');
		static $caches = array();
		
		//cache
		$cache_key = $uri . serialize($params);
		if($caches[$cache_key]){
			return $caches[$cache_key];
		}
		foreach($this->rules as $url_mode => list($resolver, $case_sensitive, $full_match)){
			//anonymous function pairs
			if(is_array($resolver) && is_function($resolver[1])){
				$matches = $resolver[1]($uri, $params);
				if(is_array($matches)){
					//found not matched params
					$ext_params = $params;
					foreach($matches as $k => $v){
						unset($ext_params[$k]);
						if(stripos($url_mode, '{$' . $k . '}') === false && stripos($url_mode, '{$' . $k . ':') === false){
							$ext_params[$k] = $v;
						}
					}
					if($full_match && $ext_params){
						throw new RouterException('Rule did not match extra params', null, $ext_params);
					}
					$url = $app_url . ltrim(self::replaceParamHolder($url_mode, $matches), '/');
					if($ext_params){
						$url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($ext_params);
					}
					$caches[$cache_key] = $url;
					return $url;
				}
			}
			
			//URI & params pattern
			if(is_array($resolver) && is_string($resolver[0])){
				//action replace
				$uri_pt = $resolver[0];
				$uri_pt = preg_replace('/\/\{\$[^\}]+\}$/', end(explode('/', $uri)), $uri_pt);
				
				//check uri match
				if(strcasecmp($uri_pt, $uri) !== 0){
					continue;
				}
				
				if($resolver[1]){
					parse_str($resolver[1], $kv_map);
					
					//保证pattern中所有变量覆盖params中的变量
					//否则当做不命中
					if(!array_keys_exists(array_keys($kv_map), $params)){
						continue;
					}
					
					$replacement = array();
					foreach($kv_map as $var_name => $holder_val){
						//remove none exists param
						if(preg_match(self::HOLDER_REG, $holder_val)){
							if(!isset($params[$var_name])){
								continue;
							}
							$holder_var_name = preg_replace('/\W/', '', $holder_val);
							$replacement[$holder_var_name] = $params[$var_name];
							unset($params[$var_name]);
						} //固定值必须相等，否则当做不命中
						else if(!isset($params[$var_name]) || $params[$var_name] != $holder_val){
							continue 2;
						} else{
							unset($params[$var_name]);
							$replacement[$var_name] = $params[$var_name];
						}
					}
					$replacement = array_merge($replacement, $params);
					$url = self::replaceParamHolder($url_mode, $replacement, $hit_keys);
					foreach($hit_keys as $k){
						unset($replacement[$k]);
					}
					
					//append left params
					if($params){
						$url .= ((strpos($url, '?') !== false) ? '&' : '?') . http_build_query($params);
					}
				} else{
					//uri mode
					$url = $app_url . ltrim(self::replaceParamHolder($url_mode, [], $hit_keys), '/');
				}
				$caches[$cache_key] = $url;
				return $url;
			}
		}
		return '';
	}
	
	/**
	 * 获取占位符类型匹配正则
	 * @param $str
	 * @return string
	 * @throws Exception
	 */
	private static function getHolderType($str){
		if(!strpos($str, ':')){
			return self::HOLDER_TYPE_WORD;
		}
		if(preg_match('/\:([^\:]+)}$/', $str, $matches)){
			switch($matches[1]){
				case '*':
					return self::HOLDER_TYPE_ANY;
				
				case 'd':
					return self::HOLDER_TYPE_DIG;
				
				case 'w':
					return self::HOLDER_TYPE_WORD;
			}
		}
		throw new Exception('Rewrite rule holder format error', null, [$str, $matches]);
	}
	
	/**
	 * url规则匹配（当前不支持针对queryString进行匹配）
	 * @param string $path_info 路径信息
	 * @param array $query_params
	 * @return array string:URL地址,array[uri, param]
	 * @throws Exception
	 */
	private function parseRequest($path_info, $query_params = array()){
		if(!$this->rules){
			return null;
		}
		
		$path_info = strpos($path_info, '/') === 0 ? $path_info : "/$path_info";
		
		foreach($this->rules as $url_mode => list($resolver, $case_sensitive, $full_match)){
			$match_var_names = array();
			$match_values = array();
			
			//remove pseudo string
			$url_reg = preg_replace_callback(self::HOLDER_REG, function ($matches){
				return self::getHolderType($matches[1]);
			}, $url_mode);
			
			//quote regexp
			$url_reg = preg_quote($url_reg, '/');
			
			//restore reg pattern
			$url_reg = str_replace(array_keys(self::HOLDER_MAP), array_values(self::HOLDER_MAP), $url_reg);
			
			//patch tail flag or case flag
			$url_reg = '/' . $url_reg . ($full_match ? '$' : '') . '/' . ($case_sensitive ? '' : 'i');
			
			//hits
			if(preg_match($url_reg, $path_info, $match_values)){
				array_shift($match_values);
				preg_match(self::HOLDER_REG, $url_mode, $match_var_names);
				array_shift($match_var_names);
				
				//extra available variable name
				array_walk($match_var_names, function (&$item){
					$item = preg_replace('/\:.*$/', '', $item); //clear pseudo string
					$item = preg_replace('/\W/', '', $item);
				});
				
				//build matches parameters
				$ret = self::execResolver($resolver, array_combine($match_var_names, $match_values));
				if(!$ret){
					continue;
				}
				list($uri, $query_string) = $ret;
				parse_str($query_string, $params);
				if($full_match && $query_params){
					throw new RouterException('Router rewrite rule no full matches all');
				}
				
				//use rewrite params first
				$params = array_merge($query_params, $params ?: array());
				return [$uri, $params];
			}
		};
		return null;
	}
	
	/**
	 * 执行解析器
	 * @param string $resolver 解析器
	 * @param array $match_params
	 * @return mixed
	 * @throws Exception
	 */
	private static function execResolver($resolver, $match_params = array()){
		//anonymous function
		if(is_function($resolver)){
			return call_user_func($resolver, $match_params);
		}
		
		//anonymous function pairs
		if(is_array($resolver) && is_function($resolver[0])){
			return call_user_func($resolver[0], $match_params);
		}
		
		//URI & params pattern
		if(is_array($resolver) && is_string($resolver[0])){
			return self::replaceParamHolder($resolver, $match_params);
		}
		
		//url string
		if(is_string($resolver)){
			$resolver = self::replaceParamHolder($resolver, $match_params);
			Router::jumpTo($resolver);
			return true;
		}
		throw new Exception('No support rewrite resolver format');
	}
	
	/**
	 * 替换占位符
	 * @param array|string $url_mode
	 * @param $param
	 * @param array $hit_keys
	 * @return string
	 */
	private static function replaceParamHolder($url_mode, $param, &$hit_keys = array()){
		if(is_array($url_mode)){
			foreach($url_mode as $k => $v){
				$url_mode[$k] = self::replaceParamHolder($v, $param, $hit_keys);
			}
		} else{
			$ks = array();
			$vs = array();
			$hit_keys = array();
			foreach($param as $k => $v){
				$ks[] = '/\{\$' . preg_quote($k) . '[\:\S+]*\}/';
				$vs[] = str_replace('$', '\$', $v);
				if(stripos($url_mode, '{$' . $k . '}') !== false){
					$hit_keys[] = $k;
				}
			}
			$url_mode = preg_replace($ks, $vs, $url_mode);
			$url_mode = preg_replace(self::HOLDER_REG, '', $url_mode); //cleanup left holder
		}
		return $url_mode;
	}
	
	/**
	 * 添加重写规则
	 * @param string $url_mode 路由URL表示，
	 * <pre>
	 * 如：news/{$year}/{$month}/{$day} 默认分隔符定义为 \w
	 * 又如：news/{$id:d} 指定为数字
	 * 又如：news/{$id:*}.html 指定为所有
	 * </pre>
	 * @param callable|string|array $resolver 解析处理模式，callable：闭包函数，string：可执行函数或URL，array
	 * @param bool $case_sensitive 大小写区分
	 * @param bool $full_match 全匹配
	 */
	public function addRule($url_mode, $resolver, $case_sensitive = false, $full_match = false){
		$this->rules[$url_mode] = [$resolver, $case_sensitive, $full_match];
	}
	
	/**
	 * 批量添加重写规则
	 * @param $rules
	 * @param bool $case_sensitive
	 * @param bool $full_match
	 */
	public function addRules($rules, $case_sensitive = false, $full_match = false){
		foreach($rules as $url_mode => $resolver){
			$this->addRule($url_mode, $resolver, $case_sensitive, $full_match);
		}
	}
}