<?php
/**
 * Created by PhpStorm.
 * User: Sasumi
 * Date: 2016/1/15
 * Time: 14:07
 */
namespace Lite\REST;

abstract class Resource {
	abstract function index(array $options);
	abstract function info($id);
	abstract function update($id, $data);
	abstract function delete($id);
}