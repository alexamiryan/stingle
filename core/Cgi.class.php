<?
class Cgi
{
	private static $mode;
	
	/**
	 * Get CGI mode
	 * @return boolean
	 */
	public static function getMode(){
		return static::$mode;
	}
	
	/**
	 * Set CGI mode
	 * 
	 * @param boolean $mode
	 */
	public static function setMode($mode){
		static::$mode = $mode;
	}
}
?>