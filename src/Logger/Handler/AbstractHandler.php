<?php
namespace Lite\Logger\Handler;

/**
 * User: sasumi
 * Date: 2015/4/15
 * Time: 13:29
 */
abstract class AbstractHandler implements InterfaceHandler {
	/**
	 * recommended to fill this method
	 * read message list
	 * @param $start
	 * @param $count
	 * @return array
	 */
	public function read($start, $count){

	}
}