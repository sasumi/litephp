<?php
namespace SimpleProject\Controller;

use Exception;
use Lite\Core\Controller;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/5/4
 * Time: 20:02
 */
class IndexController extends Controller
{

	public function test2(){
		$tm = microtime(true);
		$connomains = array(
			"http://css.erp.com/",
			"http://ds.erp.com/",
			"http://www.erp.com/",
		);
		foreach($connomains as $d){
			$res[] = file_get_contents($d);
		}
		var_dump(microtime(true)-$tm);
	}

	public function index() {
		$tm = microtime(true);
		$connomains = array(
			"http://css.erp.com/",
			"http://ds.erp.com/",
			"http://www.erp.com/",
		);
		$mh = curl_multi_init();
		$conn = array();
		$res = array();
		foreach ($connomains as $i => $url) {
			$conn[$i] = curl_init($url);
			curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
			curl_multi_add_handle($mh, $conn[$i]);
		}

		do { $n=curl_multi_exec($mh,$active); } while ($active);
		foreach ($connomains as $i => $url) {
			$res[$i]=curl_multi_getcontent($conn[$i]);
			curl_close($conn[$i]);
		}

		var_dump(microtime(true)-$tm);

		dump($res, 1);
	}
}

