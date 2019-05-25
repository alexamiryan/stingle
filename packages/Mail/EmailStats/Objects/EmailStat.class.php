<?php

class EmailStat {
	public $id;
	public $emailId = null;
	public $email;
	public $domain;
	public $from;
	public $userId = null;
	public $type = null;
	public $isRead = EmailStatsManager::STATUS_READ_UNREAD;
	public $isClicked = EmailStatsManager::STATUS_CLICKED_NO;
	public $isActivated = EmailStatsManager::STATUS_ACTIVATED_NO;
	public $isUnsubscribed = EmailStatsManager::STATUS_UNSUBSCRIBED_NO;
	public $isBouncedSoft = EmailStatsManager::STATUS_BOUNCED_NO;
	public $isBouncedHard = EmailStatsManager::STATUS_BOUNCED_NO;
	public $isBouncedBlock = EmailStatsManager::STATUS_BOUNCED_NO;
	public $date = null;
	public $dateRead = null;
	public $dateClicked = null;
	public $dateActivated = null;
	public $dateUnsubscribed = null;
	public $dateBounced = null;
	public $bounceMessage = null;
	
	public $user = null;
}
