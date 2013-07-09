<?php
if(!defined(DS)){
	define(DS, DIRECTORY_SEPARATOR);
}

class ResumeMods {
	private static $instance;
	private $mods_keys = array('career', 'education', 'info', 'intro', 'skill', 'title');
	private $mods = array();

	private function __construct($config){
		$this->loadMods();
	}

	private function loadMods(){
		foreach($this->mods_keys as $k){
			$format_file = __DIR__.DS.$k.DS.'format.inc.php';
			$theme_file = __DIR__.DS.$k.DS.'theme.inc.php';

			if(file_exists($format_file)){
				$mod = include $format_file;
				if(file_exists($theme_file)){
					$themes = include $theme_file;
					if($themes && count($themes)){
						$mod['themes'] = $themes;
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

	public function getModAllThemeCss($mod_id){
		$mod = $this->getMod($mod_id);
		$css = '';
		if($mod['themes']){
			foreach($mod['themes'] as $theme){
				$css .= "\r\n".$theme['css'];
			}
		}
		return $css;
	}

	public function getAllThemeCss(){
		$css = '';
		foreach($this->mods as $mod_id=>$mod){
			$css .= "\r\n\r\n".$this->getModAllThemeCss($mod_id);
		}
		return $css;
	}
}