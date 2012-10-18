<?
/**
 * Mail Class
 * @author Aram Gevorgyan
 *
 */
class Mail{
	
	/**
	 * 
	 * Send to array
	 * @example array( "to@domainName.com", "toName")
	 * @access protected
	 * @var Array
	 */
	protected $to			 = array();
	
	/**
	 * Reply mail to
	 * @example array( "to@domainName.com", "toName")
	 * @access protected
	 * @var Array
	 */
	protected $replyTo 		 = array();
	
	/**
     * From email address
     * @example "fromMail@domainName.com"
     * @access public
     * @var string
     */
  	public $from              = '';

  	/**
   	 * From email name
   	 * @example Name
   	 * @access public
     * @var string
     */
  	public $fromName          = '';
	
	/**
   	 * Sets the Body type.  This can be either an HTML or text body.
   	 * If HTML then run IsHTML(true).
   	 * @example "body of mail."
   	 * @access public
   	 * @var string
   	 */
  	public $body              = '';
	
	/**
	 * Sets the Subject of the mail.
	 * @example "subject of mail"
	 * @access public
	 * @var string
	 */
  	public $subject           = '';
	
	/**
	 * Sets the Content-type of the message.
	 * @example 'text/html'
	 * @access public
	 * @var string
	 */
	public $contentType       = 'text/plain';
	
    /**
     * Add To address and optional name of to address
     * @param String $address
     * @param String $name
     * @example addTo("to@domain.com", "toName");
     * @access public
     * @throws MailException
     * @return Boolean
     */
    public function addTo($address, $name = ''){
    	$address = trim($address);
   		$name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
    	if (!valid_email($address)) {
    		throw new MailException("to mail adress is not valid!");
    	}
   	    if (!array_key_exists(strtolower($address), $this->to)) {
        	$this->to[strtolower($address)] = array($address, $name);
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
    public function addReplyTo($address, $name = ''){
    	$address = trim($address);
   		$name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
    	if (!valid_email($address)) {
    		throw new MailException("Reply to adress is not valid!");
    	}
   	    if (!array_key_exists(strtolower($address), $this->replyTo)) {
        	$this->replyTo[strtolower($address)] = array($address, $name);
      		return true;
    	}
    	return false;	
    }
    
    /**
     * Get To addresses array
     * @access public
     * @return Array     
     */
    public function getToAddress(){
    	return $this->to;
    }
    
    /**
     * Get ReplyTo addresses array
     * @access public
     * @return Array
     */
    public function getReplyTos(){
    	return $this->replyTo;
    }
	
    /**
     * Remove all To addresses
     * @access public
     */
	public function clearAddresses(){
		$this->to = array();
	}
	
	/**
	 * Remove all replyTos addresses
	 * @access public
	 */
	public function clearReplyTos() {
    	$this->replyTo = array();
  	}
	
	/**
	 * Sets message type to HTML.
	 * @param bool $ishtml
	 * @access public
	 * @return void
	 */
	public function isHTML($ishtml = true){
		if($ishtml){
			$this->contentType = 'text/html';
		}
		else{
			$this->contentType = 'text/plain';
		}
	}
}
?>