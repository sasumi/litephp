<?php
namespace SimpleProject\Controller;

use Lite\Core\Controller;
use function Lite\func\dump;
use SimpleProject\Model\User;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/5/4
 * Time: 20:02
 */
class IndexController extends Controller {
	public function test2(){
		$tm = microtime(true);
		$connomains = array(
			"http://css.erp.com/",
			"http://ds.erp.com/",
			"http://www.erp.com/",
		);
		foreach($connomains as $d) {
			$res[] = file_get_contents($d);
		}
		var_dump(microtime(true) - $tm);
	}

	public function index(){
		$user = new User();
		echo $user->name;
		echo $user->address;
		echo $user->description;
		$tm = microtime(true);
		dump('x');
	}
}

