<?php

namespace Lite\Core;

class Request implements IoCPrototype{
	private $get;
	private $post;
	private $files;
	private $cookie;

	public function get($key = null){
		return $key ? (isset($_GET[$key]) ? $_GET[$key] : null) : $_GET;
	}

	public function post($key = null){
		return $key ? (isset($_POST[$key]) ? $_POST[$key] : null) : $_POST;
	}

	public static function instance(){
		static $req;
		if(!$req){
			$req = new self();
		}
		$req->cookie = $_COOKIE;
		$req->get = $_GET;
		$req->post = $_POST;
		return $req;
	}
}
