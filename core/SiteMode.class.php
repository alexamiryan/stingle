<?
class SiteMode
{
	const MODE_PRODUCTION 	= 1;
	const MODE_DEVELOPMENT 	= 2;
	
	private static $mode;
	
	public static function get(){
		return static::$mode;
	}
	
	public static function set($mode){
		static::$mode = $mode;
	}
}
?>