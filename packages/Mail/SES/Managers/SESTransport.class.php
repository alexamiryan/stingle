<?php

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class SESTransport implements MailTransportInterface {

	protected $config = null;
	protected $sesClient = null;

	public function __construct() {
		$this->config = ConfigManager::getConfig('Mail', 'SES')->AuxConfig;
		$params = [
			'version' => $this->config->version,
			'credentials' => [
				'key' => $this->config->credentials->key,
				'secret' => $this->config->credentials->secret
			]
		];

		if (!empty($this->config->region)) {
			$params['region'] = $this->config->region;
		}

		$this->sesClient = new SesClient($params);
	}

	public function send(Mail $mail, $configName = null) {

		$phpMailer = new PHPMailTransport();
		$rawEmail = $phpMailer->getMailRaw($mail, $configName);
		
		
		$toAddresses = [];
		foreach ($mail->getToAddresses() as $address) {
			array_push($toAddresses, $address['address']);
		}
		
		try {
			$result = $this->sesClient->sendRawEmail(array(
				//'Destinations' => $toAddresses,
				// RawMessage is required
				'RawMessage' => array(
					// Data is required
					'Data' => $rawEmail,
				),
				//'FromArn' => 'arn:aws:ses:us-east-1:522122328719:identity/edesirs.fr',
				//'SourceArn' => 'arn:aws:ses:us-east-1:522122328719:identity/edesirs.fr',
				//'ReturnPathArn' => 'arn:aws:ses:us-east-1:522122328719:identity/edesirs.fr',
			));
			if($result){
				return true;
			}
			return false;
		}
		catch (AwsException $e) {
			return false;
		}
		
	}

}
