<?php
class SiteMode
{
	const MODE_PRODUCTION 	= 1;
	const MODE_DEVELOPMENT 	= 2;
	
	private static $mode;
	
	/**
	 * Get site mode
	 * 
	 * @return integer
	 */
	public static function get(){
		return static::$mode;
	}
	
	/**
	 * Set site mode
	 * 
	 * @param integer $mode
	 */
	public static function set($mode){
		static::$mode = $mode;
	}
}
