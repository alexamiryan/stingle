<?php

class EmptyArgumentException extends InvalidArgumentException {
	
	/**
	 * Default exception string
	 * 
	 * @const string EXCEPTION_STRING
	 */
	const EXCEPTION_STRING = "Passed argument %s to function is empty.";
	
	/**
	 * Initialize EmptyArgumentException instance
	 * 
	 * @param string $argumentName
	 * @return 
	 */
	public function __construct($argumentName = null){
		
		/*
		 * Setting passed argument name in exception string if
		 * it is not null
		 */
		if($argumentName !== null){
			$message = sprintf(self::EXCEPTION_STRING, "`".$argumentName."`");
		}
		else{
			$message = sprintf(self::EXCEPTION_STRING, "");
		}
		
		/*
		 * Calling parent constructor to initialize instance finally
		 */
		parent::__construct($message);
	}
}
?>