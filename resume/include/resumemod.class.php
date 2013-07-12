<?php
if(!defined(DS)){
	define(DS, DIRECTORY_SEPARATOR);
}

if(!defined(RESUME_CONFIG_PATH)){
	define(RESUME_CONFIG_PATH, __DIR__.DS);
}

class ResumeMod {
	private static $instance;
	private $mods_keys = array('conver', 'career', 'education', 'info', 'intro', 'skill', 'title');
	private $mods = array();

	private function __construct($config){
		$this->loadMods();
	}

	private function loadMods(){
		foreach($this->mods_keys as $k){
			$format_file = RESUME_CONFIG_PATH.'mods'.DS.$k.DS.'format.inc.php';
			$template_file = RESUME_CONFIG_PATH.'mods'.DS.$k.DS.'template.inc.php';

			if(file_exists($format_file)){
				$mod = include $format_file;
				if(file_exists($template_file)){
					$templates = include $template_file;
					if($templates && count($templates)){
						$mod['templates'] = $templates;
					}
				}
				$this->mods[$k] = $mod;
			}
		}
	}

	public static function init($config=array()){
		if(!self::$instance){
			self::$instance = new self($config);
		}
		return self::$instance;
	}

	public function getAllMods(){
		return $this->mods;
	}

	public function getMod($mod_id){
		return $this->mods[$mod_id];
	}

	public function getModAllTemplateCss($mod_id){
		$mod = $this->getMod($mod_id);
		$css = '';
		if($mod['templates']){
			foreach($mod['templates'] as $template){
				$css .= "\r\n".$template['css'];
			}
		}
		return $css;
	}

	public function getAllTemplates(){
	}

	public function getAllTemplateCss(){
		$css = '';
		foreach($this->mods as $mod_id=>$mod){
			$css .= "\r\n\r\n".$this->getModAllTemplateCss($mod_id);
		}
		return $css;
	}
}