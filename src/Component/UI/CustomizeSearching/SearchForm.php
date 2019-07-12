<?php

namespace Lite\Component\UI\CustomizeSearching;

/**
 * 定制化搜索表单
 * <p>用法：在搜索<FORM>HTML中执行,即可输出定制化搜索条件：
 * $sf = SearchForm::instance(null, __FILE__);
 * $sf->addQuery(new SearchQuery('kw', '关键字', '<input type="search" name="kw" value="'.ha($get['kw']).'">', true));
 * $sf->render();
 * </p>
 * @package Lite\Component\UI\CustomizeSearching
 */
class SearchForm{
	public static $GET_KEY = '_SFGK_';
	public static $SESSION_KEY = '_SFSK_';
	public static $template = '';
	
	private $config;
	
	/** @var SearchQuery[] $query_list */
	private $query_list = [];
	
	/** @var array $active_fields */
	private $active_fields = [];
	
	/**
	 * SearchForm constructor.
	 * @param $config
	 */
	private function __construct($config){
		$this->config = $config;
		if(!headers_sent()){
			session_start();
		}
		$p = null;
		if(isset($_GET[self::$GET_KEY])){
			$p = explode(',', $_GET[self::$GET_KEY]);
			$_SESSION[self::$SESSION_KEY] = $p;
		} else if(isset($_SESSION[self::$SESSION_KEY])){
			$p = $_SESSION[self::$SESSION_KEY];
		}
		$this->active_fields = $p;
	}
	
	/**
	 * create instance
	 * @param array $config
	 * @param string $identify
	 * @return static
	 */
	public static function instance($config = [], $identify = 'default'){
		static $instances = [];
		if(!$instances[$identify]){
			$instances[$identify] = new self($config);
		}
		return $instances[$identify];
	}
	
	/**
	 * add query
	 * @param SearchQuery $query
	 */
	public function addQuery(SearchQuery $query){
		$active = isset($this->active_fields) ? in_array($query->id, $this->active_fields) : $query->default;
		$query->active = $active;
		$this->query_list[$query->id] = $query;
	}
	
	public function render(){
		$tpl = static::$template ?: __DIR__.'/tpl.php';
		$query_list = $this->query_list;
		include $tpl;
	}
}