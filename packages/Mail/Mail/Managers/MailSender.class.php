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
	
	const BOUNCE_TYPE_SOFT = 'soft';
	const BOUNCE_TYPE_HARD = 'hard';
	const BOUNCE_TYPE_BLOCKED = 'blocked';
	
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
	public function __construct(Config $config, MailTransportInterface $transport = null) {
		$this->config = $config;
		if($transport !== null){
			$this->transport = $transport;
		}
		else{
			$defaultTransportClassName = $this->config->mailParams->{$this->config->defaultMailConfig}->transport;
			$this->transport = new $defaultTransportClassName();
		}
	}
	
	public function setTransport(MailTransportInterface $transport){
		$this->transport = $transport;
	}
	
	public function setIncludeString($string){
		$this->stringToInclude = $string;
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
			
			if(!empty($mail->unsubscribeEmail)){
				array_push($unsubscribeArr, '<mailto:' . $mail->unsubscribeEmail . '>');
			}
			elseif(!empty($mail->returnPath)){
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
		
		if($transportConfigName === null && $mail->transportConfigName !== null){
			$transportConfigName = $mail->transportConfigName;
		}
		
		try {
			if($mail->isHtml and $mail->autogenerateTextVersion){
				$mail->textBody = Html2Text\Html2Text::convert($mail->htmlBody);
			}
			
			if(!empty($mail->transport) && $mail->transport !== get_class($this->transport)){
				$transportClassName = $mail->transport;
				$this->transport = new $transportClassName();
			}
			
			HookManager::callHook('BeforeEmailSend', $mail);
			
			return $this->transport->send($mail, $transportConfigName);
		}
		catch (Exception $e) {
			return false;
		}
	}
	
	public function sendAsync(Mail $mail, $transportConfigName = null) {
		$params = [
			'mail' => $mail,
			'transportConfigName' => $transportConfigName
		];
		Reg::get('jobQueue')->addJob(MailJobQueueChunk::$name, $params);
		return true;
	}

	public function initMail(User $to, $typeId = null, $mailAltConfigName = null, $checkValidity = true) {
		$toHost = null;
		$toLang = null;
		try {
			$toHost = new Host($to->props->hostId);
		}
		catch (InvalidArgumentException $e) {
			$toHost = new Host();
		}
		
		try {
			$toLang = new Language($to->props->langId);
		}
		catch (RuntimeException $e) {
			$toLang = new Language();
		}
		
		$this->checkHostLangPair($toHost, $toLang);
		
		$mailParams = null;
		if (!empty($mailAltConfigName)) {
			$mailParams = $this->config->mailParams->$mailAltConfigName;
		}
		else {
			$mailParams = $this->config->mailParams->{$this->config->defaultMailConfig};
		}
		
		try {
			$mail = new Mail();
			$mail->returnPath = $mailParams->returnPath;
			$mail->from = $mailParams->fromMail;
			$mail->fromName = (isset($mailParams->fromName)) ? $mailParams->fromName : '';
			if(isset($mailParams->replyToMail) && !empty($mailParams->replyToMail)){
				$mail->addReplyTo($mailParams->replyToMail, $mailParams->replyToName, $checkValidity);
			}
			if(isset($mailParams->returnPath) && !empty($mailParams->returnPath)){
				$mail->returnPath = $mailParams->returnPath;
			}
			if(isset($mailParams->unsubscribeEmail) && !empty($mailParams->unsubscribeEmail)){
				$mail->unsubscribeEmail = $mailParams->unsubscribeEmail;
			}
			$mail->addTo($to->email, $to->login, $checkValidity);
			$mail->host = $toHost;
			$mail->language = $toLang;
			$mail->user = $to;
			$mail->typeId = $typeId;
			$mail->type = (isset($this->typesMap[$typeId]) ? $this->typesMap[$typeId] : null);
			$mail->emailId = generateRandomString(self::EMAIL_ID_LENGTH, array(RANDOM_STRING_LOWERCASE, RANDOM_STRING_DIGITS));
			$mail->transport = (!empty($mailParams->transport) ? $mailParams->transport : null);
			$mail->transportConfigName = (!empty($mailParams->transportConfigName) ? $mailParams->transportConfigName : null);
		}
		catch (Exception $e) {
			return false;
		}

		return $mail;
	}
	
	public function initMailSimple($toEmail = null, $subject = null, $mailAltConfigName = null, $checkValidity = true) {
		$mailParams = null;
		if (!empty($mailAltConfigName)) {
			$mailParams = $this->config->mailParams->$mailAltConfigName;
		}
		else {
			$mailParams = $this->config->mailParams->{$this->config->defaultMailConfig};
		}

		try {
			$mail = new Mail();
			$mail->returnPath = $mailParams->returnPath;
			$mail->from = $mailParams->fromMail;
			$mail->fromName = (isset($mailParams->fromName)) ? $mailParams->fromName : '';
			if(isset($mailParams->replyToMail) && !empty($mailParams->replyToMail)){
				$mail->addReplyTo($mailParams->replyToMail, $mailParams->replyToName, $checkValidity);
			}
			if(isset($mailParams->returnPath) && !empty($mailParams->returnPath)){
				$mail->returnPath = $mailParams->returnPath;
			}
			if(isset($mailParams->unsubscribeEmail) && !empty($mailParams->unsubscribeEmail)){
				$mail->unsubscribeEmail = $mailParams->unsubscribeEmail;
			}
			$mail->emailId = generateRandomString(self::EMAIL_ID_LENGTH, array(RANDOM_STRING_LOWERCASE, RANDOM_STRING_DIGITS));
			$mail->transport = (!empty($mailParams->transport) ? $mailParams->transport : null);
			$mail->transportConfigName = (!empty($mailParams->transportConfigName) ? $mailParams->transportConfigName : null);
			
			if(!empty($toEmail)){
				if(is_array($toEmail)){
					foreach($toEmail as $email){
						$mail->addTo($email, '', $checkValidity);
					}
				}
				else{
					$mail->addTo($toEmail, '', $checkValidity);
				}
			}
			if(!empty($subject)){
				$mail->subject = $subject;
			}
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
		
		if ($mail->user->emailConfirmed != UserManager::STATE_EMAIL_CONFIRMED) {
			return false;
		}
		
		$hookArgs = ['email' => $mail->user->email, 'mail' => $mail];
		if(!HookManager::callBooleanAndHook('IsMailSendAllowed', $hookArgs)){
			return false;
		}
		
		return true;
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
		Reg::get('userMgr')->updateUser($user);
		
		$hookArgs = ['user' => $user, 'isBounced' => $isBounced];
		HookManager::callHook('DisableEmailReceive', $hookArgs);
	}
	
	public function handleBounce($email, $bounceType, $mailId = null){
		$disabledUserIds = [];
		if($bounceType == MailSender::BOUNCE_TYPE_HARD){
			$filter = new UsersFilter();
			$filter->setEmail(Reg::get('sql')->escapeString($email));

			$users = Reg::get('userMgr')->getUsersList($filter);
			foreach ($users as $user) {
				$this->disableEmailReceive($user, true);
				
				$userHookParams = array(
					'user' => $user,
					'mailId' => $mailId,
					'bounceType' => $bounceType
				);
				HookManager::callHook('EmailBounceByUser', $userHookParams);
				$disabledUserIds[] = $user->id;
			}
		}
		if($this->config->logBounces){
			DBLogger::logCustom("bounce", $email . ' - ' . implode(', ', $disabledUserIds) . ' - ' . $bounceType . ' - ' . $mailId);
		}
	}
	
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
