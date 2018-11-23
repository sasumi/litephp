<?php
namespace Lite\Component;

/**
 * 服务器环境集成类
 * User: sasumi
 */
class Server {
    public static function inWindows(){
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}