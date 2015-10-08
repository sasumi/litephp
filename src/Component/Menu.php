<?php
/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/10/8
 * Time: 18:34
 */
namespace Lite\Component;

use Lite\Core\Config;
use Lite\Core\Router;

class Menu {
	private $data;
	private $main_menu;
	private $side_menu;

	public function __construct(array $data){
		$this->data = $data;
	}

	/**
	 * 产生需要的菜单数据
	 * @param array $mnu
	 * @return array
	 */
	private static function generateSystemMenu(array $mnu){
		//主菜单命中
		$main_nav = array();
		$current_ctrl = strtolower(Router::getController());
		$current_action = strtolower(Router::getAction());
		$side = array();

		//直接命中
		foreach($mnu as $k=>$item){
			list($ctrl) = explode('/',$item[1]);
			$active = false;
			if(strtolower($ctrl) == strtolower($current_ctrl)){
				$active = true;
				$side = $item[2];
			}
			$main_nav[$k] = array($item[0], $item[1], $active, $item[2] ?: array());
		}

		//命中子菜单
		if(empty($side)){
			foreach($mnu as $k=>$item){
				if(!empty($item[2])){
					foreach($item[2] as $sub_k=>$subs){
						foreach($subs as $item_k=>$sub_item){
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
			foreach($side as $k=>$sub_list){
				foreach($sub_list as $j=>$sub_item){
					list($c, $a) = explode('/',$sub_item[1]);
					if(strtolower($c) == $current_ctrl && strtolower($a) == $current_action){
						$side[$k][$j][2] = true;
						break 2;
					}
				}
			}
		}

		//过滤掉没有权限的子菜单
		foreach($main_nav as $k=>$item){
			$mnu = $item[3] ?: array();
			foreach($mnu as $cap=>$subs){
				foreach($subs as $j=>$sub_item){
					if(Access::getUrlAccessFlag($sub_item[1])){
						unset($subs[$j]);
					}
				}
				if($subs){
					$mnu[$cap] = $subs;
				} else {
					unset($mnu[$cap]);
				}
			}
			$item[3] = $mnu;
			if($item[1] && Access::getUrlAccessFlag($item[1]) && $item[3]){
				$item[1] = '';
			}
			$main_nav[$k] = $item;
		}
		return array(
			'main' => $main_nav,
			'side' => $side,
		);
	}

	public static function generateSystemMainMenu(){
		$data = self::generateSystemMenu(Config::get('nav'));
		$viewer = new self(array('main_nav'=>$data['main']));
		return $viewer->render('inc/main_mnu.php', true);
	}

	public static function generateSystemSideMenu(){
		$data = self::generateSystemMenu(Config::get('nav'));
		$viewer = new self(array('side_nav'=>$data['side']));
		return $viewer->render('inc/side_mnu.php', true);
	}
}