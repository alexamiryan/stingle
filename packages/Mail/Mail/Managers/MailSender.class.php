<?php

class MailSender {

	/**
	 * 
	 * @access protected
	 * @var Config
	 */
	protected $config;

	/**
	 * Constructor
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		$this->config = $config;
	}

	/**
	 * Send mail by current Mail object
	 * 
	 * @param Mail $mail
	 * @throws MailException
	 * @access public
	 * @return bool true if the mail was successfully accepted for delivery, false otherwise.
	 */
	public function send(Mail $mail) {
		if (empty($mail)) {
			throw new MailException("Mail object is empty");
		}

		$phpMailer = new PHPMailer();
		
		$phpMailer->setFrom($mail->from, $mail->fromName);

		foreach ($mail->getToAddresses() as $address) {
			$phpMailer->addAddress($address['address'], $address['name']);
		}

		foreach ($mail->getReplyToAddresses() as $address) {
			$phpMailer->addReplyTo($address['address'], $address['name']);
		}
		
		foreach ($mail->getCustomHeaders() as $header) {
			$phpMailer->addCustomHeader($header['name'], $header['value']);
		}

		$phpMailer->Subject = $mail->subject;
		$phpMailer->isHTML(true);
		$phpMailer->CharSet = $mail->charSet;
		
		$phpMailer->Body = $mail->htmlBody;
		if (!empty($mail->textBody)) {
			$phpMailer->AltBody = $mail->textBody;
		}

		if ($this->config->DKIM->enabled) {
			$phpMailer->DKIM_domain = $this->config->DKIM->domain;
			$phpMailer->DKIM_private_string = $this->config->DKIM->privateKey;
			$phpMailer->DKIM_selector = $this->config->DKIM->selector;
			$phpMailer->DKIM_passphrase = $this->config->DKIM->password;
			$phpMailer->DKIM_identity = $phpMailer->From;
		}


		if(!$phpMailer->send()){
			throw new MailException("Error sending email: " . $phpMailer->ErrorInfo);
		}
		return true;
	}
}
