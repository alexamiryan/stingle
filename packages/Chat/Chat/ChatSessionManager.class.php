<?php
class ChatSessionManager extends Filterable
{
	const TBL_CHAT_SESSIONS = 'chat_sessions';
	
	const CLOSED_STATUS_NO = 0; 
	const CLOSED_STATUS_YES = 1; 
	
	const CLOSED_REASON_CLOSE = 1; 
	const CLOSED_REASON_OFFLINE = 2; 
	const CLOSED_REASON_MONEY = 3;
	const CLOSED_REASON_SYNC_UI = 4;
	
	const FILTER_ID_FIELD = 'id';
	const FILTER_INVITER_USER_ID_FIELD = 'inviter_user_id';
	const FILTER_INVITED_USER_ID_FIELD = 'invited_user_id';
	const FILTER_DATE_FIELD = 'date';
	const FILTER_CLOSED_FIELD = 'closed';
	const FILTER_CLOSED_REASON_FIELD = 'closed_reason';
	const FILTER_CLOSED_DATE_FIELD = 'closed_date';
	
	private $sessionClearTimeout = 10;  // in minutes
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		if(isset($config->sessionClearTimeout)){
			$this->sessionClearTimeout = $config->sessionClearTimeout;
		}
	}
	
	protected function getFilterableFieldAlias($field){
		switch($field){
			case static::FILTER_ID_FIELD :
			case static::FILTER_INVITER_USER_ID_FIELD :
			case static::FILTER_INVITED_USER_ID_FIELD :
			case static::FILTER_DATE_FIELD :
			case static::FILTER_CLOSED_FIELD :
			case static::FILTER_CLOSED_REASON_FIELD :
			case static::FILTER_CLOSED_DATE_FIELD :
				return "sess";
		}

		throw new RuntimeException("Specified field does not exist or not filterable");
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
	
	public function getChatSessions(ChatSessionFilter $filter, $myUserId = null){
		$chatSessions = array();
		
		$sessionRows = $this->query->exec("SELECT `sess`.*
										FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` `sess`
										{$this->generateJoins($filter)}
										WHERE 1
										{$this->generateWhere($filter)}
										{$this->generateOrder($filter)}
										{$this->generateLimits($filter)}"
									)->fetchRecords();
										
		foreach ($sessionRows as $sessionRow){
			array_push($chatSessions, $this->getChatSessionObject($sessionRow, $myUserId));
		}
		
		return $chatSessions;
	}
	
	public function getChatSession(ChatSessionFilter $filter, $myUserId = null){
		$sessions = $this->getChatSessions($filter, $myUserId);
		if(count($sessions) !== 1){
			throw new ChatSessionException("There is no such chat session or it is not unique.");
		}
		return $sessions[0];
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
		
		$this->query->exec("	INSERT IGNORE INTO `".Tbl::get('TBL_CHAT_SESSIONS')."`
										(
											`inviter_user_id`, 
											`invited_user_id`)
								VALUES	(
											'$inviterUserId', 
											'$invitedUserId'
										)");
		
		return $this->query->getLastInsertId();
	}
	
	public function deleteSession($sessionId){
		if(empty($sessionId) or !is_numeric($sessionId)){
			throw new InvalidArgumentException("Invalid session ID specified!");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` WHERE `id`='{$sessionId}'");
	}
	
	public function closeSession($sessionId, $closerUserId, $reason = null){
		if(empty($sessionId) or !is_numeric($sessionId)){
			throw new InvalidArgumentException("Invalid session ID specified!");
		}
		if(empty($closerUserId) or !is_numeric($closerUserId)){
			throw new InvalidArgumentException("Invalid closerUserId specified!");
		}
		
		$updateReason = "";
		if($reason !== null){
			$updateReason = ", `closed_reason`='$reason'";
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_CHAT_SESSIONS')."` 
								SET 	`closed` = '".static::CLOSED_STATUS_YES."', 
									 	`closed_date` = NOW(), 
										`closed_by` = '$closerUserId'$updateReason 
								WHERE `id`='$sessionId'");
	}
	
	public function clearTimedOutSessions(){
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` 
								WHERE 	`closed` = 1 AND
										(now() - `closed_date`) >= ".($this->sessionClearTimeout * 60));
		return $this->query->affected();
	}
}
?>