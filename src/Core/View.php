<?phpnamespace Lite\Core;use Lite\Exception\Exception;use function Lite\func\is_assoc_array;use function Lite\func\is_function;/** * 视图类，大部分方法用于在模版中直接调用 * 注意：部分方法由于继承于路由，调用这些方法的时候，需考虑是否做htmlspecialchars保护 * User: sasumi * Date: 2015/01/06 * Time: 9:49 */class View extends Router{	const EVENT_AFTER_VIEW_RENDER = 'EVENT_AFTER_VIEW_RENDER';	const REQ_PAGE = 'page';	const REQ_JSON = 'json';	const REQ_JSONP = 'jsonp';	const REQ_FORM_SENDER = 'formsender';	const REQ_IFRAME = 'iframe';	const REQ_DES_KEY = 'ref';	private $req_type;	private static $pre_vars = array();	/** @var \Lite\Core\Result */	private $result;	private $ret_mode = false;	public $controller;	/**	 * 构造方法	 * @param mixed $data 视图渲染数据	 */	public function __construct($data=null){		if($data instanceof Result){			$this->result = $data;		} else {			//默认返回数组情况的标记为"成功"			$this->result = new Result('', true, $data);			$this->ret_mode = true;		}		$this->req_type = $this->parseRequestType();		$this->controller = Controller::getCurrentControllerInstance();	}	/**	 * 渲染视图数据	 * @param string||array $key	 * @param string $val	 */	public function assign($key, $val=null){		if(isset($val)){			$this->result->setItem($key, $val);		} else {			foreach($key as $k=>$v){				$this->result->setItem($k, $v);			}		}	}	/**	 * 预渲染视图数据，用于系统全局前置变量的渲染	 * @param array $pre_vars	 */	public static function preAssignVar(array $pre_vars){		self::$pre_vars = array_merge(self::$pre_vars, $pre_vars);	}	/**	 * 获取前置视图变量	 * @return array	 */	public static function getPreVar(){		return self::$pre_vars;	}	/**	 * 解析请求类型	 * @return string	 */	private function parseRequestType(){		$type = Router::get(self::REQ_DES_KEY);		if(empty($type) || !in_array($type, array(self::REQ_FORM_SENDER , self::REQ_JSON, self::REQ_JSONP, self::REQ_IFRAME, self::REQ_PAGE))){			$type = self::REQ_PAGE;		}		return $type;	}	/**	 * 获取请求类型	 * @return string	 */	public function getRequestType(){		return $this->req_type;	}	/**	 * 设置渲染数据	 * @param Result $result	 */	public function setResult(Result $result){		$this->result = $result;	}	/**	 * 获取渲染的数据	 * @return Result	 */	public function getResult(){		return $this->result;	}	/**	 * 渲染模版	 * @param string $file 文件名称	 * @param bool $return 是否以返回方式返回渲染结果	 * @param null $req_type 请求类型，缺省采用自动解析请求类型	 * @return string	 */	public function render($file=null, $return=false, $req_type=null){		$result = $this->result;		$jump_url = $result->getJumpUrl();		$message = $result->getMessage();		$html = '';		if($req_type === null || !in_array($req_type, array(self::REQ_FORM_SENDER , self::REQ_JSON, self::REQ_JSONP, self::REQ_IFRAME, self::REQ_PAGE))){			$req_type = $this->req_type;		}		switch($req_type){			case self::REQ_FORM_SENDER:				$html = $result->getIframeResponse();				break;			case self::REQ_JSON:				$html = $result->getJson();				break;			case self::REQ_JSONP:				$html = $result->getJsonp();				break;			case self::REQ_PAGE:			case self::REQ_IFRAME:			default:				$data = $result->getData();				//support pure string mode				if(is_scalar($data)){					echo $data;					break;				}				$file = $this->getTemplate($file);				if($file){					ob_start();					if(is_array(self::$pre_vars)){						extract(self::$pre_vars);					}					if(is_array($data)){						extract($data);					}					include $file;					$html = ob_get_contents();					ob_clean();				}				if($this->req_type == self::REQ_PAGE && $this->ret_mode){					//页面模式+纯数据,不补充js交互				} else if($message || $jump_url){					$html .= '<script>';					$html .= ($message?'alert("'.addslashes($message).'");':'');					$html .= ($jump_url ? 'location.href="'.addslashes($jump_url).'";' : '');					$html .= '</script>';				}				break;		}		//引用方式抛事件		$ref_param = new RefParam(array('html'=>$html, 'return'=>$return));		Hooker::fire(self::EVENT_AFTER_VIEW_RENDER, $ref_param);		if($return){			return $ref_param['html'];		} else {			echo $ref_param['html'];			return '';		}	}	/**	 * 获取模版文件路径	 * @param string $file_name	 * @return string 文件路径	 */	private function getTemplate($file_name=null){		$tpl_path = Config::get('app/tpl');		if(is_file($file_name)){			return $file_name;		}		if($file_name){			$file_name = trim(str_replace(array('/','\\'), DIRECTORY_SEPARATOR, $file_name), DIRECTORY_SEPARATOR);			return $tpl_path.$file_name;		}		$controller = self::getController();		$action = self::getAction();		if($controller == self::getDefaultController() &&			$action == self::getDefaultAction()){			$file = 'index.php';		} else if($action == self::getDefaultAction()){			$file = $controller.'.php';		} else {			$file = $controller.'_'.$action.'.php';		}		$file = Config::get('app/tpl').strtolower($file);		$file2 = Config::get('app/tpl').strtolower($controller.DS.$action).'.php';		return is_file($file) ? $file : (			is_file($file2) ? $file2 : ''		);	}	/**	 * 获取静态资源url，重载路由方法，保护html输出	 * @param string $file_name	 * @param null $type	 * @return string	 */	public static function getStaticUrl($file_name, $type = null){		$url = call_user_func_array('parent::getStaticUrl', func_get_args());		return htmlspecialchars($url);	}	/**	 * 获取url，重载路由方法，保护html输出	 * @param string $target	 * @param array $params	 * @param null $router_mode	 * @return string	 */	public static function getUrl($target = '', $params = array(), $router_mode = null){		$url = call_user_func_array('parent::getUrl', func_get_args());		return htmlspecialchars($url);	}	/**	 * 获取js资源url，重载路由方法，保护html输出	 * @param string $file_name	 * @return string	 */	public static function getJsUrl($file_name){		$url = call_user_func_array('parent::getJsUrl', func_get_args());		return htmlspecialchars($url);	}	/**	 * 调用css路径，重载路由方法，保护html输出	 * @param string $css	 * @return string	 */	public static function getCssUrl($css) {		$url = call_user_func_array('parent::getCssUrl', func_get_args());		return htmlspecialchars($url);	}	/**	 * 调用img路径，重载路由方法，保护html输出	 * @param string $file_name	 * @return string	 */	public static function getImgUrl($file_name) {		$url = call_user_func_array('parent::getImgUrl', func_get_args());		return htmlspecialchars($url);	}	/**	 * 调用flash路径，重载路由方法，保护html输出	 * @param string $file_name	 * @return string	 */	public static function getFlashUrl($file_name) {		$url = call_user_func_array('parent::getFlashUrl', func_get_args());		return htmlspecialchars($url);	}	/**	 * 获取脚本链接代码	 * @param string||array $js	 * @return string	 **/	public static function getJs($js/**, $js2, $js3...*/){		$args = func_get_args();		$rst = '';		foreach($args as $js){			if(gettype($js) == 'string'){				if(stripos('/', $js) === false){					$js = self::getJsUrl($js);				}				$rst .= '<script type="text/javascript" src="'.$js.'" charset="utf-8"></script>';			} else {				$sc = '<script type="text/javascript"';				foreach($js as $pro=>$val){					if(strtolower($pro) == 'src'){						$sc .= ' src="'.self::getJsUrl($val).'"';					} else {						$sc .= ' '.htmlspecialchars($pro).'="'.htmlspecialchars($val).'"';					}				}				$sc .= '></script>';				$rst .= $sc;			}		}		return $rst;	}	/**	 * 获取样式表链接代码	 * @param string||array $css	 * @return string	 **/	public static function getCss($css/**, $css2, $css3...*/){		$args = func_get_args();		$rst = '';		foreach($args as $css){			if(gettype($css) == 'string'){				$rst .= '<link rel="stylesheet" type="text/css" href="'.htmlspecialchars(self::getCssUrl($css)).'" media="all"/>';			} else {				$lnk = '<link rel="stylesheet" type="text/css"';				foreach($css as $pro=>$val){					if(strtolower($pro) == 'href'){						$lnk .= ' href="'.self::getCssUrl($val).'"';					} else {						$lnk .= ' '.htmlspecialchars($pro).'="'.htmlspecialchars($val).'"';					}				}				$lnk .= '/>';				$rst .= $lnk;			}		}		return $rst;	}	/**	 * 获取配置	 * @param $key	 * @return array|null	 */	public static function getConfig($key){		return Config::get($key);	}	/**	 * 获取IMG代码	 * @param string $src 图片src	 * @param array $option 选项	 * @return string	 */	public static function getImg($src, $option=array()){		if(!$src){			return '';		}		$ext = '';		$adjust = false;		$src = self::getImgUrl($src);		$option = array_merge(array(			'onload' => '(function(img){window.__img_adjust__ &&　__img_adjust__(img);})(this)',			'onerror' => '(function(img){window.__img_error__ &&　__img_error__(img);})(this)',		), $option);		$onload_fn = $option['onload'];		foreach($option as $key=>$val){			if(preg_match("/(min-height|min-width|max-height|max-width)/i", $key)){				$adjust = true;				$key = 'data-'.$key;			}			$ext .= ' '.htmlspecialchars($key).'="'.htmlspecialchars($val).'"';		}		return "<img src=\"".$src."\" $ext ".($adjust ? " onload=\"$onload_fn(this)\"" : "")."/>";	}	/**	 * 构建html表单代码	 * @param string $url 表单提交地址	 * @param array $fields 字段集	 * @param array $form_option 选项	 * @throws Exception	 * @return string	 */	public static function genForm($url='', array $fields, array $form_option=array()){		$form_option = array_merge(array(			'method' => 'post',			'url' => $url,			'add_submit' => true,			'title' => '',			//container template			'container_tpl' =>			'<form action="$url" method="'.$form_option['method'].'" class="$class">'.			'<fieldset>'.			'<legend>$title</legend>'.			'$fields_html'.			' $submit_html'.			'</fieldset>'.			'</form>',			'submit_html' => '<dl><dt></dt><dd><input type="submit" value="submit"/></dd></dl>',			//one field template			'field_tpl' => '<dl><dt><label for="$id">$label</label></dt><dd>$element</dd></dl>'		), $form_option);		/**		 * build attribute string for element		 * @param array $field		 * @return string		*/		$build_attr = function($field){			$supports = array('name','type','class','style', 'id');			$supports_map = array(					'placeholder' => array('text','email','password','textarea'),					'value' => array('text','password','email','submit','checkbox'),					'size' => array('select'),					'cols' => array('textarea'),					'rows' => array('textarea')			);			foreach($supports_map as $attr_key=>$ls){				if(in_array($field['type'], $ls)){					array_push($supports, $attr_key);				}			}			$attrs = array();			foreach($field as $attr_key=>$val){				if(in_array($attr_key, $supports) && is_scalar($val)){					$val = str_replace('"', '\\"', $val);					array_push($attrs, htmlspecialchars($attr_key).'='.'"'.htmlspecialchars($val).'"');				}			}			return implode(' ', $attrs);		};		/**		 * build a php template with given envarionment data		 * @param $str		 * @param $data		 * @param bool $debug		 * @return mixed		 */		$build_tpl = function($str, $data, $debug=false){			$str = str_replace('"','\\"',$str);			$a = null;			extract($data);			if($debug){				dump(';$a = "'.$str.'"; ');			}			eval(';$a = "'.$str.'"; ');			return $a;		};		$found_submit = false;		$fields_html = '';		foreach($fields as $name=>$field){			if(!$name){				throw new Exception('NO FORM FIELD NAME GIVEN');			}			if($field['type'] == 'submit'){				$found_submit = true;			}			$field['name'] = $name;			$field['label'] = $field['type'] != 'submit' ? ($field['label'] ?: $name) : null;			$field['type'] = $field['type'] ?: 'text';			$field['id'] = $field['type'] != 'radio' ? ($field['id'] ?: '_form_'.$name) : null;			$field['attr_str'] = $build_attr($field);			$item_tpl = $field['item_tpl'] ?: '';			if(!$item_tpl){				switch($field['type']){					case 'textarea':						$item_tpl = '<textarea $attr_str>$value</textarea>';						break;					case 'select':						$item_tpl = '<select size="1" $attr_str>';						foreach($field['options'] as $val=>$label){							$item_tpl .=							'<option value="'.htmlspecialchars($val).'" '.((isset($field['value']) && ($field['value'] == $val)) ? 'selected':'').'>'.							htmlspecialchars($label).							'</option>';						}						$item_tpl .= '</select>';						break;					case 'radio':						$item_tpl = '';						foreach($field['options'] as $val=>$label){							$id = $name.'_radio_'.htmlspecialchars($val);							$item_tpl .= '<input type="radio" id="'.$id.'" value="'.htmlspecialchars($val).'" $attr_str '.((isset($field['value']) && ($field['value'] == $val)) ? 'checked="checked"':'').'/>';							$item_tpl .= '<label for="'.$id.'">$options['.htmlspecialchars($val).']</label>';						}						break;					case 'hidden':						$item_tpl = '<input type="hidden" name="$name" value="$value" />';						break;					case 'checkbox':					case 'file':					case 'text':					case 'password':					case 'submit':					case 'email':					default:						$item_tpl = '<input $attr_str/>';						break;				}			}			//build element html			$element = $build_tpl($item_tpl, $field);			//merge			$tmp = array_merge(array('element'=>$element), $field);			//build field html			$fields_html .= $build_tpl($form_option['field_tpl'], $tmp);		}		if($found_submit || !$form_option['add_submit']){			$form_option['submit_html'] = '';		}		$tmp = array_merge($form_option, array('fields_html' => $fields_html));		$con_tpl = $build_tpl($form_option['container_tpl'], $tmp);		return $con_tpl;	}	/**	 * 表格输出	 * @param array $data 数据	 * @param array $fields 显示key集合	 * @param array $option 选项	 * @return string html字符串	 **/	public static function genTable($data, array $fields=array(), array $option=array()){		$tmp = array_slice($data, 0, 1);		$first = array_pop($tmp);		if(!is_array($data) || !is_array($first) || count($data) == 0){			return '';		}		$fields = $fields ?: array_combine(array_keys($first), array_keys($first));		$option = array_merge(array(				'class' => 'tbl',				'id' => '',				'style' => '',				'summary' => '',				'caption' => '',				'manage' => array(					'checkbox' => function($item){						return '<input type="checkbox" name="ids[]" value="'.$item['id'].'"/>';					},					'batchDeleteAction' => function(/** $item */){						return '';					},					'deleteAction' => function(/** $item */){						return '';					},					'modifyAction' => function(/** $item */){						return '';					}				)		), $option);		$att_str = array();		foreach(array('class','style','summary','id', 'rel') as $cp){			if($option[$cp]){				array_push($att_str, htmlspecialchars($cp).'="'.htmlspecialchars($option[$cp]).'"');			}		}		$html = '<table '.implode(' ', $att_str).'>';		$html .= $option['caption'] ? '<caption>'.htmlspecialchars($option['caption']).'</caption>' : '';		//colgroup		$html .= '<colgroup>';		foreach(array_keys($fields) as $k){			$html .= "<col class=\"$option[class]_col_$k\">";		}		$html .= '</colgroup>';		//head field		if(is_assoc_array($first)){			$html .= '<thead><tr>';			foreach($fields as $field){				if(is_callable($field)){					$html .= '<th>'.htmlspecialchars($field(null)).'</th>';				} else {					$html .= '<th>'.htmlspecialchars($field).'</th>';				}			}			$html .= '</head>';		}		//data fields		$html .= '<tbody>';		foreach($data as $item){			$html .= '<tr>';			foreach($fields as $k=>$v){				if(is_callable($v)){					$html .= '<td>'.htmlspecialchars($v($item)).'</td>';				} else {					$html .= '<td>'.htmlspecialchars($item[$k]).'</td>';				}			}			$html .= '</tr>';		}		$html .= '</tbody>';		if($option['tfoot']){			$cols = count($fields);			$html .= '<tfoot><tr><td colspan="'.$cols.'">'.htmlspecialchars($option['tfoot']).'</td></tr></tfoot>';		}		$html .= '</table>';		return $html;	}	/**	 * 打印异常信息	 * @param Exception $exception	 */	public function print_exception(Exception $exception){		$msg = $exception->getMessage();		$code = $exception->getCode();		$file = $exception->getFile();		$line = $exception->getLine();		echo "<b style=\"font-size:24px\">$msg<br/>[$code]</b><br/>";		echo $file." [$line]";		echo '<pre style="font-size:12px; color:gray">';		dump($exception);	}	/**	 * 判断是不是手机访问	 * @return bool	 */	public static function isMobile(){		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备		if(isset ($_SERVER['HTTP_X_WAP_PROFILE'])){			return true;		}		// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息		if(isset ($_SERVER['HTTP_VIA'])){			// 找不到为false,否则为true			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;		}		// 脑残法，判断手机发送的客户端标志,兼容性有待提高		if(isset ($_SERVER['HTTP_USER_AGENT'])){			$client_keywords = array('nokia',				'sony',				'ericsson',				'mot',				'samsung',				'htc',				'sgh',				'lg',				'sharp',				'sie-',				'philips',				'panasonic',				'alcatel',				'lenovo',				'iphone',				'ipod',				'blackberry',				'meizu',				'android',				'netfront',				'symbian',				'ucweb',				'windowsce',				'palm',				'operamini',				'operamobi',				'openwave',				'nexusone',				'cldc',				'midp',				'wap',				'mobile'			);			// 从HTTP_USER_AGENT中查找手机浏览器的关键字			if(preg_match("/(" . implode('|', $client_keywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){				return true;			}		}		// 协议法，因为有可能不准确，放到最后判断		if(isset ($_SERVER['HTTP_ACCEPT'])){			// 如果只支持wml并且不支持html那一定是移动设备			// 如果支持wml和html但是wml在html之前则是移动设备			if((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){				return true;			}		}		return false;	}}