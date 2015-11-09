<?php
namespace www\controller;

use Captcha;
use Lite\Component\ParallelCurl;
use Lite\Core\Config;
use Lite\Component\Request;
use Lite\Core\Result;
use function Lite\func\dump;
use www\Auth;
use www\model\User;

class IndexController extends BaseController {
	public function index(){
		set_time_limit(0);
		$fp = fopen('c:/pss.txt', 'a+');
			$st = microtime(true);
			ParallelCurl::addRequest(array(
				'url' => 'http://www.baidu.com',
			), function($data, $req){
				dump($req['url'], strlen($data));
			});
			ParallelCurl::addRequest(array(
				'url' => 'http://www.baidu.com',
			), function($data, $req){
				dump($req['url'], strlen($data));
			});
			ParallelCurl::addRequest(array(
				'url' => 'http://www.baidu.com',
			), function($data, $req){
				dump($req['url'], strlen($data));
			});
			ParallelCurl::send();

			$off = microtime(true) - $st;
			fwrite($fp, $off."\n");
		fclose($fp);
		dump('x',1);
	}

	public function index2(){
		set_time_limit(0);
		$fp = fopen('c:/sss.txt', 'a+');
			$st = microtime(true);
			$this->aa();
			$this->aa();
			$this->aa();
			$off = microtime(true) - $st;
			fwrite($fp, $off."\n");
		fclose($fp);
	}

	public function aa(){
		$ch = curl_init();
		$req = array(
			'url' => 'http://www.baidu.com',
			'data' => null,
			'timeout' => 10,
			'user_agent' => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
			'method' => 'get',
			'max_redirect' => 2, //最大302次数
		);
		curl_setopt($ch, CURLOPT_URL, $req['url']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $req['timeout']);
		curl_setopt($ch, CURLOPT_USERAGENT, $req['user_agent']);
		if($req['max_redirect']){
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $req['max_redirect']);
		}

		$content = curl_exec($ch);
	}

	public function login($get, $post) {
		$access = Auth::instance();
		$use_captcha = Config::get('app/login/use_captcha');

		if($access->isLogin()){
			$this->jumpTo('user');
		}

		if($post){
			if($use_captcha){
				if(empty($post['captcha'])){
					return new Result('请输入验证码', false, 'captcha');
				}
				if(strtolower($post['captcha']) != strtolower(Auth::getCaptcha())){
					Auth::setCaptcha('');
					return new Result('您输入的验证码不正确，请重新输入', false, 'captcha');
				}
				Auth::setCaptcha('');
			}

			if(!User::find('name=?',$post['name'])->count()){
				return new Result('当前用户名未登记');
			}

			$user_info = User::validateUser($post['name'], $post['password']);
			if($user_info){
				if($post['auto_login']){
					$access->setCookieExpired(Config::get('app/login/expired'));
				} else {
					$access->setCookieExpired(0);
				}
				Auth::instance()->login($user_info);
				return new Result('登录成功', true, null, $this->getUrl());
			}
			return new Result('用户名或密码错误');
		}

		return array(
			'use_captcha' => $use_captcha
		);
	}

	public function logout() {
		Auth::instance()->logout();
		return new Result('退出成功', true, null, $this->getUrl('index/login'));
	}

	public function captcha(){
		$cfg = Config::get('app/login/captcha');
		$cap = new Captcha();
		$cap->width  = $cfg['width'];
		$cap->height = $cfg['height'];
		$cap->maxWordLength = $cfg['words'];
		$cap->minWordLength = $cfg['words'];
		$cap->fontSize      = $cfg['font_size'];
		$text = null;
		$cap->CreateImage($text);
		Auth::setCaptcha($text);
		die;
	}

	public function uploadImage(){
		if ($this->isPost()) {
			$file = $_FILES['file'];
			if (empty($file['name'])) {
				return new Result('请选择文件', false);
			}

			$ext = $this->getFileExt($file['type']);

			if (!$ext) {
				return new Result('文件类型不符合，请重新选择文件上传', false);
			}

			$rsp = Request::postFiles(Config::get('upload/host'), array(), array('file'=>$file['tmp_name']));
			$result = json_decode($rsp, true);
			if($result['code'] == '0'){
				return new Result('图片上传成功', null, array(
					'src' => Config::get('upload/url').$result['data'],
					'value' => $result['data'],
				));
			}
			return new Result('图片上传失败，请稍候重试', null, $rsp);
		}
		return array(
			'UPLOAD_PAGE_URL' => $this->getUrl('index/uploadImage')
		);
	}

	/**
	 * get file extension
	 *
	 * @param $mime_info
	 * @return mixed
	 */
	private function getFileExt($mime_info)
	{
		$map = array(
			'image/gif' => 'gif',
			'image/jpeg' => 'jpg',
			'image/png' => 'png'
		);
		return $map[$mime_info];
	}
}
