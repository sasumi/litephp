<?php
namespace Lite\Component\UI;
use Lite\Component\String\Html;
use Lite\Core\Router;
use function Lite\func\array_merge_recursive_distinct;
use function Lite\func\guid;

/**
 * 分页
 * User: sasumi
 * Date: 14-8-28
 * Time: 上午11:25
 */
class Paginate implements PaginateInterface {
	private $guid;
	private $page_info;
	public $page_size_flag = false;     //page_size是否来自于GET
	private $config = array(
		'show_dot'      => true,
		'num_offset'    => 2,
		'page_size'     => 10,
		'page_key'      => 'page',
		'page_size_key' => 'page_size',
		'mode'          => 'prev,num,next,info',

		'lang' => array(
			'page_first' => '首页',
			'page_prev'  => '上一页',
			'page_next'  => '下一页',
			'page_last'  => '末页',
			'page_info'  => '共 %s 条数据, 每页 %i 条',
			'page_jump'  => '跳转',
			'page_size'  => '每页条数：',
			'page_sel'   => '第%s页',
		),
	);

	/**
	 * 私有构造方法,防止非单例调用
	 * @param $config
	 */
	private function __construct($config){
		$this->guid = guid();
		$this->setConfig($config);
	}

	/**
	 * 获取单例
	 * @param string $identify 分页唯一性ID
	 * @param array $config 配置
	 * @return Paginate
	 */
	public static function instance($identify='page', $config=array()){
		static $instance_list = [];
		if(!isset($instance_list[$identify])){
			$instance_list[$identify] = new self($config);
		}
		return $instance_list[$identify];
	}

	/**
	 * 设置配置
	 * @param $config
	 * @return $this
	 */
	public function setConfig($config){
		$this->config = array_merge_recursive_distinct($this->config, $config);
		return $this;
	}

	/**
	 * 数组分页
	 * @param array $data
	 * @return bool
	 */
	public function paginateData(array &$data=null){
		if(!is_array($data)){
			debug_print_backtrace();die;
		}
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
	 * @throws \Lite\Exception\Exception
	 */
	public function getUrl($num = null, $page_size=null){
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
		return Router::getUrl(Router::getCurrentUri(), $gets);
	}

	/**
	 * 转换字符串
	 * @return string
	 * @throws \Lite\Exception\Exception
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
		$form_action = Router::getUrl(Router::getCurrentUri(), $gets);

		foreach($page_modes as $mode){
			//first page
			if($mode == 'first'){
				if($page_info['page_index'] == 1){
					$html .= '<span class="page_first">'.$lang['page_first'].'</span>';
				} else {
					$html .= Html::htmlLink($lang['page_first'], $this->getUrl(1, $page_info['page_size']), ['class' => 'page_first']);
				}
			}

			//last page
			else if($mode == 'last'){
				$tmp = $lang['page_last'];
				$tmp = str_replace('%d', $page_info['page_total'], $tmp);
				if(empty($page_info['page_total']) || $page_info['page_index'] == $page_info['page_total']){
					$html .= '<span class="page_last">'.$tmp.'</span>';
				} else {
					$html .= Html::htmlLink($tmp, $this->getUrl($page_info['page_total'], $page_info['page_size']), ['class' => 'page_last']);
				}
			}

			//next page
			else if($mode == 'next'){
				$tmp = $lang['page_next'];
				if($page_info['page_index'] < $page_info['page_total']){
					$html .= Html::htmlLink($tmp, $this->getUrl($page_info['page_index']+1, $page_info['page_size']), ['class' => 'page_next']);
				} else {
					$html .= '<span class="page_next">'.$tmp.'</span>';
				}
			}

			//prev page
			else if($mode == 'prev'){
				$tmp = $lang['page_prev'];
				if($page_info['page_index'] > 1){
					$html .= Html::htmlLink($tmp, $this->getUrl($page_info['page_index']-1, $page_info['page_size']), ['class' => 'page_prev']);
				} else {
					$html .= '<span class="page_prev">'.$tmp.'</span>';
				}
			}

			//page num
			else if($mode == 'num'){
				$offset_len = $page_config['num_offset'];
				$html .= '<span class="page_num">';
				if($page_info['page_index']-$offset_len > 1){
					$html .= Html::htmlLink(1, $this->getUrl(1, $page_info['page_size']));
				}

				$html .= ($page_info['page_index'] - $offset_len > 2) ? '<em class="page_dots">...</em>' : null;
				for($i=$page_info['page_index']-$offset_len; $i<=$page_info['page_index']+$offset_len; $i++){
					if($i>0 && $i<=$page_info['page_total']){
						$html .= ($page_info['page_index'] != $i) ?
							Html::htmlLink($i, $this->getUrl($i, $page_info['page_size'])) :'<em class="page_current">'.$i.'</em>';
					}
				}
				$show_last_dots = ($page_info['page_index'] + $offset_len < $page_info['page_total']) && $page_config['show_dot'];
				$html .= ($show_last_dots && ($page_info['page_total'] - $page_info['page_index'] - $offset_len) > 1) ? '<em class="page_dots">...</em>' : null;
				if($show_last_dots){
					$html .= Html::htmlLink($page_info['page_total'], $this->getUrl($page_info['page_total'], $page_info['page_size']));
				}

				$html .= '</span>';
			}

			//total
			else if($mode == 'info'){
				$html .= '<span class="page_info">';
				$tmp = $lang['page_info'];
				$tmp = str_replace('%s', $page_info['item_total'], $tmp);
				$tmp = str_replace('%d', $page_info['page_total'], $tmp);
				$tmp = str_replace('%k', $page_info['page_index'], $tmp);
				$tmp = str_replace('%i', $page_info['page_size'], $tmp);
				$tmp = str_replace('%p', htmlspecialchars($this->getUrl(1, '_ppp_')), $tmp);
				$html .= $tmp;
				$html .= '</span>';
			}

			//page input
			//need javascript enabled supporting
			else if($mode == 'input' && $page_info['page_total'] > 0){
				$html .= '<form action="'.$form_action.'" method="get" class="page_input_form">';
				$html .= Html::htmlNumber($this->config['page_key'], '', [
					'class'    => 'page_input',
					'size'     => 2,
					'step'     => 1,
					'min'      => 1,
					'required' => 'required'
				]);
				$html .= $this->page_size_flag ? Html::htmlHidden($this->config['page_size_key'], $page_info['page_size']) : '';
				$html .= Html::htmlInputSubmit($lang['page_jump'], ['class'=>'page_jump_btn']);
				$html .= '</form>';
			}

			else if($mode == 'select' && $page_info['page_total'] > 0){
				$html .= '<form action="'.$form_action.'" method="get" class="page_select_form">';
				$html .= $this->page_size_flag ? Html::htmlHidden($this->config['page_size_key'], $page_info['page_size']) : '';
				$html .= '<select onchange="this.parentNode.submit()" name="'.$this->config['page_key'].'" required="required">';
				for($i=1; $i<=$page_info['page_total']; $i++){
					$html .= Html::htmlOption(str_replace('%s', $i, $lang['page_sel']), $i, $page_info['page_index']  == $i);
				}
				$html .= '</select>';
				$html .= '</form>';
			}

			else if($mode == 'page_size'){
				$html .= '<form action="'.$form_action.'" method="get" class="page_size_form">';
				$html .= '<label>'.$lang['page_size'];
				$html .= Html::htmlNumber($this->config['page_size_key'], $page_info['page_size'], [
					'list'  => 'page_number_list_'.$this->guid,
					'class' => 'page_size_input',
					'size'  => 2,
					'min'   => 1
				]);
				$html .= Html::htmlDataList('page_number_list_'.$this->guid, ['10', '20', '50', '100']);
				$html .= '</label>';
				$html .= Html::htmlInputSubmit('提交', ['class'=>'page_size_submit']);
				$html .= '</form>';
			}
		}
		return '<span class="pagination '.'pagination-'.$page_info['page_total'].'">'.$html.'</span>';
	}
}