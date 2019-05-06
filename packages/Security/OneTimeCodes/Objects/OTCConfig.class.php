<?php
class OTCConfig extends Config {
	
	/**
	 * Assoc array of variables to store into code
	 * @var array
	 */
	public $paramsArray = array();
	
	/**
	 * Is code for multiple use or not
	 * Default in no, it means code will 
	 * be for one time use
	 *  
	 * @var boolean
	 */
	public $multiUse = false;
	
	/**
	 * Number of times multiple usage code can be used.
	 * Default is unlimited.
	 * @var integer|null
	 */
	public $usageLimit = null; 
	
	/**
	 * Time in seconds starting from now of code validity.
	 * Default is unlimited
	 * @var integer|null
	 */
	public $validityTime = null;
}
