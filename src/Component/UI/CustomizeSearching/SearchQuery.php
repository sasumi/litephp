<?php

namespace Lite\Component\UI\CustomizeSearching;

class SearchQuery{
	public $id;
	public $title;
	public $html;
	public $default;
	public $active;
	
	public function __construct($id, $title, $html, $default = false){
		$this->id = $id;
		$this->title = $title;
		$this->html = $html;
		$this->default = $default;
	}
}