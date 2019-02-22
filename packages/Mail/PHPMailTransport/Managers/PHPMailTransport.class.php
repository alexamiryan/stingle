<?php

class PHPMailTransport implements MailTransportInterface {

	public static function getDefaultConfig() {
		$configs = ConfigManager::getConfig('Mail', 'PHPMailTransport')->AuxConfig;
		foreach ($configs->toArray() as $config) {
			if ($config->isDefault) {
				return $config;
			}
		}
		throw new RuntimeException("There is no default PHPMailTransport config defined");
	}

	public static function getConfigByName($name) {
		$config = ConfigManager::getConfig('Mail', 'PHPMailTransport')->AuxConfig;
		if (empty($name)) {
			throw new InvalidArgumentException("name is empty");
		}
		if (isset($config->$name)) {
			return $config->$name;
		}
		else {
			return self::getDefaultConfig();
		}
	}

	/**
	 * Send mail by current Mail object
	 * 
	 * @param Mail $mail
	 * @throws MailException
	 * @access public
	 * @return bool true if the mail was successfully accepted for delivery, false otherwise.
	 */
	public function send(Mail $mail, $configName = null) {
		if (empty($mail)) {
			throw new MailException("Mail object is empty");
		}

		$config = null;
		if ($configName) {
			$config = self::getConfigByName($configName);
		}
		else{
			$config = self::getDefaultConfig();
		}

		$phpMailer = new PHPMailer();

		if ($config->SMTP->enabled) {
			$phpMailer->isSMTP();
			$phpMailer->Host = $config->SMTP->host;
			$phpMailer->Port = $config->SMTP->port;

			if ($config->SMTP->debug) {
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

			if ($config->SMTP->customOptions) {
				$phpMailer->SMTPOptions = $config->SMTP->customOptions->toArray(true);
			}
		}

		if (!empty($mail->returnPath)) {
			$phpMailer->Sender = $mail->returnPath;
		}

		$phpMailer->setFrom($mail->from, $mail->fromName);

		foreach ($mail->getToAddresses() as $address) {
			$phpMailer->addAddress($address['address'], $address['name']);
		}

		if (count($mail->getReplyToAddresses())) {
			foreach ($mail->getReplyToAddresses() as $address) {
				$phpMailer->addReplyTo($address['address'], $address['name']);
			}
		}

		foreach ($mail->getCustomHeaders() as $header) {
			$phpMailer->addCustomHeader($header['name'], $header['value']);
		}

		$phpMailer->Subject = $mail->subject;
		$phpMailer->isHTML($mail->isHtml);
		$phpMailer->CharSet = $mail->charSet;
		$phpMailer->Encoding = $mail->encoding;

		if ($mail->isHtml) {
			$phpMailer->Body = $mail->htmlBody;
		}
		else {
			$phpMailer->Body = $mail->textBody;
		}
		if ($mail->isHtml and ! empty($mail->textBody)) {
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
			if ($config->SMTP->enabled && $config->SMTP->debug) {
				$error .= "\n\nDebug\n\n" . $phpMailer->Debugoutput;
			}
			throw new MailException("Error sending email: " . $error);
		}
		return true;
	}

}
