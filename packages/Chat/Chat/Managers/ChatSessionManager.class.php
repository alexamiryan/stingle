<?php
class ChatSessionManager extends DbAccessor
{
	const TBL_CHAT_SESSIONS = 'chat_sessions';
	const TBL_CHAT_SESSIONS_LOG = 'chat_sessions_log';
	
	const CLOSED_STATUS_NO = 0; 
	const CLOSED_STATUS_YES = 1; 
	
	const CLOSED_REASON_CLOSE = 1; 
	const CLOSED_REASON_OFFLINE = 2; 
	const CLOSED_REASON_MONEY = 3;
	const CLOSED_REASON_SYNC_UI = 4;
	
	private $sessionClearTimeout = 10;  // in minutes
	
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		if(isset($config->sessionClearTimeout)){
			$this->sessionClearTimeout = $config->sessionClearTimeout;
		}
	}
	
	public static function getInterlocutorsFromSessions($chatSessions){
		$interlocutorIds = array();
		foreach($chatSessions as $chatSession){
			array_push($interlocutorIds, $chatSession->interlocutorUser->userId);
		}
		
		return $interlocutorIds;
	}
	
	public static function fillSessionsWithMessages($chatSessions, $chatMessages){
		foreach($chatMessages as $chatMessage){
			foreach($chatSessions as &$chatSession){
				// Check if this message belongs to this session.
				if(
					(
						$chatMessage->senderUser->userId == $chatSession->interlocutorUser->userId
							and
						$chatMessage->receiverUser->userId == $chatSession->chatterUser->userId
					)
					or
					(
						$chatMessage->senderUser->userId == $chatSession->chatterUser->userId
							and
						$chatMessage->receiverUser->userId == $chatSession->interlocutorUser->userId
					)
				){
					array_push($chatSession->messages, $chatMessage);
				}
			}
		}
		return $chatSessions;
	}
	
	/**
	 * Sessions list
	 * @param ChatSessionFilter $filter
	 * @param unknown_type $myUserId
	 */
	public function getChatSessions(ChatSessionFilter $filter = null, $myUserId = null){
		$chatSessions = array();
		
		if($filter == null){
			$filter = new ChatSessionFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		
		$sessionRows = $this->query->exec($sqlQuery)->fetchRecords();
										
		foreach ($sessionRows as $sessionRow){
			array_push($chatSessions, $this->getChatSessionObject($sessionRow, $myUserId));
		}
		
		return $chatSessions;
	}
	
	/**
	 * Sessions count
	 * @param ProfileViewsFilter $filter
	 * @param Integer $cacheMinutes
	 * @return Integer
	 */
	public function getChatSessionsCount(ChatSessionFilter $filter = null){
	
		if($filter == null){
			$filter = new ChatSessionFilter();
		}
		$filter->setSelectCount();
	
		$sqlQuery = $filter->getSQL();
	
		$this->query->exec($sqlQuery);
		return $this->query->fetchField('cnt');
	}
	
	public function getChatSession(ChatSessionFilter $filter = null, $myUserId = null){
		$sessions = $this->getChatSessions($filter, $myUserId);
		if(count($sessions) !== 1){
			throw new ChatSessionException("There is no such chat session or it is not unique.");
		}
		return $sessions[0];
	}
	
	public function getChatSessionById($sessionId, $myUserId = null){
		$filter = new ChatSessionFilter();
		$filter->setId($sessionId);
		$session = $this->getChatSession($filter);
		return $session;
	}
	
	protected function getChatSessionObject($sessionRow, $myUserId = null){
		if(empty($sessionRow) or !is_array($sessionRow)){
			throw new InvalidArgumentException("Invalid \$sessionRow specified!");
		}
		
		$chatSession = new ChatSession();
		
		if($myUserId !== null){
			if($sessionRow['inviter_user_id'] == $myUserId){
				$interlocutorUserId = $sessionRow['invited_user_id'];
			}
			else{
				$interlocutorUserId = $sessionRow['inviter_user_id'];
			}
			
			$chatSession->chatterUser = ChatUser::getObject($myUserId);
			$chatSession->interlocutorUser = ChatUser::getObject($interlocutorUserId);
		}
		else{
			$chatSession->chatterUser = ChatUser::getObject($sessionRow['inviter_user_id']);
			$chatSession->interlocutorUser = ChatUser::getObject($sessionRow['invited_user_id']);
		}
		
		$chatSession->id = $sessionRow['id'];
		$chatSession->startDate = $sessionRow['date'];
		$chatSession->closed = $sessionRow['closed'];
		$chatSession->closedBy = $sessionRow['closed_by'];
		$chatSession->closedReason = $sessionRow['closed_reason'];
		$chatSession->closedDate = $sessionRow['closed_date'];
		
		return $chatSession;
	}
	
	public function getInterlocutorsIds($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}
		
		$filter = new ChatSessionFilter();
		$filter->setEitherUserId($userId);
		$chatSessions = $this->getChatSessions($filter, $userId);
		
		$interlocutorIds = array();
		foreach($chatSessions as $chatSession){
			array_push($interlocutorIds, $chatSession->interlocutorUser->userId);
		}
		
		return $interlocutorIds;
	}
	
	public function insertSession($inviterUserId, $invitedUserId){
		if(empty($inviterUserId) or !is_numeric($inviterUserId)){
			throw new InvalidArgumentException("Invalid inviterUser specified!");
		}
		if(empty($invitedUserId) or !is_numeric($invitedUserId)){
			throw new InvalidArgumentException("Invalid invitedUser specified!");
		}
		$qb = new QueryBuilder();
		$qb->insertIgnore(Tbl::get('TBL_CHAT_SESSIONS'))
			->values(array(
							'inviter_user_id' => $inviterUserId,
							'invited_user_id' => $invitedUserId
			));
		$this->query->exec($qb->getSQL());
		
		$newSessionId = $this->query->getLastInsertId();
		//$this->insertSessionLog($inviterUserId, $invitedUserId);
		
		return $newSessionId;
	}
	
	public function deleteSession($sessionId){
		if(empty($sessionId) or !is_numeric($sessionId)){
			throw new InvalidArgumentException("Invalid session ID specified!");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CHAT_SESSIONS'))
			->where($qb->expr()->equal(new Field('id'), $sessionId));
		$this->query->exec($qb->getSQL());
	}
	
	public function closeSession(ChatSession $session, ChatUser $closerUser, $reason = null){
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CHAT_SESSIONS'))
			->set(new Field('closed'), static::CLOSED_STATUS_YES)
			->set(new Field('closed_date'), new Func('NOW'))
			->set(new Field('closed_by'), $closerUser->userId)
			->where($qb->expr()->equal(new Field('id'), $session->id));
		if($reason !== null){
			$qb->set(new Field('closed_reason'), $reason);
			$session->closedReason = $reason;
		}	
		$this->query->exec($qb->getSQL());
		
		$session->closed = static::CLOSED_STATUS_YES;
		$session->closedBy = $closerUser->userId;
	}
	
	public function clearTimedOutSessions(){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CHAT_SESSIONS'))
			->where($qb->expr()->equal(new Field('closed'), 1))
			->andWhere(
						$qb->expr()->greaterEqual(
								$qb->expr()->diff(new Func('NOW'), new Field('closed_date')),
								$qb->expr()->prod($this->sessionClearTimeout, 60)
						 ));
						 
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	public function getChatSessionsLog(MysqlPager $pager = null, $myUserId = null, $cacheMinutes = 0){
		$sessions = array();
		$qb = new QueryBuilder();
		$qb->select(new Field("*", "chat_sess_log"))->from(Tbl::get('TBL_CHAT_SESSIONS_LOG'), "chat_sess_log");

		if($myUserId !== null){
			$orClause = new Orx();
			$orClause->add($qb->expr()->equal(new Field("user1_id", "chat_sess_log"), $myUserId));
			$orClause->add($qb->expr()->equal(new Field("user2_id", "chat_sess_log"), $myUserId));
			$qb->andWhere($orClause);
		}
		
		$qb->orderBy(new Field("datetime", "chat_sess_log"), MySqlDatabase::ORDER_DESC);
		$sqlQuery = $qb->getSQL();
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		if($this->query->countRecords()){
			while(($sesLogRow = $this->query->fetchRecord()) != null){
				$chatSession = new ChatSessionLog();

				$chatSession->user1 = ChatUser::getObject($sesLogRow['user1_id']);
				$chatSession->user2 = ChatUser::getObject($sesLogRow['user2_id']);
				
				$chatSession->id = $sesLogRow['id'];
				$chatSession->closedDate = $sesLogRow['datetime'];
				
				array_push($sessions, $chatSession);
			}
		}
		
		return $sessions;
	}
	
	public function getChatSessionsLogCount($myUserId = null){
		
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count(new Field('*'), 'cnt'))->from(Tbl::get('TBL_CHAT_SESSIONS_LOG'), "chat_sess_log");
		if($myUserId !== null){
			$orClause = new Orx();
			$orClause->add($qb->expr()->equal(new Field("user1_id", "chat_sess_log"), $myUserId));
			$orClause->add($qb->expr()->equal(new Field("user2_id", "chat_sess_log"), $myUserId));
			$qb->andWhere($orClause);
		}
		
		$sqlQuery = $qb->getSQL();
		$this->query->exec($sqlQuery);
		return $this->query->fetchField('cnt');
	}
	/**
	 * @param integer $inviterUserId
	 * @param integer $invitedUserId
	 * @deprecated Sessions log insertd by mysql TRIGGER chat_sessions_log 
	 */
	protected function insertSessionLog($inviterUserId, $invitedUserId){
		if($inviterUserId > $invitedUserId){
			$userId1 = $inviterUserId;
			$userId2 = $invitedUserId;
		}
		else{
			$userId1 = $invitedUserId;
			$userId2 = $inviterUserId;
		}
		$qb = new QueryBuilder();
		$qb->select(new Field('id'))->from(Tbl::get('TBL_CHAT_SESSIONS_LOG'));
		
		$andClause1 = new Andx();
		$andClause1->add($qb->expr()->equal(new Field('user1_id', Tbl::get('TBL_CHAT_SESSIONS_LOG')), $userId1));
		$andClause1->add($qb->expr()->equal(new Field('user2_id', Tbl::get('TBL_CHAT_SESSIONS_LOG')), $userId2));
		
		$andClause2 = new Andx();
		$andClause2->add($qb->expr()->equal(new Field('user1_id', Tbl::get('TBL_CHAT_SESSIONS_LOG')), $userId2));
		$andClause2->add($qb->expr()->equal(new Field('user2_id', Tbl::get('TBL_CHAT_SESSIONS_LOG')), $userId1));
		
		$orClause = new Orx();
		$orClause->add($andClause1);
		$orClause->add($andClause2);
		
		$qb->andWhere($orClause);
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		if($this->query->countRecords()){
			$sesionId = $this->query->fetchField("id");
			$qb->update(Tbl::get('TBL_CHAT_SESSIONS_LOG'))
			->set(new Field('datetime'), date(DEFAULT_DATETIME_FORMAT))
			->where($qb->expr()->equal(new Field('id'), $sesionId));
			
		}
		else{
			$qb->insert(Tbl::get('TBL_CHAT_SESSIONS_LOG'))
			->values(array(
					'user1_id' => $userId1,
					'user2_id' => $userId2,
					'datetime' => date(DEFAULT_DATETIME_FORMAT)
			));
		}
		$this->query->exec($qb->getSQL());
		
		return $this->query->affected();
	}
}
