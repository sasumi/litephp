<?php
namespace Lite\Logger\Handler;

/**
 * User: sasumi
 * Date: 2015/4/15
 * Time: 13:28
 */
Interface InterfaceHandler {
	/**
	 * write message list
	 * @param array $messages
	 * @return boolean
	 */
	public function write(array $messages);
}