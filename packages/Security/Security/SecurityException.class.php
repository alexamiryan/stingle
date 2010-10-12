<?php

class SecurityException extends Exception {
	
	/**v
	 * Default exception string
	 * 
	 * @const string EXCEPTION_STRING
	 */
	const EXCEPTION_STRING = "Security exception: %s";
	
	private $usermessage = "";
	private $blockmessage = "";
	
	/**
	 * Initialize EmptyArgumentException instance
	 * 
	 * @param string $argumentName
	 * @return 
	 */
	public function __construct($securityMessage = null){
		
		/*
		 * Setting passed argument name in exception string if
		 * it is not null
		 */
		if($securityMessage !== null){
			$message = sprintf(static::EXCEPTION_STRING, "`".$securityMessage."`");
		}
		else{
			$message = sprintf(static::EXCEPTION_STRING, "");
		}
		
		/*
		 * Calling parent constructor to initialize instance finally
		 */
		parent::__construct($message);
	}
	
	public function setUserMessage($usermessage) {
		$this->usermessage = $usermessage;
	}
	
	public function getUserMessage() {
		return $this->usermessage;
	}
	
	public function setBlockMessage($blockmessage) {
		$this->blockmessage = $blockmessage;
	}
	
	public function getBlockMessage() {
		return $this->blockmessage;
	}
}
?>