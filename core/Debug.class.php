<?
class Debug
{
	private static $mode;
	
	/**
	 * Get debug mode
	 * 
	 * @return boolean
	 */
	public static function getMode(){
		return static::$mode;
	}
	
	/**
	 * Set debug mode
	 * 
	 * @param boolean $mode
	 */
	public static function setMode($mode){
		static::$mode = $mode;
	}
}
?>