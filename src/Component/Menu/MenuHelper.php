<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/10/8
 * Time: 18:34
 */
namespace Lite\Component\Menu;
use Lite\Core\Router;

function array_clear(&$array, $handler){
	if(!$array){
		return;
	}
	foreach($array as $k=>$item){
		$ret = $handler($item, $k);
		if(!$ret){
			unset($array[$k]);
		} else {
			$array[$k] = $ret;
		}
	}
}

const MENU_KEY_TITLE = 0;
const MENU_KEY_URI = 1;
const MENU_KEY_SUB = 2;
const MENU_KEY_ACTIVE = 3;

/**
 * 系统菜单
 * 用法：<p>
 * $mnu = new Menu($menu_data);
 * echo $mnu->getMainMenu();
 * </p>
 * 菜单配置数据$menu_data结构为：<p>
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
 * @package Lite\Component\Menu
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
	 * 检查权限，缺省不提供权限检查功能
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
			$current_ctrl = strtolower(Router::getControllerAbbr());
			$current_action = strtolower(Router::getAction());

			//权限清理
			array_clear($mnu, function(&$main_item){
				//优先过滤子菜单
				if(!empty($main_item[MENU_KEY_SUB])){
					array_clear($main_item[MENU_KEY_SUB], function($sub_list){
						array_clear($sub_list, function($sub_item){
							if($sub_item[MENU_KEY_URI] && !$this->checkAccess($sub_item[MENU_KEY_URI])){
								return false;
							}
							return $sub_item;
						});
						return $sub_list;
					});
				}

				//只有在父级菜单没有权限情况下，才需要清理菜单项
				if(!$main_item[MENU_KEY_URI] || !$this->checkAccess($main_item[MENU_KEY_URI])){
					if(!$main_item[MENU_KEY_SUB]){
						return false;
					} else {
						$main_item[MENU_KEY_URI] = ''; //子菜单有权限，父菜单没有权限，uri重置为空
					}
				}
				//父级菜单没有权限

				return $main_item;
			});

			//子菜单菜单命中检测
			$found_in_sub = false;
			array_clear($mnu, function(&$main_item)use(&$found_in_sub, $current_ctrl, $current_action){
				if($main_item[MENU_KEY_SUB] && !$found_in_sub){
					array_clear($main_item[MENU_KEY_SUB], function(&$sub_list)use(&$found_in_sub, $current_ctrl, $current_action){
						if(!$found_in_sub){
							array_clear($sub_list, function(&$sub)use(&$found_in_sub, $current_ctrl, $current_action){
								if(!$found_in_sub){
									list($c, $a) = explode('/',strtolower($sub[MENU_KEY_URI]));
									$a = $a ?: strtolower(Router::getDefaultAction());
									if($c == $current_ctrl && $a == $current_action){
										$found_in_sub = true;
										$sub[MENU_KEY_ACTIVE] = true;
									}
								}
								return $sub;
							});
						}
						return $sub_list;
					});
					if($found_in_sub){
						$main_item[MENU_KEY_ACTIVE] = true;
					}
				}
				return $main_item;
			});

			//父级菜单命中检测
			if(!$found_in_sub){
				foreach($mnu as $main_k=>$main_item){
					if($main_item[MENU_KEY_URI]){
						list($c, $a) = explode('/',strtolower($main_item[MENU_KEY_URI]));
						$a = $a ?: strtolower(Router::getDefaultAction());
						if($c == $current_ctrl && $a == $current_action){
							$mnu[$main_k][MENU_KEY_ACTIVE] = true;
							break;
						}
					}
				}
			}

			//普通CA模式命中不了，只能简单匹配C
			if(!$found_in_sub){
				array_clear($mnu, function(&$main_item)use(&$found_in_sub, $current_ctrl, $current_action){
					if($main_item[MENU_KEY_SUB] && !$found_in_sub){
						array_clear($main_item[MENU_KEY_SUB], function(&$sub_list)use(&$found_in_sub, $current_ctrl, $current_action){
							if(!$found_in_sub){
								array_clear($sub_list, function(&$sub)use(&$found_in_sub, $current_ctrl, $current_action){
									if(!$found_in_sub){
										list($c, $a) = explode('/',strtolower($sub[MENU_KEY_URI]));
										if($c == $current_ctrl){
											$found_in_sub = true;
											$sub[MENU_KEY_ACTIVE] = true;
										}
									}
									return $sub;
								});
							}
							return $sub_list;
						});
						if($found_in_sub){
							$main_item[MENU_KEY_ACTIVE] = true;
						}
					}
					return $main_item;
				});

				//父级菜单命中检测
				if(!$found_in_sub){
					foreach($mnu as $main_k=>$main_item){
						if($main_item[MENU_KEY_URI]){
							list($c, $a) = explode('/',strtolower($main_item[MENU_KEY_URI]));
							$a = $a ?: strtolower(Router::getDefaultAction());
							if($c == $current_ctrl){
								$mnu[$main_k][MENU_KEY_ACTIVE] = true;
								break;
							}
						}
					}
				}
			}

			//析出子菜单数据
			$side = array();
			foreach($mnu as $main_item){
				if($main_item[MENU_KEY_ACTIVE]){
					$side = $main_item[MENU_KEY_SUB];
					break;
				}
			}

			$this->menu_data = array(
				'main' => $mnu,
				'side' => $side
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
		$tpl_file = $tpl_file ?: $this->side_tpl_file;
		$side_nav = $this->getMenuData('side');
		ob_start();
		include $tpl_file;
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}
}