<?php
if(!defined(DS)){
	define(DS, DIRECTORY_SEPARATOR);
}

class ResumeMod {
	private static $instance;
	private $mods_keys;
	private $mods = array();
	private $path = '';

	private function __construct($config){
		$this->path = dirname(__FILE__).'/';
		$this->mods_keys = $this->getAllModsKey();
		$this->loadMods();
	}

	private function loadMods(){
		foreach($this->mods_keys as $k){
			$format_file = $this->path.$k.DS.'format.inc.php';
			$template_file = $this->path.$k.DS.'template.inc.php';


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

	private function getAllModsKey(){
		$file_list = $this->getFileList($this->path);
		return $file_list;
	}

	public static function init($config=array()){
		if(!self::$instance){
			self::$instance = new self($config);
		}
		return self::$instance;
	}

	private function getFileList($dir) {
	    $file_list = array();
	    if(false != ($handle = opendir($dir))) {
	        $i=0;
	        while(false !== ($file = readdir($handle))) {
	            //去掉"“.”、“..”以及带“.xxx”后缀的文件
	            if ($file != "." && $file != ".."&&!strpos($file,".")) {
	                $file_list[$i]=$file;
	                $i++;
	            }
	        }
	        closedir ($handle);
	    }
	    return $file_list;
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

	public function parseModValue($mod_id, $data_string){
		$mod = $this->getMod($mod_id);
		$tmp = json_decode($data_string);
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