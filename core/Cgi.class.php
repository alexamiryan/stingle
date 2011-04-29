<?
class Cgi
{
	private static $mode;
	
	public static function getMode(){
		return static::$mode;
	}
	
	public static function setMode($mode){
		static::$mode = $mode;
	}
}
?>