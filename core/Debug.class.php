<?php
class Debug
{
	private static $mode = false;
	
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
