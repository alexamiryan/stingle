<?
class ChatSessionManager extends DbAccessor
{
	const TBL_CHAT_SESSIONS = 'chat_sessions';
	
	const CLOSED_STATUS_NO = 0; 
	const CLOSED_STATUS_YES = 1; 
	
	const CLOSED_REASON_CLOSE = 1; 
	const CLOSED_REASON_OFFLINE = 2; 
	const CLOSED_REASON_MONEY = 3;
	const CLOSED_REASON_SYNC_UI = 4;
	
	private $sessionClearTimeout = 10;  // in minutes
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
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
	
	public function getChatSessions(ChatSessionFilter $filter, $myUserId = null){
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
	
	public function closeSession(ChatSession $session, ChatUser $closerUser, $reason = null){
		$updateReason = "";
		if($reason !== null){
			$updateReason = ", `closed_reason`='$reason'";
			$session->closedReason = $reason;
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_CHAT_SESSIONS')."` 
								SET 	`closed` = '".static::CLOSED_STATUS_YES."', 
									 	`closed_date` = NOW(), 
										`closed_by` = '{$closerUser->userId}'$updateReason 
								WHERE `id`='{$session->id}'");
		
		$session->closed = static::CLOSED_STATUS_YES;
		$session->closedBy = $closerUser->userId;
	}
	
	public function clearTimedOutSessions(){
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` 
								WHERE 	`closed` = 1 AND
										(now() - `closed_date`) >= ".($this->sessionClearTimeout * 60));
		return $this->query->affected();
	}
}
?>