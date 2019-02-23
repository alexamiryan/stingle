<?php

class MailSender extends Model {

	/**
	 * 
	 * @access protected
	 * @var Config
	 */
	protected $config;
	
	const RECEIVE_MAIL_YES = 1;
	const RECEIVE_MAIL_NO = 0;
	
	protected $receiveMailFlagsLength = 0;
	
	const EMAIL_ID_LENGTH = 16;

	public $typesMap = array(
			null => 'general'
	);
	
	protected $stringToInclude = "";
	
	protected $transport = null;
	
	/**
	 * Classes constructor
	 * @access public
	 * @param Config $config
	 */
	public function __construct(Config $config, MailTransportInterface $transport) {
		$this->config = $config;
		$this->transport = $transport;
	}
	
	public function setTransport(MailTransportInterface $transport){
		$this->transport = $transport;
	}
	
	public function setIncludeString($string){
		$this->stringToInclude = $string;
	}
	
	public function getDefaultMailParams(){
		foreach($this->config->mailParams->toArray() as $config){
			if($config->isDefault){
				return $config;
			}
		}
		throw new RuntimeException("There is no default mail config defined");
	}
	
	public function getMailParamsByName($name){
		if(empty($name)){
			throw new InvalidArgumentException("name is empty");
		}
		if(isset($this->config->mailParams->$name)){
			return $this->config->mailParams->$name;
		}
		else{
			return $this->getDefaultMailParams();
		}
	}
	
	/**
	 * Send mail
	 */
	public function send(Mail $mail, $transportConfigName = null) {
		if (!$this->isMailSendAllowed($mail)) {
			return true;
		}
		
		if (!empty($mail->user)) {
			$unsubscribeArr = array();
			
			if(!empty($mail->returnPath)){
				array_push($unsubscribeArr, '<mailto:' . $mail->returnPath . '>');
			}
			
			$unsubscribeUrl = $this->getUnsubscribeUrl($mail, $this->config->unsubscribeFromAll);
			if (!empty($unsubscribeUrl)) {
				array_push($unsubscribeArr, '<' . $unsubscribeUrl . '>');
			}
			
			if(!empty($unsubscribeArr)){
				$mail->addCustomHeader('List-unsubscribe', implode(', ', $unsubscribeArr));
			}
		}
		if(!empty($mail->emailId)){
			$mail->addCustomHeader('X-MailId', $mail->emailId);
		}
		if($this->config->isMailsAreBulk){
			$mail->addCustomHeader('Precedence', 'bulk');
		}
		
		try {
			if($mail->isHtml and $mail->autogenerateTextVersion){
				$mail->textBody = Html2Text\Html2Text::convert($mail->htmlBody);
			}
			
			HookManager::callHook('BeforeEmailSend', $mail);
			
			return $this->transport->send($mail, $transportConfigName);
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function initMail(User $to, $typeId = null, $checkValidity = true, $mailAltConfigName = null) {
		$toHost = null;
		$toLang = null;
		if (isset($to->props->host) && !empty($to->props->host)) {
			$toHost = $to->props->host;
		}
		else {
			try {
				$toHost = new Host($to->props->hostId);
			}
			catch (InvalidArgumentException $e) {
				$toHost = new Host();
			}
		}
		if (isset($to->props->language) && !empty($to->props->language)) {
			$toLang = $to->props->language;
		}
		else {
			try {
				$toLang = new Language($to->props->langId);
			}
			catch (RuntimeException $e) {
				$toLang = new Language();
			}
		}
		$this->checkHostLangPair($toHost, $toLang);

		$mailParams = null;
		if (!empty($mailAltConfigName)) {
			$mailParams = $this->getMailParamsByName($mailAltConfigName);
		}
		else {
			$mailParams = $this->getDefaultMailParams();
		}

		try {
			$mail = new Mail();
			$mail->returnPath = $mailParams->returnPath;
			$mail->from = $mailParams->fromMail;
			$mail->fromName = (isset($mailParams->fromName)) ? $mailParams->fromName : '';
			$mail->addReplyTo($mailParams->replyToMail, $mailParams->replyToName, $checkValidity);
			$mail->returnPath = $mailParams->returnPath;
			$mail->addTo($to->email, $to->login, $checkValidity);
			$mail->host = $toHost;
			$mail->language = $toLang;
			$mail->user = $to;
			$mail->typeId = $typeId;
			$mail->type = (isset($this->typesMap[$typeId]) ? $this->typesMap[$typeId] : null);
			$mail->emailId = generateRandomString(self::EMAIL_ID_LENGTH);
		}
		catch (Exception $e) {
			return false;
		}

		return $mail;
	}
	
	public function getHTMLBody(Mail $mail, $templateName) {

		Reg::get('smarty')->assign('contentPath', $this->config->mailTemplatesPath . $templateName . '.tpl');

		Reg::get('smarty')->assign('mail', $mail);
		Reg::get('smarty')->assign('to', $mail->user);
		Reg::get('smarty')->assign('mailUrl', HostManager::hostToURLAddress($mail->host));
		
		$optOutUrl = "";
		if ($mail->typeId !== null) {
			$optOutUrl = $this->getUnsubscribeUrl($mail, $this->config->unsubscribeFromAll);
		}
		
		Reg::get('smarty')->assign("optOutUrl", $optOutUrl);
		
		$this->localSmartyAssigns($mail->user, $mail);
		
		Reg::get('smarty')->assign('toInclude', $this->stringToInclude);

		$defaultTemplateName = ConfigManager::getConfig("Output", "Smarty")->AuxConfig->templatesConfig->defaultTemplateName;
		$controllerTemplate = HostControllerTemplate::getControllerTemplateByHost($mail->host);
		if (empty($controllerTemplate)) {
			$templateByHost = $defaultTemplateName;
		}
		else {
			$templateByHost = $controllerTemplate["template"];
		}

		$html = "";
		$oldTemplate = Reg::get('smarty')->getTemplate();
		try {
			Reg::get('smarty')->setTemplate($templateByHost);
			$html = Reg::get('smarty')->getChunk("mails/layout.tpl");
			Reg::get('smarty')->setTemplate($oldTemplate);
		}
		catch (RuntimeException $e) {
			Reg::get('smarty')->setTemplate($oldTemplate);
		}

		return $html;
	}
	
	protected function localSmartyAssigns(User $to, Mail $mail){
		
	}

	public function isMailSendAllowed(Mail $mail) {
		if ($mail->typeId === null or $mail->user === null) {
			return true;
		}
		if($mail->user->enabled == UserManager::STATE_ENABLED_DISABLED){
			return false;
		}
		if($this->isUserAllowedToReceiveMail($mail)){
			return true;
		}
		return false;
	}
	
	protected function isUserAllowedToReceiveMail(Mail $mail){
		if (!empty($mail->user) and $mail->user->emailConfirmed == UserManager::STATE_EMAIL_CONFIRMED) {
			return true;
		}
		return false;
	}

	public function convertArrayToMailFlags($flags) {

		$receiveMailFlags = array_fill(0, $this->receiveMailFlagsLength, '0');
		$availableFlags = self::getConstsArray('RECEIVE_MAIL');
		if (empty($flags)) {
			$flags = array();
		}

		foreach ($flags as $flag) {
			if (in_array($flag, array_values($availableFlags))) {
				$receiveMailFlags[$flag] = '1';
			}
		}

		return $this->buildReceiveMailsFlagsFromArray($receiveMailFlags);
	}
	
	public function convertMailFlagsToArray($receiveMail) {
		if (empty($receiveMail)) {
			return array();
		}

		$receiveMailFlags = str_split($receiveMail);
		unset($receiveMailFlags[0]);
		$receiveMailFlags = array_reverse($receiveMailFlags);
		return $receiveMailFlags;
	}
	
	public function disableReceiveMailFlag($receiveMail, $mailId) {
		if ($mailId < 0 or $mailId >= $this->receiveMailFlagsLength) {
			throw new InvalidArgumentException("Incorrect mail ID: $mailId");
		}
		$mailIds = $this->convertMailFlagsToArray($receiveMail);
		if (isset($mailIds[$mailId])) {
			$mailIds[$mailId] = '0';
			return $this->buildReceiveMailsFlagsFromArray($mailIds);
		}
		return false;
	}

	public function buildFullReceiveMailsFlags() {
		return $this->buildReceiveMailsFlagsFromArray(array_fill(0, $this->receiveMailFlagsLength, '1'));
	}

	public function buildZeroReceiveMailsFlags() {
		return $this->buildReceiveMailsFlagsFromArray(array_fill(0, $this->receiveMailFlagsLength, '0'));
	}

	public function buildReceiveMailsFlagsFromArray($mailIds) {
		$mailIds = array_reverse($mailIds);

		$finalValue = "9";
		$finalValue .= implode("", $mailIds);

		return $finalValue;
	}
	
	public function disableEmailReceive(User $user, $isBounced = true){
		$user->emailConfirmed = 0;
		$this->additionalEmailDisableActions($user, $isBounced);
		return Reg::get('userMgr')->updateUser($user);
	}
	
	protected function additionalEmailDisableActions(User $user, $isBounced = true){ }
	

	protected function checkSenderReceiverObjects(User $to, User $from = null) {
		if (empty($to->email)) {
			throw new InvalidArgumentException("User mail have to be non empty string!");
		}
		if ($from !== null && empty($from->email)) {
			throw new InvalidArgumentException("From user mail have to be non empty string!");
		}
	}
	
	function checkHostLangPair(Host &$host, Language &$language) {
		try {
			HostLanguageManager::getHostLanguageId($host, $language);
		}
		catch (Exception $e) {
			if (!empty($host->id)) {
				$language = HostLanguageManager::getHostDefaultLanguage($host);
			}
			elseif (!empty($language->id)) {
				$languageHosts = HostLanguageManager::getLanguageHosts($language);
				$host = $languageHosts[0];
			}
			else {
				$pairs = HostLanguageManager::getAllPairs();
				$somePair = array_shift($pairs);
				$host = $somePair['host'];
				$language = $somePair['language'];
			}
		}
	}

	protected function getUnsubscribeUrl(Mail $mail, $unsubscribeFromAll = false) {
		if(empty($mail->user) or empty($mail->typeId)){
			return "";
		}
		$authOTCconfig = new OTCConfig();
		$authOTCconfig->multiUse = true;
		$authOTCconfig->paramsArray = array('t' => 'un', 'u' => $mail->user->id);
		if ($mail->typeId != null) {
			if ($unsubscribeFromAll) {
				$authOTCconfig->paramsArray['m'] = 'a';
			}
			else {
				$authOTCconfig->paramsArray['m'] = $mail->typeId;
			}
		}
		if(!empty($mail->emailId)){
			$authOTCconfig->paramsArray['id'] = $mail->emailId;
		}
		$authOTCconfig->validityTime = 60 * 60 * 24 * 31;
		$authCode = Reg::get('otc')->generate($authOTCconfig);

		return HostManager::hostToURLAddress($mail->host) . SITE_PATH . $this->config->unsubscribePath . "/code:" . $authCode;
	}

	
}
