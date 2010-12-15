<?php
class ChatManager extends Filterable
{
	const TBL_CHAT_SESSIONS = 'chat_sessions';
	const TBL_CHAT_MESSAGES = 'chat_messages';
	const TBL_CHAT_INVITATIONS = 'chat_invitations';
	
	const STATUS_READ_UNREAD = 0;
	const STATUS_READ_READ = 1;
	
	const IS_SYSTEM_YES = 1;
	const IS_SYSTEM_NO = 0;
	const LOG_MINUTES = 30;
	
	const FILTER_ID_FIELD = "id";
	const FILTER_SENDER_USER_ID_FIELD = "sender_user_id";
	const FILTER_RECEIVER_USER_ID_FIELD = "receiver_user_id";
	const FILTER_DATETIME_FIELD = "datetime";
	const FILTER_MESSAGE_FIELD = "message";
	const FILTER_READ_FIELD = "read";
	const FILTER_IS_SYSTEM_FIELD = "read";
	

	public function getChatSessions($userId, $lastId = null){
		$chatSessions = $this->getChatSessionObjects($userId);
		
		$this->fillSessionsMessages(&$chatSessions, $userId, $lastId);
		
		return $chatSessions;
	}
	
	protected function fillSessionsMessages($chatSessions, $userId, $lastId = null){
		$interlocutorIds = array();
		foreach($chatSessions as $chatSession){
			array_push($interlocutorIds, $chatSession->interlocutorUser->userId);
		}
		
		$chatMessages = array();
		if(!empty($interlocutorIds)){
			if($lastId !== null){
				$additionalWhere = "`id` > '$lastId'";
			}
			else{
				$additionalWhere = "TIMESTAMPDIFF(MINUTE,datetime ,NOW()) < ".self::LOG_MINUTES;
			}
			
			$this->query->exec("SELECT * FROM `".Tbl::get('TBL_CHAT_MESSAGES')."` 
										WHERE 
											(
												(`receiver_user_id`='$userId' AND `sender_user_id` IN (".implode(",", $interlocutorIds).")) OR
												(`sender_user_id`='$userId' AND `receiver_user_id` IN (".implode(",", $interlocutorIds)."))
											)
										AND $additionalWhere
										ORDER BY `datetime` ASC");
			
			foreach ($this->query->fetchRecords() as $messageRow){
				array_push($chatMessages, $this->getChatMessage($messageRow));
			}
		}

		foreach($chatMessages as $chatMessage){
			foreach($chatSessions as &$chatSession){
				// Check if this message belongs to this session.
				if(	
					(
						$chatMessage->senderUser->userId == $chatSession->interlocutorUser->userId 
							and 
						$chatMessage->receiverUser->userId == $userId
					)
					or
					(
						$chatMessage->senderUser->userId == $userId 
							and 
						$chatMessage->receiverUser->userId == $chatSession->interlocutorUser->userId
					)
				){
					array_push($chatSession->messages, $chatMessage);
				}
			}
		}
	}
	
	protected function getChatSessionObjects($userId){
		$chatSessions = array();
		$sessionRows = $this->query->exec("SELECT *
										FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` 
										WHERE (`inviter_user_id`='$userId' OR `invited_user_id`='$userId')" 
										)->fetchRecords();
										
		foreach ($sessionRows as $sessionRow){
			$chat = new ChatSession();
			
			$interlocutorUserId = null;
			if($sessionRow['inviter_user_id'] == $userId){
				$interlocutorUserId = $sessionRow['invited_user_id'];
			}
			else{
				$interlocutorUserId = $sessionRow['inviter_user_id'];
			}
			
			$chat->id = $sessionRow['id'];
			$chat->chatterUser = $this->getChatUser($userId);
			$chat->interlocutorUser = $this->getChatUser($interlocutorUserId);
			$chat->startDate = $sessionRow['date'];
			$chat->closed = $sessionRow['closed'];
			$chat->closedBy = $sessionRow['closed_by'];
			$chat->closedReason = $sessionRow['closed_reason'];
			array_push($chatSessions, $chat);
		}
		
		return $chatSessions;
	}
	
	public function getInterlocutorsIds($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}
		
		$chatSessions = $this->getChatSessionObjects($userId);
		
		$interlocutorIds = array();
		foreach($chatSessions as $chatSession){
			array_push($interlocutorIds, $chatSession->interlocutorUser->userId);
		}
		
		return $interlocutorIds;
	}
	
	public function getInvitations($userId, $lastId = 0){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}

		$invitationsObjects = array();
		
		$invitations = $this->query->exec("SELECT *
										FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."` 
										WHERE (`receiver_user_id`='$userId' OR `sender_user_id`='$userId') AND `id`>'$lastId'" 
										)->fetchRecords();
										
		foreach ($invitations as $invitationRow){
			$invitation = new ChatInvitation();
			
			$invitation->id = $invitationRow['id'];
			$invitation->inviterUser = $this->getChatUser($invitationRow['sender_user_id']);
			$invitation->invitedUser = $this->getChatUser($invitationRow['receiver_user_id']);
			$invitation->invitationMessage = $invitationRow['invitation_message'];
			$invitation->status = $invitationRow['status'];
			array_push($invitationsObjects, $invitation);
		}
		return $invitationsObjects;
	}
	
	public function getInvitation($inviterUserId, $invitedUserId){
		if(empty($inviterUserId) or !is_numeric($inviterUserId)){
			throw new InvalidArgumentException("Invalid \$inviterUserId specified!");
		}
		if(empty($invitedUserId) or !is_numeric($inviterUserId)){
			throw new InvalidArgumentException("Invalid \$invitedUserId specified!");
		}
		
		$invitationRow = $this->query->exec("SELECT *
										FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."` 
										WHERE `sender_user_id`='$inviterUserId' AND `receiver_user_id` = '$invitedUserId'" 
										)->fetchRecord();
		if(!empty($invitationRow)){
			$invitation = new ChatInvitation();
			
			$invitation->id = $invitationRow['id'];
			$invitation->inviterUser = $this->getChatUser($invitationRow['sender_user_id']);
			$invitation->invitedUser = $this->getChatUser($invitationRow['receiver_user_id']);
			$invitation->invitationMessage = $invitationRow['invitation_message'];
			$invitation->status = $invitationRow['status'];
			
			return $invitation;
		}
		else{
			return false;
		}
	}
	
	public function isInvitationExists(ChatInvitation $invitation){
		if(empty($invitation->inviterUser->userId) or !is_numeric($invitation->inviterUser->userId)){
			throw new InvalidArgumentException("Invalid inviterUser specified!");
		}
		if(empty($invitation->invitedUser->userId) or !is_numeric($invitation->invitedUser->userId)){
			throw new InvalidArgumentException("Invalid invitedUser specified!");
		}

		$invitationsCount = $this->query->exec("SELECT count(*) as `cnt`
										FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."` 
										WHERE	`receiver_user_id`='{$invitation->invitedUser->userId}' AND 
												`sender_user_id` = '{$invitation->inviterUser->userId}'" 
										)->fetchField('cnt');
										
		return ($invitationsCount > 0 ? true : false);
	}
	
	public function insertInvitation(ChatInvitation $invitation){
		if(empty($invitation->inviterUser->userId) or !is_numeric($invitation->inviterUser->userId)){
			throw new InvalidArgumentException("Invalid inviterUser specified!");
		}
		if(empty($invitation->invitedUser->userId) or !is_numeric($invitation->invitedUser->userId)){
			throw new InvalidArgumentException("Invalid invitedUser specified!");
		}
		
		$this->query->exec("	INSERT INTO `".Tbl::get('TBL_CHAT_INVITATIONS')."`
										(
											`sender_user_id`, 
											`receiver_user_id`, 
											`invitation_message`,
											`status`)
								VALUES	(
											'{$invitation->inviterUser->userId}', 
											'{$invitation->invitedUser->userId}', 
											'{$invitation->invitationMessage}',
											'{$invitation->status}'
										)");
		
		return $this->query->getLastInsertId();
	}
	
	public function deleteInvitation(ChatInvitation $invitation){
		if(empty($invitation->id) or !is_numeric($invitation->id)){
			throw new InvalidArgumentException("Invalid invitation ID specified!");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."` WHERE `id`='{$invitation->id}'");
	}
	
	public function updateInvitationStatus($inviterUserId, $invitedUserId, $newStatus){
		$invitation = $this->getInvitation($inviterUserId, $invitedUserId);
		
		$this->deleteInvitation($invitation);
		
		$invitation->status = $newStatus;
		$this->insertInvitation($invitation);
	}
	
	public function insertSession($inviterUserId, $invitedUserId){
		if(empty($inviterUserId) or !is_numeric($inviterUserId)){
			throw new InvalidArgumentException("Invalid inviterUser specified!");
		}
		if(empty($invitedUserId) or !is_numeric($invitedUserId)){
			throw new InvalidArgumentException("Invalid invitedUser specified!");
		}
		
		$this->query->exec("	INSERT INTO `".Tbl::get('TBL_CHAT_SESSIONS')."`
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
	
	public function getSessionIdByInterlocutors($user1, $user2){
		$sessionId = $this->query->exec("SELECT `id`
										FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` 
										WHERE	
												(`inviter_user_id`='$user1' AND 
												`invited_user_id` = '$user2')
												OR
												(`inviter_user_id`='$user2' AND 
												`invited_user_id` = '$user1')" 
										)->fetchField('id');
		return $sessionId;
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
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_CHAT_SESSIONS')."` SET `closed`='".ChatResponse::STATUS_CLOSED."', `closed_by`='$closerUserId'$updateReason WHERE `id`='$sessionId'");
	}
	
	
	/**
	 * Insert ChatMessage object to database 
	 *
	 * @param ChatMessage $chatMessage
	 * @return int inserted message Id
	 */
	public function insertMessage(ChatMessage $chatMessage){
		if(empty($chatMessage->senderUser->userId) or !is_numeric($chatMessage->senderUser->userId)){
			throw new InvalidArgumentException("Invalid senderUser specified!");
		}
		if(empty($chatMessage->receiverUser->userId) or !is_numeric($chatMessage->receiverUser->userId)){
			throw new InvalidArgumentException("Invalid receiverUser specified!");
		}
		
		$this->query->exec("	INSERT INTO `".Tbl::get('TBL_CHAT_MESSAGES')."`
										(
											`sender_user_id`, 
											`receiver_user_id`, 
											`message`, 
											`is_system`) 
								VALUES	(
											'{$chatMessage->senderUser->userId}', 
											'{$chatMessage->receiverUser->userId}', 
											'{$chatMessage->message}', 
											'{$chatMessage->is_system}'
										)");
		
		return $this->query->getLastInsertId();
	}
	
	public function getLastId(){
		$lastId = $this->query->exec("SELECT MAX(id) as `lastId` FROM `".Tbl::get('TBL_CHAT_MESSAGES')."`")->fetchField('lastId');
		return (empty($lastId) ? 0 : $lastId);
	}
	
	public function getLastInvitationId(){
		$lastId = $this->query->exec("SELECT MAX(id) as `lastId` FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."`")->fetchField('lastId');
		return (empty($lastId) ? 0 : $lastId);
	}
	
	protected function getFilterableFieldAlias($field){
		switch($field){
			case self::FILTER_ID_FIELD :
			case self::FILTER_SENDER_USER_ID_FIELD :
			case self::FILTER_RECEIVER_USER_ID_FIELD :
			case self::FILTER_DATETIME_FIELD :
			case self::FILTER_MESSAGE_FIELD :
			case self::FILTER_READ_FIELD :
			case self::FILTER_IS_SYSTEM_FIELD :
				return "chat";
		}

		throw new RuntimeException("Specified field does not exist or not filterable");
	}
	
	protected function getChatObjects($userId, $chatMessages){
		
	}
	
	public function getChatMessages(ChatMessageFilter $filter = null, $pager = null, $cacheMinutes = 0){
		$chatMessages = array();
		
		if($filter == null){
			$filter = new ChatMessageFilter();
		}
		
		$sqlQuery = "SELECT `chat`.*
						FROM `".Tbl::get('TBL_CHAT_MESSAGES')."` `chat`
						{$this->generateJoins($filter)}
						WHERE 1
						{$this->generateWhere($filter)}
						{$this->generateOrder($filter)}
						{$this->generateLimits($filter)}";
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		if($this->query->countRecords()){
			foreach($this->query->fetchRecord() as $messageRow){
				array_push($chatMessages, $this->getChatMessage($messageRow));
			}
		}

		return $chatMessages;
	}
	
	protected function getChatMessage($messageRow){
		$chatMessage = new ChatMessage();
		$chatMessage->id = $messageRow['id'];
		$chatMessage->senderUser = $this->getChatUser($messageRow['sender_user_id']);
		$chatMessage->receiverUser = $this->getChatUser($messageRow['receiver_user_id']);
		$chatMessage->datetime = $messageRow['datetime'];
		$chatMessage->message = nl2br(htmlentities($messageRow['message'],ENT_COMPAT,'UTF-8'));
		$chatMessage->read = $messageRow['read'];
		$chatMessage->is_system = $messageRow['is_system'];
		
		return $chatMessage;
	}
	
	protected function getChatUser($userId){
		$chatUser = new ChatUser();
		$chatUser->userId = $userId;
		
		return $chatUser;
	}
}
?>