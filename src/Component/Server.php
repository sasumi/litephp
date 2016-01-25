<?php
namespace Lite\Component;

/**
 * 锟斤拷锟斤拷锟斤拷锟斤拷锟斤拷息锟斤拷取锟斤拷
 * User: sasumi
 */
class Server {
    public static function inWindows(){
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}