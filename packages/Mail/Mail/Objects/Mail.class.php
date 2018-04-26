<?php

/**
 * Mail Class
 * @author Aram Gevorgyan
 *
 */
class Mail {

	/**
	 * 
	 * Send to array
	 * @example array( "to@domainName.com", "toName")
	 * @access protected
	 * @var Array
	 */
	protected $to = array();

	/**
	 * Reply mail to
	 * @example array( "to@domainName.com", "toName")
	 * @access protected
	 * @var Array
	 */
	protected $replyTo = array();
	
	/**
	 * Custom headers
	 * @var Array 
	 */
	protected $customHeaders = array();

	/**
	 * From email address
	 * @example "fromMail@domainName.com"
	 * @access public
	 * @var string
	 */
	public $from = '';

	/**
	 * From email name
	 * @example Name
	 * @access public
	 * @var string
	 */
	public $fromName = '';

	/**
	 * Sets the HTML body
	 * @access public
	 * @var string
	 */
	public $htmlBody = '';
	
	/**
	 * Sets the plain text body
	 * @access public
	 * @var string
	 */
	public $textBody = '';
	
	/**
	 * Charset of the email
	 * 
	 * @var String
	 */
	public $charSet = 'utf-8';

	/**
	 * Sets the Subject of the mail.
	 * @example "subject of mail"
	 * @access public
	 * @var string
	 */
	public $subject = '';

	/**
	 * Add To address and optional name of to address
	 * @param String $address
	 * @param String $name
	 * @example addTo("to@domain.com", "toName");
	 * @access public
	 * @throws MailException
	 * @return Boolean
	 */
	public function addTo($address, $name = '', $checkValidity = false) {
		$address = trim($address);
		$name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
		if ($checkValidity and ! valid_email($address)) {
			throw new MailException("to mail adress is not valid!");
		}
		if (!array_key_exists(strtolower($address), $this->to)) {
			array_push($this->to, array('address' => $address, 'name' => $name));
			return true;
		}
		return false;
	}

	/**
	 * Add replyTo address, optional name of reply-to address
	 * @param String $address
	 * @param String $name
	 * @example addReplyTo("replyto@domain.com", "replytoName");
	 * @throws MailException
	 * @return Boolean
	 */
	public function addReplyTo($address, $name = '', $checkValidity = false) {
		$address = trim($address);
		$name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
		if ($checkValidity and ! valid_email($address)) {
			throw new MailException("Reply to adress is not valid!");
		}
		if (!array_key_exists(strtolower($address), $this->replyTo)) {
			array_push($this->replyTo, array('address' => $address, 'name' => $name));
			return true;
		}
		return false;
	}

	/**
	 * Add custom header
	 * 
	 * @param string $name
	 * @param string $value
	 * @return boolean
	 */
	public function addCustomHeader($name, $value){
		if(!empty($name)){
			array_push($this->customHeaders, array('name' => $name, 'value'=>$value));
			return true;
		}
		return false;
	}
	
	/**
	 * Clear custom headers
	 */
	public function clearCustomHeaders(){
		$this->customHeaders = array();
	}
	
	/**
	 * Get Custom headers array
	 * 
	 * @return Array
	 */
	public function getCustomHeaders(){
		return $this->customHeaders;
	}
	
	/**
	 * Get To addresses array
	 * @access public
	 * @return Array     
	 */
	public function getToAddresses() {
		return $this->to;
	}

	/**
	 * Get ReplyTo addresses array
	 * @access public
	 * @return Array
	 */
	public function getReplyToAddresses() {
		return $this->replyTo;
	}

	/**
	 * Remove all To addresses
	 * @access public
	 */
	public function clearAddresses() {
		$this->to = array();
	}

	/**
	 * Remove all replyTos addresses
	 * @access public
	 */
	public function clearReplyTos() {
		$this->replyTo = array();
	}
}
