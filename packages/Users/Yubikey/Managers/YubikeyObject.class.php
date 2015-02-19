<?php

/**
 * 
 *	Yubikey Object
 */
class YubikeyObject
{
	/**
	 * yubikey Id
	 * @var Integer
	 */
	public $id;
	
	/**
	 * yubikey
	 * @var string
	 */
	public $key;
	
	/**
	 * Yubikey description
	 * @var String
	 */
	public $description;
	
	/**
	 * Yubikey status (enabled/disabled)
	 * @var Integer 1|0
	 */
	public $status;
}