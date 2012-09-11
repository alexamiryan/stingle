<?
class Mail extends PHPMailer {
	
	/*protected $mailDomain;
	protected $fromUsername;
	protected $replyUsername;
	protected $adminUsername;
	
	protected $developerMail;*/
	
		
	public function __construct(Config $config){
		parent::__construct($config->throwExaptions);
		
		$this->IsMail();
		if($config->replyToMail !== null){
			$this->AddReplyTo($config->replyToMail, $config->replyToName);
		}		
		if($config->fromMail !== null){
			$this->SetFrom($config->fromMail, $config->fromName);
		}	
			
		//$this->mailDomain = $config->domainName;
	}
}
?>