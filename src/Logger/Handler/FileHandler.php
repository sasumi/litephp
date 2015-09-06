<?php
namespace Lite\Logger\Handler;
use Lite\Logger\Message\AbstractMessage;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/4/15
 * Time: 13:16
 */
class FileHandler extends AbstractHandler{
	private $file_prefix = '';
	private $file_midfix = '';
	private $file_path = '';
	private $file_name = '';
	private $file_max_size = 0;
	private $file_count = 1;
	private $message_separator = "\n";

	public function setMessageSeparator($sep){
		$this->message_separator = $sep;
	}

	public function setFileName($file_name){
		$this->file_count = 1;
		$this->file_name = $file_name;
	}

	public function setFilePath($file_path){
		$this->file_path = $file_path;
	}

	public function setFileMaxSize($size){
		$this->file_max_size = $size;
	}

	public function setFilePrefix($prefix){
		$this->file_prefix = $prefix;
	}

	public function setFileCount($file_count){
		$this->file_count = $file_count;
	}

	public function write(array $messages){
		$file = null;

		//write single file
		if($this->file_name){
			$file = $this->file_name;
		}

		//write multi files
		else if($this->file_path){
			$file = $this->getWritableFile();
		}

		if($file){
			$str = '';
			foreach($messages as $msg){
				/** @var AbstractMessage $msg */
				$str .= $msg->serialize().$this->message_separator;
			}
			$fp = fopen($file, 'a+');
			if($this->file_max_size && filesize($this->file_name) >= $this->file_max_size){
				return false;
			}
			fwrite($fp, $str);
			fclose($fp);
			return true;
		}
		return false;
	}

	private function getWritableFile(){
		return '';
	}
}