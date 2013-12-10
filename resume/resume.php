<?php
include 'config/app.inc.php';

if(ACTION == 'index'){
	include tpl('resume_guide.php');
	die;
}

function getResume($resume_id){
	$user_id = 1;
	$current_resume = array();

	if($resume_id){
		$current_resume = Table_Resume::find("id=? AND user_id=?", $resume_id, $user_id)->asArray()->getOne();
		if($current_resume['id']){
			$template_item_config = Helper_ResumeMod::init()->getAllMods();

			$datas = Table_ResumeData::find('resume_id=?', $resume_id)->order('order DESC')->asArray()->getAll();
			$tmp = array(
				'cover' => array(
					'item_type' => 'cover',
					'item_title' => '封面',
					'visible' => $current_resume['front_cover']
				),
				'title' => array(
					'item_type' => 'title',
					'item_title' => '标题',
					'visible' => true,
					'item_value' => $current_resume['title']
				)
			);

			foreach($datas as $item){
				//$item['item_value'] = json_decode($item['item_value']);

				$item_type = $item['item_type'];
				$tic = $template_item_config[$item_type];

				//可合并实例
				if($tic['merge_instance']){
					if(!$tmp[$item_type]){
						$tmp[$item_type] = $item;
						$tmp[$item_type]['item_value'] = array();
					}
					$tmp[$item_type]['item_value'][] = $item['item_value'];
				}

				//多实例
				else if($tic['multi_instance']){
					$tmp[$item_type.'_'.$this->guid()] = $item;
				}

				//单例
				else {
					$tmp[$item_type] = $item;
				}
			}
			$current_resume['resume_data'] = $tmp;
		}
	}
	return $current_resume;
}

$_guid = 1;
function guid(){
	return $_guid++;
}

function getDefaultResume(){
	return array(
		'id' => 0,
		'theme' => 'green',
		'title' => '简历',
		'resume_data' => array(
			'cover' => array(
				'item_type' => 'cover',
				'item_title' => '封面',
				'item_value' => '',
				'visible' => 1
			),
			'info' => array(
				'item_type' => 'cover',
				'item_title' => '个人资料',
				'item_value' => '个人资料',
				'visible' => 1
			)

		)
	);
}

function actionCreate(){
	$d = mktime(0,0,0,date('m'), date('d'), date('Y')-10);

	$resume_id = $this->_context->get('id');
	if($resume_id){
		$resume = $this->getResume($resume_id);
	} else {
		$resume = $this->getDefaultResume();
	}

	$user = array(
		'id' => 1,
		'name' => 'sasumi',
		'email' => 'cnsasumi@gmail.com',
		'mobile' => '234234234'
	);

	$all_mods = Helper_ResumeMod::init()->getAllMods();

	foreach($resume['resume_data'] as $guid=>$mod){
		$cur_mod = $all_mods[$mod['item_type']];
		$cur_mod['default_title'] = $cur_mod['title'];
		unset($cur_mod['title']);	//去除配置title，避免歧义
		$resume['resume_data'][$guid] = array_merge($cur_mod, $mod);
	}

	$template_css = Helper_ResumeMod::init()->getAllTemplateCss();
	$themes = Helper_ResumeTheme::init()->getAllThemes();
	$theme_css = Helper_ResumeTheme::init()->getAllThemesCss();
}

/**
 * 获取模块配置
 * @param  string $id
 * @return array
 */
function getModConfig($id){
	$file = Q::ini('app_config/TEMPLATE_CONFIG_DIR')."/$id/format.inc.php";
	if(file_exists($file)){
		return include $file;
	}
}

function getModAllThemeCss($id){
	$themes = $this->getModAllThemes($id);
	if(!$themes){
		return '';
	}
	$css = array();
	foreach($themes as $k=>$theme){
		$css[] = $theme['css'];
	}
	return implode("\r\n", $css);
}

/**
 * 获取模块所有主题配置
 * @param  string $id
 * @return array
 */
function getModAllThemes($id){
	$file = Q::ini('app_config/TEMPLATE_CONFIG_DIR')."/$id/theme.inc.php";
	if(file_exists($file)){
		return include $file;
	}
}

include tpl();

