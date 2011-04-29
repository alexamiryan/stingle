<?

class InvalidArrayArgumentException extends InvalidArgumentException {
	
	/**
	 * Default exception string
	 * 
	 * @const string EXCEPTION_STRING
	 */
	const EXCEPTION_STRING = "Expecting array in arguments as defined in function doc.";
	
	/**
	 * Initialize EmptyArgumentException instance
	 * 
	 * @param string $message
	 * @param string $code
	 * @return 
	 */
	public function __construct($message = null, $code = null){
		
		/*
		 * Setting default message if message is empty
		 */
		if($message === null){
			$message = static::EXCEPTION_STRING;
		}
		
		/*
		 * Calling parent constructor to initialize instance finally
		 */
		parent::__construct($message);
	}
}

?>