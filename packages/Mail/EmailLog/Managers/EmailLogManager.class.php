<?php

class EmailLogManager extends DbAccessor {

	const TBL_EMAIL_LOG = 'email_log';
	
	const TYPE_CONFIRM = 'confirm';
	const TYPE_UNSUBSCRIBE = 'unsubscribe';
	const TYPE_BOUNCE = 'bounce';
	
	const INIT_NONE = 0;
	const INIT_USERS = 1;
	
	const INIT_ALL = 1;
	

	public function getEmailLogs(EmailLogFilter $filter = null, MysqlPager $pager = null,  $initObjects = self::INIT_ALL, $cacheMinutes = 0){
		
		if($filter == null){
			$filter = new EmailLogFilter();
		}
		$sqlQuery = $filter->getSQL();
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$logs = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				$logs[] = $this->getEmailLogObjectFromData($row, $initObjects, $cacheMinutes);
			}
		}
		return $logs;
	}
	
	public function getEmailLog(EmailLogFilter $filter, $initObjects = self::INIT_ALL, $cacheMinutes = 0){
		$logs = $this->getEmailLogs($filter, null, $initObjects, $cacheMinutes);
		if(count($logs) !== 1){
			throw new RuntimeException("There is no such email log or email log is not unique.");
		}
		return $logs[0];
	}
	
	public function addEmailLog(EmailLog $log){

		$log->emailHost = substr(strrchr($log->email, "@"), 1);
        $qb = new QueryBuilder();
		$insertArr = array(
			'user_id'           => $log->userId,
			'email_id'          => $log->emailId,
			'email'             => $log->email,
			'email_host'		=> $log->emailHost,
			'type'		        => $log->type,
			'bounce_type'       => $log->bounceType,
			'bounce_code'       => $log->bounceCode,
			'data'              => $log->data
		);
		
		
		
        $qb->insert(Tbl::get("TBL_EMAIL_LOG"))
            ->values($insertArr);

		return $this->query->exec($qb->getSQL())->getLastInsertId();
	}
	
	public function updateEmailLog(EmailLog $log){
		$qb = new QueryBuilder();
		
		$log->emailHost = substr(strrchr($log->email, "@"), 1);
		
		$qb->update(Tbl::get('TBL_EMAIL_LOG'))
			->set(new Field('user_id'), $log->userId)
			->set(new Field('email_id'), $log->emailId)
			->set(new Field('email'), $log->email)
			->set(new Field('email_host'), $log->emailHost)
			->set(new Field('type'), $log->type)
			->set(new Field('bounce_type'), $log->bounceType)
			->set(new Field('bounce_code'), $log->bounceCode)
			->set(new Field('data'), $log->data)
			->where($qb->expr()->equal(new Field('id'), $log->id));
		
		try{
            return $this->query->exec($qb->getSQL())->affected();
        }
        catch(MySqlException $e){
            return false;
        }
	}

	protected function getEmailLogObjectFromData($data, $initObjects = self::INIT_ALL, $cacheMinutes = 0){
		$log = new EmailLog();
		$log->id 				= $data['id'];
		$log->userId 			= $data['user_id'];
		$log->emailId 			= $data['email_id'];
		$log->email 			= $data['email'];
		$log->emailHost			= $data['email_host'];
		$log->type 				= $data['type'];
		$log->bounceType		= $data['bounce_type'];
		$log->bounceCode		= $data['bounce_code'];
		$log->date	 			= $data['date'];
		$log->data				= $data['data'];
		
		if( ($initObjects & self::INIT_USERS) != 0){
			$usersManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
			try {
				if(!empty($data['user_id'])){
					$log->user = $usersManager->getUserById($data['user_id'], UserManager::INIT_ALL, $cacheMinutes);
				}
			}
			catch (UserNotFoundException $e){ }
		}

		return $log;
	}
	

}
