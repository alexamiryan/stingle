<?
class Debug
{
	private static $mode;
	
	public static function getMode(){
		return self::$mode;
	}
	
	public static function setMode($mode){
		self::$mode = $mode;
	}
}
?>