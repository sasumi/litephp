<?php
namespace Lite\Component;
use Lite\Core\Config;
use Lite\Core\PaginateInterface;
use Lite\Core\Router;

/**
 * 分页
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
class Paginate implements PaginateInterface {
	private static $instance_list;

	private static $_guid_count = 1;
	private $guid;
	private $page_info;
	private $page_size_flag = false;     //page_size是否来自于GET
	private $config = array(
		'show_dot' => true,
		'num_offset' => 5,
		'page_size' => 10,
		'page_key' => 'page',
		'page_size_key' => 'page_size',
		'mode' => 'first,prev,num,next,last',

		'lang' => array(
			'page_first' => '首页',
			'page_prev' => '上一页',
			'page_next' => '下一页',
			'page_last' => '末页',
			'page_info' => '共%s条记录, %d 页, 每页%i条记录',
			'page_jump' => '跳转',
			'page_size' => '每页条数：',
			'page_sel' => '第%s页',
		),
	);

	/**
	 * 私有构造方法,防止非单例调用
	 * @param $config
	 */
	private function __construct($config){
		$this->guid = self::$_guid_count++;
		$this->setConfig($config);
	}

	/**
	 * 获取单例
	 * @param string $identify 分页唯一性ID
	 * @param array $config 配置
	 * @return Paginate
	 */
	public static function instance($identify='page', $config=array()){
		if(!self::$instance_list[$identify]){
			self::$instance_list[$identify] = new self($config);
		}
		return self::$instance_list[$identify];
	}

	/**
	 * 设置配置
	 * @param $config
	 * @return $this
	 */
	public function setConfig($config){
		$this->config = array_merge($this->config, $config);
		return $this;
	}

	/**
	 * 数组分页
	 * @param array $data
	 * @return bool
	 */
	public function paginateData(array &$data=array()){
		$this->setItemTotal(count($data));
		$limit = $this->getLimit();
		$data = array_slice($data, $limit[0], $limit[1]);
		return true;
	}

	/**
	 * 设置每页数量
	 * @param $num
	 */
	public function setPageSize($num){
		$this->config['page_size'] = $num;
	}

	/**
	 * 获取配置
	 * @param string $key
	 * @return array
	 */
	public function getConfig($key = ''){
		return $key ? $this->config[$key] : $this->config;
	}

	/**
	 * 设置总数量
	 * @param int $item_total
	 * @return $this
	 */
	public function setItemTotal($item_total = 0){
		$this->page_info['item_total'] = $item_total;
		return $this;
	}

	/**
	 * 获取分页信息
	 * @param string $key
	 * @return mixed
	 */
	public function getInfo($key = ''){
		$this->updatePageInfo();
		return $key ? $this->page_info[$key] : $this->page_info;
	}

	/**
	 * 获取limit信息
	 * @return array
	 */
	public function getLimit(){
		$this->updatePageInfo();
		$start = ($this->page_info['page_index']-1)*$this->page_info['page_size'];
		return array($start, $this->page_info['page_size']);
	}

	/**
	 * 更新(重载)分页信息
	 * @return $this
	 */
	private function updatePageInfo(){
		$page_index = (int)Router::get($this->config['page_key']);
		$page_index = $page_index > 0 ? $page_index : 1;

		$page_size = (int)Router::get($this->config['page_size_key']);
		if($page_size){
			$this->page_size_flag = true;
		} else {
			$page_size = $this->getConfig('page_size');
		}
		$item_total = $this->page_info['item_total'];

		$page_total = (int)ceil($item_total / $page_size);

		$this->page_info['page_index'] = $page_index;
		$this->page_info['page_size'] = $page_size;
		$this->page_info['page_total'] = $page_total;
		return $this;
	}

	/**
	 * 获取分页链接URL
	 * @param int $num 页码(1开始)
	 * @param null $page_size
	 * @return string
	 */
	private function getUrl($num = null, $page_size=null){
		$gets = Router::get();
		if(!empty($gets)){
			foreach($gets as $key=>$get){
				if($key == $this->config['page_key']){
					unset($gets[$key]);
				}
				if($key == $this->config['page_size_key']){
					unset($gets[$key]);
				}
			}
		}
		if(isset($num)){
			$gets[$this->config['page_key']] = $num;
		}
		if($this->page_size_flag && $page_size){
			$gets[$this->config['page_size_key']] = $page_size;
		}
		$controller = Router::getController();
		$action = Router::getAction();

		/** @var Router $render */
		$render = Config::get('app/render');
		return $render::getUrl("$controller/$action", $gets);
	}

	/**
	 * 转换字符串
	 * @return string
	 */
	public function __toString(){
		$page_modes = array_map('trim', explode(',', $this->config['mode']));
		$this->updatePageInfo();
		$page_info = $this->getInfo();
		$page_config = $this->getConfig();
		$lang = $this->getConfig('lang');
		$html = '';

		$gets = Router::get();
		if(!empty($gets)){
			foreach($gets as $key=>$get){
				if($key == $this->config['page_key']){
					unset($gets[$key]);
				}
				if($key == $this->config['page_size_key']){
					unset($gets[$key]);
				}
			}
		}
		$form_action = Router::getUrl(Router::getController().'/'.Router::getAction(), $gets);

		foreach($page_modes as $mode){
			//first page
			if($mode == 'first'){
				if($page_info['page_index'] == 1){
    				$html .= '<span class="page_first">'.$lang['page_first'].'</span>';
				} else {
    				$html .= '<a href="'.$this->getUrl(1, $page_info['page_size']).'" class="page_first">'.$lang['page_first'].'</a>';
				}
			}

			//last page
			else if($mode == 'last'){
				$tmp = $lang['page_last'];
				$tmp = str_replace('%d', $page_info['page_total'], $tmp);
				if(empty($page_info['page_total']) || $page_info['page_index'] == $page_info['page_total']){
    				$html .= '<span class="page_last">'.$tmp.'</span>';
				} else {
    				$html .= '<a href="'.$this->getUrl($page_info['page_total'], $page_info['page_size']).'" class="page_last">'.$tmp.'</a>';
				}
			}

			//next page
			else if($mode == 'next'){
				$tmp = $lang['page_next'];
				if($page_info['page_index'] < $page_info['page_total']){
    				$html .= '<a href="'.$this->getUrl($page_info['page_index']+1, $page_info['page_size']).'" class="page_next">'.$tmp.'</a>';
				} else {
    				$html .= '<span class="page_next">'.$tmp.'</span>';
				}
			}

			//prev page
			else if($mode == 'prev'){
				$tmp = $lang['page_prev'];
				if($page_info['page_index'] > 1){
    				$html .= '<a href="'.$this->getUrl($page_info['page_index']-1, $page_info['page_size']).'" class="page_prev">'.$tmp.'</a>';
				} else {
    				$html .= '<span class="page_prev">'.$tmp.'</span>';
				}
			}

			//page num
			else if($mode == 'num'){
				$offset_len = $page_config['num_offset'];
				$html .= '<span class="page_num">';
				$html .= (($page_info['page_index']-$offset_len>0) && $page_config['show_dot']) ? '<em class="page_dots">...</em>' : null;
				for($i=$page_info['page_index']-$offset_len; $i<=$page_info['page_index']+$offset_len; $i++){
					if($i>0 && $i<=$page_info['page_total']){
						$html .= ($page_info['page_index'] != $i) ? '<a href="'.$this->getUrl($i, $page_info['page_size']).'">'.$i.'</a>':'<em class="page_current">'.$i.'</em>';
					}
				}
				$html .= (($page_info['page_index'] + $offset_len < $page_info['page_total'])
					&& $page_config['show_dot']) ? '<em class="page_dots">...</em>' : null;
				$html .= '</span>';
			}

			//total
			else if($mode == 'info'){
    			$html .= '<span class="page_info">';
				$tmp = $lang['page_info'];
				$tmp = str_replace('%s', $page_info['item_total'], $tmp);
				$tmp = str_replace('%d', $page_info['page_total'], $tmp);
				$tmp = str_replace('%i', $page_info['page_size'], $tmp);
				$html .= $tmp;
    			$html .= '</span>';
			}

			//page input
			//need javascript enabled supporting
			else if($mode == 'input' && $page_info['page_total'] > 0){
				$html .= '<form action="'.$form_action.'" method="get" class="page_input_form">';
				$html .= '<input type="number" class="page_input" name="'.$this->config['page_key'].'" size="2" value="">';

				if($this->page_size_flag){
					$html .= '<input type="hidden" class="page_input" name="'.$this->config['page_size_key'].'" size="2" value="'.$page_info['page_size'].'">';
				}

				$html .= '<input type="submit" class="page_jump_btn" value="'.$lang['page_jump'].'">';
				$html .= '</form>';
			}

			else if($mode == 'select' && $page_info['page_total'] > 0){
				$html .= '<form action="'.$form_action.'" method="get" class="page_select_form">';
				if($this->page_size_flag){
					$html .= '<input type="hidden" class="page_input" name="'.$this->config['page_size_key'].'" size="2" value="'.$page_info['page_size'].'">';
				}
				$html .= '<select onchange="this.parentNode.submit()" name="'.$this->config['page_key'].'">';
				for($i=1; $i<=$page_info['page_total']; $i++){
					$html .= '<option value="'.$i.'" '.($page_info['page_index']  == $i ? 'selected':'').'>'.
						str_replace('%s', $i, $lang['page_sel']).'</option>';
				}
				$html .= '</select>';
				$html .= '</form>';
			}

			else if($mode == 'page_size'){
				$html .= '<form action="'.$form_action.'" method="get" class="page_size_form">';
				$html .= '<label>'.$lang['page_size'];
				$html .= '<input type="number" list="page_number_list_'.$this->guid.'" class="page_size_input" name="'.$this->config['page_size_key'].'" size="2" value="'.$page_info['page_size'].'">';
				$html .= '<datalist id="page_number_list_'.$this->guid.'">';
				$html .= '<option label="10" value="10"/>';
				$html .= '<option label="20" value="20"/>';
				$html .= '<option label="50" value="50"/>';
				$html .= '<option label="100" value="100"/>';
				$html .= '</datalist>';
				$html .= '</label>';
				$html .= '<input type="submit" class="page_size_submit" value="提交">';
				$html .= '</form>';
			}
		}
		return '<span class="pagination '.'pagination-'.$page_info['page_total'].'">'.$html.'</span>';
	}
}