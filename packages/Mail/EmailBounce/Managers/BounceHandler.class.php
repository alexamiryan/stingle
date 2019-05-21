<?php

class BounceHandler extends Model {

	/**
	 * 
	 * @access protected
	 * @var Config
	 */
	protected $config;
	
	/**
	 * Classes constructor
	 * @access public
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		$this->config = $config;
	}
	
	public function getIMAPConfigByName($name){
		if(empty($name)){
			throw new InvalidArgumentException("name is empty");
		}
		if(isset($this->config->IMAP->$name)){
			return $this->config->IMAP->$name;
		}
		else{
			throw new InvalidArgumentException("There is no IMAP config with name $name");
		}
	}
	
	
	public function handleBounceEmails($imapConfigName) {
		$imapConfig = $this->getIMAPConfigByName($imapConfigName);

		$bmh = new BounceMailHandler();
		$bmh->actionFunction = array($this, 'callbackAction'); // default is 'callbackAction'
		$bmh->verbose = BounceMailHandler::VERBOSE_QUIET; //BounceMailHandler::VERBOSE_SIMPLE; //BounceMailHandler::VERBOSE_REPORT; //BounceMailHandler::VERBOSE_DEBUG; //BounceMailHandler::VERBOSE_QUIET; // default is BounceMailHandler::VERBOSE_SIMPLE
		//$bmh->useFetchStructure  = true; // true is default, no need to specify
		//$bmh->testMode           = false; // false is default, no need to specify
		//$bmh->debugBodyRule      = false; // false is default, no need to specify
		//$bmh->debugDsnRule       = false; // false is default, no need to specify
		//$bmh->purgeUnprocessed   = false; // false is default, no need to specify
		$bmh->disableDelete = !$this->config->deleteBouncedEmails; // false is default, no need to specify
		$bmh->maxMessages = 10000; // false is default, no need to specify

		$bmh->mailhost = $imapConfig->host; // your mail server
		$bmh->mailboxUserName = $imapConfig->username; // your mailbox username
		$bmh->mailboxPassword = $imapConfig->password; // your mailbox password
		$bmh->port = $imapConfig->port; // the port to access your mailbox, default is 143
		$bmh->service = 'imap'; // the service to use (imap or pop3), default is 'imap'
		$bmh->serviceOption = 'ssl/novalidate-cert'; // the service options (none, tls, notls, ssl, etc.), default is 'notls'
		$bmh->boxname = 'INBOX'; // the mailbox to access, default is 'INBOX'
		//$bmh->moveHard           = true; // default is false
		//$bmh->hardMailbox        = 'INBOX.hardtest'; // default is 'INBOX.hard' - NOTE: must start with 'INBOX.'
		//$bmh->moveSoft           = true; // default is false
		//$bmh->softMailbox        = 'INBOX.softtest'; // default is 'INBOX.soft' - NOTE: must start with 'INBOX.'
		//$bmh->deleteMsgDate      = '2009-01-05'; // format must be as 'yyyy-mm-dd'

		$bmh->openMailbox();
		$bmh->processMailbox();
	}

	/**
	 * Callback (action) function
	 *
	 * @param int            $msgnum       the message number returned by Bounce Mail Handler
	 * @param string         $bounceType   the bounce type:
	 *                                     'antispam','autoreply','concurrent','content_reject','command_reject','internal_error','defer','delayed'
	 *                                     =>
	 *                                     array('remove'=>0,'bounce_type'=>'temporary'),'dns_loop','dns_unknown','full','inactive','latin_only','other','oversize','outofoffice','unknown','unrecognized','user_reject','warning'
	 * @param string         $email        the target email address
	 * @param string         $subject      the subject, ignore now
	 * @param string         $xheader      the XBounceHeader from the mail
	 * @param boolean        $remove       remove status, 1 means removed, 0 means not removed
	 * @param string|boolean $ruleNo       Bounce Mail Handler detect rule no.
	 * @param string|boolean $ruleCat      Bounce Mail Handler detect rule category.
	 * @param int            $totalFetched total number of messages in the mailbox
	 * @param string         $body         Bounce Mail Body
	 * @param string         $headerFull   Bounce Mail Header
	 * @param string         $bodyFull     Bounce Mail Body (full)
	 *
	 * @return boolean
	 */
	function callbackAction($msgnum, $bounceType, $email, $subject, $xheader, $remove, $ruleNo = false, $ruleCat = false, $totalFetched = 0, $body = '', $headerFull = '', $bodyFull = '') {
		$displayData = $this->prepData($email, $bounceType, $remove);
		$bounceType = $displayData['bounce_type'];
		$emailName = $displayData['emailName'];
		$emailAddy = $displayData['emailAddy'];
		$remove = $displayData['remove'];

		$logins = "";
		if (!empty($email)) {
			
			$mailId = null;
			$matches = array();
			if(preg_match('/^X-MailId:\s(.+)$/m', $bodyFull, $matches) && !empty($matches[1])){
				$mailId = trim($matches[1]);
			}
			$hookParams = array(
				'email' => $email,
				'mailId' => $mailId,
				'bounceType' => $bounceType,
				'msgHeaders' => $headerFull,
				'msgBody' => $bodyFull
			);
			HookManager::callHook('EmailBounce', $hookParams);
		}
		if($this->config->bounceLogging){
			DBLogger::logCustom("bounce_remove_sum", $msgnum . ': ' . $ruleNo . ' | ' . $bounceType . ' | ' . $remove . ' | ' . $email . ' | ' . $emailName . ' | ' . $emailAddy . ' | ' . $logins . "\n\n" .
				$headerFull . "\n\n\n" . $bodyFull);
		}
		if($this->config->bounceEchoOutput){
			echo $msgnum . ': ' . $ruleNo . ' | ' . $bounceType . ' | ' . $remove . ' | ' . $email . ' | ' . $emailName . ' | ' . $emailAddy . ' | ' . $logins . "\n";
		}
		return true;
	}

	/**
	 * Function to clean the data from the Callback Function for optimized display
	 *
	 * @param $email
	 * @param $bounceType
	 * @param $remove
	 *
	 * @return mixed
	 */
	function prepData($email, $bounceType, $remove) {
		$data['bounce_type'] = trim($bounceType);
		$data['email'] = '';
		$data['emailName'] = '';
		$data['emailAddy'] = '';
		$data['remove'] = '';
		if (strpos($email, '<') !== false) {
			$pos_start = strpos($email, '<');
			$data['emailName'] = trim(substr($email, 0, $pos_start));
			$data['emailAddy'] = substr($email, $pos_start + 1);
			$pos_end = strpos($data['emailAddy'], '>');
			if ($pos_end) {
				$data['emailAddy'] = substr($data['emailAddy'], 0, $pos_end);
			}
		}
		// replace the < and > able so they display on screen
		$email = str_replace(array('<', '>'), array('&lt;', '&gt;'), $email);
		// replace the "TO:<" with nothing
		$email = str_ireplace('TO:<', '', $email);
		$data['email'] = $email;
		// account for legitimate emails that have no bounce type
		if (trim($bounceType) == '') {
			$data['bounce_type'] = 'none';
		}
		// change the remove flag from true or 1 to textual representation
		if (stripos($remove, 'moved') !== false && stripos($remove, 'hard') !== false) {
			$data['removestat'] = 'moved (hard)';
			$data['remove'] = 'moved (hard)';
		}
		elseif (stripos($remove, 'moved') !== false && stripos($remove, 'soft') !== false) {
			$data['removestat'] = 'moved (soft)';
			$data['remove'] = 'moved (soft)';
		}
		elseif ($remove == true || $remove == '1') {
			$data['removestat'] = 'deleted';
			$data['remove'] = 'deleted';
		}
		else {
			$data['removestat'] = 'not deleted';
			$data['remove'] = 'not deleted';
		}
		return $data;
	}

}
