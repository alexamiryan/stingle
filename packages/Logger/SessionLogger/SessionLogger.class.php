<?
class SessionLogger extends Logger
{
	private static $prefix = "slogger";
	
	public static function setPrefix($prefix){
		static::$prefix = $prefix;
	}
	
	public function log($name, $value){
		if(!isset($_SESSION[static::$prefix . $name]) or !is_array($_SESSION[static::$prefix . $name])){
			$_SESSION[static::$prefix . $name] = array();
		}
		array_push($_SESSION[static::$prefix . $name], $value);
	}
	
	public function getLog($name){
		if(isset($_SESSION[static::$prefix . $name]) and is_array($_SESSION[static::$prefix . $name])){
			return $_SESSION[static::$prefix . $name];
		}
		else{
			return array();
		}
	}
	
	public function clearLog($name){
		$_SESSION[static::$prefix . $name] = array();
	}
}
?>