<?
class SessionLogger extends Logger
{
	private $id;
	private static $prefix = "slogger";
	
	public function __construct($id = null) {
		if($id === null){
			$id = 0;
			foreach($_SESSION as $key => $value){
				preg_match("/".static::$prefix."(\d+)/", $key, $matches);
				if(!empty($matches)){
					if($matches[0] == $key and $matches[1] >= $id){
						$id = $matches[1] + 1;
					}
				}
			}
		}
		$this->id = $id;
	}
	
	public static function setPrefix($prefix){
		static::$prefix = $prefix;
	}
	
	public function log($message){
		if(!isset($_SESSION[static::$prefix . $this->id]) or !is_array($_SESSION[static::$prefix . $this->id])){
			$_SESSION[static::$prefix . $this->id] = array();
		}
		array_push($_SESSION[static::$prefix . $this->id], $message);
	}
	
	public function getLog(){
		if(isset($_SESSION[static::$prefix . $this->id]) and is_array($_SESSION[static::$prefix . $this->id])){
			return $_SESSION[static::$prefix . $this->id];
		}
		else{
			return array();
		}
	}
	
	public function clearLog(){
		$_SESSION[static::$prefix . $this->id] = array();
	}
}
?>