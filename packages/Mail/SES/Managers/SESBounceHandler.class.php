<?php

class SESBounceHandler {

	public static function handleBounce() {
		
		$message = '';
		
		try {
			$message = Aws\Sns\Message::fromRawPostData();

			$validator = new Aws\Sns\MessageValidator();
			$validator->validate($message);
		}
		catch (Exception $e) {
			http_response_code(404);
			die();
		}
		
		if ($message['Type'] == "Notification" && !empty($message['Message'])) {
			$notif = json_decode($message['Message']);

			$mailId = self::findMailId($notif);

			if ($notif->notificationType == 'Bounce' && !empty($notif->bounce)) {
				self::handleBounceNotification($notif, $mailId);
			}
			elseif ($notif->notificationType == 'Complaint' && !empty($notif->complaint)) {
				self::handleComplaintNotification($notif, $mailId);
			}
		}
		elseif ($message['Type'] === 'SubscriptionConfirmation') {
			//file_get_contents($message['SubscribeURL']);
			DBLogger::logCustom('sns_confirm', $message['SubscribeURL']);
		}
	}

	public static function handleBounceNotification($notif, $mailId) {
		$config = ConfigManager::getConfig('Mail', 'SES')->AuxConfig;
		$bounceType = $notif->bounce->bounceType;
		$bounceSubType = $notif->bounce->bounceSubType;

		$failedAddresses = [];
		if (!empty($notif->bounce->bouncedRecipients)) {
			foreach ($notif->bounce->bouncedRecipients as $recp) {
				$failedAddress = self::filterRecpAddress($recp->emailAddress);

				$hookParams = [
					'email' => $failedAddress,
					'mailId' => $mailId,
					'bounceType' => self::translateBounceType($bounceType),
				];
				HookManager::callHook('EmailBounce', $hookParams);

				$failedAddresses[] = $failedAddress;
			}

			if ($config->logBounces) {
				DBLogger::logCustom("sns_bounce", $bounceType . ' - ' . $bounceSubType . ' - ' . $mailId . ' - ' . implode(', ', $failedAddresses));
			}
		}
	}

	public static function handleComplaintNotification($notif, $mailId) {
		$config = ConfigManager::getConfig('Mail', 'SES')->AuxConfig;

		$failedAddresses = [];
		if (!empty($notif->complaint->complainedRecipients)) {
			foreach ($notif->complaint->complainedRecipients as $recp) {
				$failedAddress = self::filterRecpAddress($recp->emailAddress);

				$hookParams = [
					'email' => $failedAddress,
					'mailId' => $mailId,
					'bounceType' => MailSender::BOUNCE_TYPE_HARD
				];
				HookManager::callHook('EmailBounce', $hookParams);

				$failedAddresses[] = $failedAddress;
			}

			if ($config->logBounces) {
				DBLogger::logCustom("sns_bounce", 'Complaint - ' . $mailId . ' - ' . implode(', ', $failedAddresses));
			}
		}
	}

	public static function translateBounceType($type) {
		switch ($type) {
			case 'Permanent':
				return MailSender::BOUNCE_TYPE_HARD;
			case 'Transient':
			case 'Undetermined':
				return MailSender::BOUNCE_TYPE_SOFT;
		}
	}

	public static function findMailId($notif) {
		$mailId = null;
		if (!empty($notif->mail) && !empty($notif->mail->headers)) {
			foreach ($notif->mail->headers as $header) {
				if (!empty($header->name) && $header->name == 'X-MailId') {
					$mailId = $header->value;
					break;
				}
			}
		}
		return $mailId;
	}

	public static function filterRecpAddress($email) {
		$matches = array();
		if (preg_match('/<(.+@.+)>/iU', $email, $matches)) {
			$email = $matches[1];
		}
		return $email;
	}

}
