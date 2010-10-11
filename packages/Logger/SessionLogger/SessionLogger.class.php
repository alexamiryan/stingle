<?php

class SessionLogger extends Logger
{
	private $id;
	private static $prefix = "slogger";
	
	public function __construct($id = null) {
		if($id === null){
			$id = 0;
			foreach($_SESSION as $key => $value){
				preg_match("/".self::$prefix."(\d+)/", $key, $matches);
				if($matches[0] == $key and $matches[1] >= $id){
					$id = $matches[1] + 1;
				}
			}
		}
		$this->id = $id;
	}
	
	public static function setPrefix($prefix){
		self::$prefix = $prefix;
	}
	
	public function log($message){
		if(!is_array($_SESSION[self::$prefix . $this->id])){
			$_SESSION[self::$prefix . $this->id] = array();
		}
		array_push($_SESSION[self::$prefix . $this->id], $message);
	}
	
	public function getLog(){
		if(is_array($_SESSION[self::$prefix . $this->id])){
			return $_SESSION[self::$prefix . $this->id];
		}
		else{
			return array();
		}
	}
	
	public function clearLog(){
		$_SESSION[self::$prefix . $this->id] = array();
	}
}

?>