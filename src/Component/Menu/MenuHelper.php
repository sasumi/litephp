<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/10/8
 * Time: 18:34
 */
namespace Lite\Component\Menu;
use Lite\Core\Router;
use function Lite\func\array_first;

/**
 * 系统菜单
 * 用法：<p>
 * $mnu = new Menu($menu_data);
 * echo $mnu->getMainMenu();
 * </p>
 * 菜单配置数据结构为：<p>
 * array(
 *      array('首页', 'index'),
 *      array('样品管理', 'article/index', array(
 *          '样品管理' => array(
 *              array('样品数据', 'article/showArticleList'),
 *              array('样品生产', 'articlecategory/index'),
 *              array('工价管理', 'articlecategory/index'),
 *              array('色号釉号', 'articlecategory/index'),
 *              array('花纸管理', 'articlecategory/index'),
 *              array('取样管理', 'articlecategory/index'),
 *          ),
 *      )),
 *      array('业务管理', 'business/index', array(
 *           '业务管理' => array(
 *              array('订单管理', 'business/showArticleList'),
 *              array('送样管理', 'business/index'),
 *              array('客户管理', 'business/index'),
 *              array('参数设置', 'business/index'),
 *          ),
 *      )),
 * )
 * </p>
 * Class Menu
 * @package Lite\Component
 */
class MenuHelper {
	private $data;
	private $menu_data;
	private $access_check;

	private $main_tpl_file = __DIR__.DIRECTORY_SEPARATOR.'main_tpl.php';
	private $side_tpl_file = __DIR__.DIRECTORY_SEPARATOR.'side_tpl.php';

	/**
	 * @param array $data 菜单数据
	 * @param null $access_check 权限检查函数
	 * @param string $main_tpl_file 主菜单模版
	 * @param string $side_tpl_file 子菜单模版
	 */
	public function __construct(array $data, $access_check = null, $main_tpl_file = '', $side_tpl_file = ''){
		$this->data = $data;
		$this->access_check = $access_check;
		if($main_tpl_file){
			$this->main_tpl_file = $main_tpl_file;
		}
		if($side_tpl_file){
			$this->side_tpl_file = $side_tpl_file;
		}
	}

	/**
	 * 检查权限
	 * @param $uri
	 * @return bool
	 */
	private function checkAccess($uri){
		if($this->access_check){
			return !!call_user_func($this->access_check, $uri);
		}
		return true;
	}

	/**
	 * 产生需要的菜单数据
	 * @param $type
	 * @return array
	 */
	public function getMenuData($type){
		if(!$this->menu_data[$type]){
			$mnu = $this->data;
			//主菜单命中
			$main_nav = array();
			$current_ctrl = strtolower(Router::getController());
			$current_action = strtolower(Router::getAction());
			$side = array();

			//直接命中
			foreach($mnu as $k => $item){
				list($ctrl) = explode('/', $item[1]);
				$active = false;
				if(strtolower($ctrl) == strtolower($current_ctrl)){
					$active = true;
					$side = $item[2];
				}
				$main_nav[$k] = array($item[0], $item[1], $active, $item[2] ?: array());
			}

			//命中子菜单
			if(empty($side)){
				foreach($mnu as $k => $item){
					if(!empty($item[2])){
						foreach($item[2] as $sub_k => $subs){
							foreach($subs as $item_k => $sub_item){
								list($c, $a) = explode('/', $sub_item[1]);
								if(strtolower($c) == $current_ctrl){
									$main_nav[$k][2] = true;
									$side = $item[2];

									//main sub active
									if(strtolower($a) == $current_action){
										$main_nav[$k][3][$sub_k][$item_k][2] = true;
									}
									break 3;
								}
							}
						}
					}
				}
			}

			//子菜单active处理
			if(!empty($side)){
				foreach($side as $k => $sub_list){
					foreach($sub_list as $j => $sub_item){
						list($c, $a) = explode('/', $sub_item[1]);
						if(strtolower($c) == $current_ctrl && strtolower($a) == $current_action){
							$side[$k][$j][2] = true;
							break 2;
						}
					}
				}
			}

			//过滤掉没有权限的子菜单
			foreach($main_nav as $k => $item){
				$mnu = $item[3] ?: array();
				foreach($mnu as $cap => $subs){
					foreach($subs as $j => $sub_item){
						if($this->checkAccess($sub_item[1])){
							unset($subs[$j]);
						}
					}
					if($subs){
						$mnu[$cap] = $subs;
					}else{
						unset($mnu[$cap]);
					}
				}
				$item[3] = $mnu;
				if($item[1] && $this->checkAccess($item[1]) && $item[3]){
					$item[1] = '';
				}
				$main_nav[$k] = $item;
			}
			$this->menu_data = array(
				'side' => $side,
				'main' => $main_nav
			);
		}
		return $this->menu_data[$type];
	}

	/**
	 * 获取主菜单
	 * @param string $tpl_file
	 * @return string
	 */
	public function getMainMenu($tpl_file=''){
		$tpl_file = $tpl_file ?: $this->main_tpl_file;
		$main_nav = $this->getMenuData('main');
		ob_start();
		include $tpl_file;
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}

	/**
	 * 获取子菜单
	 * @param string $tpl_file
	 * @return string
	 */
	public function getSideMenu($tpl_file=''){
		$tpl_file = $tpl_file ?: $this->main_tpl_file;
		$side_nav = $this->getMenuData('side');
		ob_start();
		include $tpl_file;
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}
}