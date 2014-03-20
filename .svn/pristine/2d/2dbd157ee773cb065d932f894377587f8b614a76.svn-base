<?php
class LException extends Exception {
	protected $message;
	protected $code;
	private $trace_info;
	private $data;

	public function __construct($message=null, $code=0, $data=null){
		parent::__construct($message, 0);
		$this->trace_info = debug_backtrace();
		$this->data = $data;
	}

	public function getData(){
		return $this->data;
	}

	public function setData($data){
		$this->data = $data;
	}

	public function dump(){
		dump($this->message, $this->data, $this->trace_info);
	}

	public function __toString(){
		$html = '<pre>';
		$html .= "ERROR HAPPANDS\n";
		$html .= "====================================\n";
		$html .= $this->message."\n";
		$html .= "====================================\n";
		$html .= print_r($this->data, true);
		foreach($this->trace_info as $k=>$t){
			$html .= $k.'.'.$t['file'].' [line:'.$t['line']."]\n";
		}

		if($this->data){
			$html .= "\n\nPARAMS:\n---------------------------------------\n";
			$html .= print_r($this->getData(), true);
		}
		return $html;
	}
}