<?php
/**
 * TODO connect with db instances
 *
 *
 */
class MySqlException extends Exception {

	protected $error_code;

	/**
	 *
	 * @param $message
	 * @param $code
	 * @return unknown_type
	 */
	public function __construct($message, $code) {
		$this->error_code = $code;
		parent::__construct($message, 0);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->error_code}]: {$this->message}\n";
	}

	public function getDBCode(){
		return $this->error_code;
	}
}

