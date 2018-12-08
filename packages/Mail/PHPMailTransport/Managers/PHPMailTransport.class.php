<?php

class PHPMailTransport implements MailTransportInterface {

	/**
	 * Send mail by current Mail object
	 * 
	 * @param Mail $mail
	 * @throws MailException
	 * @access public
	 * @return bool true if the mail was successfully accepted for delivery, false otherwise.
	 */
	public function send(Mail $mail, Config $customConfig = null) {
		if (empty($mail)) {
			throw new MailException("Mail object is empty");
		}

		$config = clone(ConfigManager::getConfig('Mail', 'PHPMailTransport')->AuxConfig);
		if($customConfig){
			$config = ConfigManager::mergeConfigs($customConfig, $config);
		}
		
		$phpMailer = new PHPMailer();

		if ($config->SMTP->enabled) {
			$phpMailer->isSMTP();
			$phpMailer->Host = $config->SMTP->host;
			$phpMailer->Port = $config->SMTP->port;
			
			if($config->SMTP->debug){
				$phpMailer->SMTPDebug = $config->SMTP->debug;
			}
			if ($config->SMTP->secureMethod) {
				$phpMailer->SMTPSecure = $config->SMTP->secureMethod;
			}
			if ($config->SMTP->auth->enabled) {
				$phpMailer->SMTPAuth = true;
				$phpMailer->Username = $config->SMTP->auth->username;
				$phpMailer->Password = $config->SMTP->auth->password;
			}
			
			if($config->SMTP->customOptions){
				$phpMailer->SMTPOptions = $config->SMTP->customOptions->toArray(true);
			}
		}

		$returnPath = (!empty($mail->returnPath) ? $mail->returnPath : $config->mailParams->returnPath);
		if(!empty($returnPath)){
			$phpMailer->Sender = $returnPath;
		}
		
		$fromMail = (!empty($mail->from) ? $mail->from : $config->mailParams->fromMail);
		$fromName = (!empty($mail->fromName) ? $mail->fromName : $config->mailParams->fromName);
		$phpMailer->setFrom($fromMail, $fromName);

		foreach ($mail->getToAddresses() as $address) {
			$phpMailer->addAddress($address['address'], $address['name']);
		}

		if(count($mail->getReplyToAddresses())){
			foreach ($mail->getReplyToAddresses() as $address) {
				$phpMailer->addReplyTo($address['address'], $address['name']);
			}
		}
		else{
			$phpMailer->addReplyTo($config->mailParams->replyToMail, $config->mailParams->replyToName);
		}

		foreach ($mail->getCustomHeaders() as $header) {
			$phpMailer->addCustomHeader($header['name'], $header['value']);
		}

		$phpMailer->Subject = $mail->subject;
		$phpMailer->isHTML(true);
		$phpMailer->CharSet = $mail->charSet;
		$phpMailer->Encoding = $mail->encoding;

		$phpMailer->Body = $mail->htmlBody;
		if (!empty($mail->textBody)) {
			$phpMailer->AltBody = $mail->textBody;
		}

		if ($config->DKIM->enabled) {
			$phpMailer->DKIM_domain = $config->DKIM->domain;
			$phpMailer->DKIM_private_string = $config->DKIM->privateKey;
			$phpMailer->DKIM_selector = $config->DKIM->selector;
			$phpMailer->DKIM_passphrase = $config->DKIM->password;
			$phpMailer->DKIM_identity = $phpMailer->From;
		}

		if (!$phpMailer->send()) {
			$error = $phpMailer->ErrorInfo;
			if ($config->SMTP->enabled && $config->SMTP->debug){
				$error .= "\n\nDebug\n\n" . $phpMailer->Debugoutput;
			}
			throw new MailException("Error sending email: " . $error);
		}
		return true;
	}

}
