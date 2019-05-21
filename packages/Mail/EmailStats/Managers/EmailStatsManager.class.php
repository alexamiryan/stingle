<?php

class EmailStatsManager extends DbAccessor {

	const TBL_EMAIL_STATS = 'email_stats';
	
	const STATUS_READ_UNREAD = 0;
	const STATUS_READ_READ = 1;
	
	const STATUS_CLICKED_NO = 0;
	const STATUS_CLICKED_YES = 1;
	
	const STATUS_ACTIVATED_NO = 0;
	const STATUS_ACTIVATED_YES = 1;
	
	const STATUS_UNSUBSCRIBED_NO = 0;
	const STATUS_UNSUBSCRIBED_YES = 1;
	
	const STATUS_BOUNCED_NO = 0;
	const STATUS_BOUNCED_YES = 1;
	
	
	public function getEmailStats(EmailStatsFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = 0){
		
		if($filter == null){
			$filter = new EmailStatsFilter();
		}
		$sqlQuery = $filter->getSQL();
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$stats = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				$stats[] = $this->getEmailStatsObjectFromData($row);
			}
		}
		return $stats;
	}
	
	public function getEmailStat(EmailStatsFilter $filter, $cacheMinutes = 0){
		$stats = $this->getEmailStats($filter, null, $cacheMinutes);
		if(count($stats) !== 1){
			throw new RuntimeException("There is no such email stat or email stat is not unique.");
		}
		return $stats[0];
	}
	
	public function addEmailStat(EmailStat $stat){

        $qb = new QueryBuilder();
		$insertArr = array(
			'email_id'          => $stat->emailId,
			'email'             => $stat->email,
			'from'              => $stat->from,
			'user_id'			=> $stat->userId,
			'type'              => $stat->type,
			'bounce_message'    => $stat->bounceMessage
		);
		
        $qb->insert(Tbl::get("TBL_EMAIL_STATS"))
            ->values($insertArr);

		return $this->query->exec($qb->getSQL())->getLastInsertId();
	}
	
	public function updateEmailStat(EmailStat $stat){
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_EMAIL_STATS'))
			->set(new Field('email_id'), $stat->emailId)
			->set(new Field('email'), $stat->email)
			->set(new Field('from'), $stat->from)
			->set(new Field('user_id'), $stat->userId)
			->set(new Field('type'), $stat->type)
			->set(new Field('is_read'), $stat->isRead)
			->set(new Field('is_clicked'), $stat->isClicked)
			->set(new Field('is_activated'), $stat->isActivated)
			->set(new Field('is_unsubscribed'), $stat->isUnsubscribed)
			->set(new Field('is_bounced_soft'), $stat->isBouncedSoft)
			->set(new Field('is_bounced_hard'), $stat->isBouncedHard)
			->set(new Field('is_bounced_block'), $stat->isBouncedBlock)
			->set(new Field('date'), $stat->date)
			->set(new Field('date_read'), $stat->dateRead)
			->set(new Field('date_clicked'), $stat->dateClicked)
			->set(new Field('date_activated'), $stat->dateActivated)
			->set(new Field('date_unsubscribed'), $stat->dateUnsubscribed)
			->set(new Field('date_bounced'), $stat->dateBounced)
			->set(new Field('bounce_message'), $stat->bounceMessage)
			->where($qb->expr()->equal(new Field('id'), $stat->id));
		
		try{
            return $this->query->exec($qb->getSQL())->affected();
        }
        catch(MySqlException $e){
            return false;
        }
	}
	
	public function getEmailStatById($id){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($id);
		$stats = $this->getEmailStats($filter);
		if(count($stats) == 1){
			return $stats[0];
		}
		return false;
	}
	
	public function isEmailSoftBounced($email, $inLastXDays = 30, $cacheMinutes = 0){
		if(empty($inLastXDays)){
			return false;
		}
		
		$filter = new EmailStatsFilter();
		$filter->setEmail($email);
		$filter->setIsBouncedSoft();
		$filter->setLastXDays($inLastXDays);
		$filter->setSelectCount();
		
		$this->query->exec($filter->getSQL(), $cacheMinutes);
		
		$bouncedCount = $this->query->fetchField('cnt');
		
		if($bouncedCount > 0){
			return true;
		}
		return false;
	}
	
	public function setEmailAsRead($emailId){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($emailId);
		
		try{
			$emailStat = $this->getEmailStat($filter);
			$emailStat->isRead = self::STATUS_READ_READ;
			$emailStat->dateRead = date(DEFAULT_DATETIME_FORMAT);

			return $this->updateEmailStat($emailStat);
		}
		catch(RuntimeException $e){
			return false;
		}
	}
	
	public function setEmailAsClicked($emailId){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($emailId);
		
		try{
			$emailStat = $this->getEmailStat($filter);
			$emailStat->isClicked = self::STATUS_CLICKED_YES;
			$emailStat->dateClicked = date(DEFAULT_DATETIME_FORMAT);

			return $this->updateEmailStat($emailStat);
		}
		catch(RuntimeException $e){
			return false;
		}
	}
	
	public function setEmailAsActivated($emailId){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($emailId);
		
		try{
			$emailStat = $this->getEmailStat($filter);
			$emailStat->isActivated = self::STATUS_ACTIVATED_YES;
			$emailStat->dateActivated = date(DEFAULT_DATETIME_FORMAT);

			return $this->updateEmailStat($emailStat);
		}
		catch(RuntimeException $e){
			return false;
		}
	}
	
	public function setEmailAsUnsubscribed($emailId){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($emailId);
		
		try{
			$emailStat = $this->getEmailStat($filter);
			$emailStat->isUnsubscribed = self::STATUS_UNSUBSCRIBED_YES;
			$emailStat->dateUnsubscribed = date(DEFAULT_DATETIME_FORMAT);

			return $this->updateEmailStat($emailStat);
		}
		catch(RuntimeException $e){
			return false;
		}
	}
	
	public function setEmailAsBouncedSoft($emailId, $msgHeaders, $msgBody){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($emailId);
		
		try{
			$emailStat = $this->getEmailStat($filter);
			$emailStat->isBouncedSoft = self::STATUS_BOUNCED_YES;
			$emailStat->dateBounced = date(DEFAULT_DATETIME_FORMAT);
			$emailStat->bounceMessage = $msgHeaders . "\n\n" . $msgBody;

			return $this->updateEmailStat($emailStat);
		}
		catch(RuntimeException $e){
			return false;
		}
	}
	
	public function setEmailAsBouncedHard($emailId, $msgHeaders, $msgBody){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($emailId);
		
		try{
			$emailStat = $this->getEmailStat($filter);
			$emailStat->isBouncedHard = self::STATUS_BOUNCED_YES;
			$emailStat->dateBounced = date(DEFAULT_DATETIME_FORMAT);
			$emailStat->bounceMessage = $msgHeaders . "\n\n" . $msgBody;

			return $this->updateEmailStat($emailStat);
		}
		catch(RuntimeException $e){
			return false;
		}
	}
	
	public function setEmailAsBouncedBlock($emailId, $msgHeaders, $msgBody){
		$filter = new EmailStatsFilter();
		$filter->setEmailId($emailId);
		
		try{
			$emailStat = $this->getEmailStat($filter);
			$emailStat->isBouncedBlock = self::STATUS_BOUNCED_YES;
			$emailStat->dateBounced = date(DEFAULT_DATETIME_FORMAT);
			$emailStat->bounceMessage = $msgHeaders . "\n\n" . $msgBody;

			return $this->updateEmailStat($emailStat);
		}
		catch(RuntimeException $e){
			return false;
		}
	}
	
	public function sendEmail($to, $from, $id = null, $type = null, $userId = null){
		$stat = new EmailStat();
		$stat->email = $to;
		$stat->from = $from;
		
		if(!empty($id)){
			$stat->emailId = $id;
		}
		
		if(!empty($type)){
			$stat->type = $type;
		}
		
		if(!empty($userId)){
			$stat->userId = $userId;
		}
		
		return $this->addEmailStat($stat);
	}
	
	protected function getEmailStatsObjectFromData($data){
		$stat = new EmailStat();
		$stat->id 				= $data['id'];
		$stat->emailId 			= $data['email_id'];
		$stat->email 			= $data['email'];
		$stat->from 			= $data['from'];
		$stat->userId			= $data['user_id'];
		$stat->type 			= $data['type'];
		$stat->isRead			= $data['is_read'];
		$stat->isClicked		= $data['is_clicked'];
		$stat->isActivated		= $data['is_activated'];
		$stat->isUnsubscribed	= $data['is_unsubscribed'];
		$stat->isBouncedSoft	= $data['is_bounced_soft'];
		$stat->isBouncedHard	= $data['is_bounced_hard'];
		$stat->isBouncedBlock	= $data['is_bounced_block'];
		$stat->date	 			= $data['date'];
		$stat->dateRead			= $data['date_read'];
		$stat->dateClicked		= $data['date_clicked'];
		$stat->dateActivated	= $data['date_activated'];
		$stat->dateUnsubscribed	= $data['date_unsubscribed'];
		$stat->dateBounced		= $data['date_bounced'];
		$stat->bounceMessage	= $data['bounce_message'];
		
		return $stat;
	}
	

}
