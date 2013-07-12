<?php
if(!defined(DS)){
	define(DS, DIRECTORY_SEPARATOR);
}

if(!defined(RESUME_CONFIG_PATH)){
	define(RESUME_CONFIG_PATH, __DIR__.DS);
}

class ResumeTheme {
	private static $instance;
	private $themes_keys = array('green', 'base', 'simp');
	private $themes = array();

	private function __construct($config){
		$this->loadThemes();
	}

	private function loadThemes(){
		$THEME_IMG_URL = 'http://localhost/litephp/resume/static/theme/';
		foreach($this->themes_keys as $k){
			$file = RESUME_CONFIG_PATH.'themes'.DS.$k.'.inc.php';
			if(file_exists($file)){
				$theme = include $file;
				$theme['css'] = str_replace('{$THEME_IMG_URL}', $THEME_IMG_URL, $theme['css']);
				$theme['thumb'] = str_replace('{$THEME_IMG_URL}', $THEME_IMG_URL, $theme['thumb']);
				$this->themes[$k] = $theme;
			}
		}
	}

	public static function init($config=array()){
		if(!self::$instance){
			self::$instance = new self($config);
		}
		return self::$instance;
	}

	public function getAllThemes(){
		return $this->themes;
	}

	public function getAllThemesCss(){
		$css = '';
		foreach($this->themes as $theme){
			$css .= "\r\n\r\n".$theme['css'];
		}
		return $css;
	}
}