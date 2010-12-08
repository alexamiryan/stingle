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
	

	public function getNewChats($userId, $lastId){
		return $this->getChatObjects($userId, $this->getNewMessages($userId, $lastId));
	}
	
	public function getHistoryChats($userId){
		return $this->getChatObjects($userId, $this->getMessagesHistory($userId));
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
										
		$invitation = new ChatInvitation();
		
		$invitation->id = $invitationRow['id'];
		$invitation->inviterUser = $this->getChatUser($invitationRow['sender_user_id']);
		$invitation->invitedUser = $this->getChatUser($invitationRow['receiver_user_id']);
		$invitation->invitationMessage = $invitationRow['invitation_message'];
		$invitation->status = $invitationRow['status'];

		return $invitation;
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
	
	public function insertSession(ChatSession $session){
		if(empty($session->inviterUser->userId) or !is_numeric($session->inviterUser->userId)){
			throw new InvalidArgumentException("Invalid inviterUser specified!");
		}
		if(empty($session->invitedUser->userId) or !is_numeric($session->invitedUser->userId)){
			throw new InvalidArgumentException("Invalid invitedUser specified!");
		}
		
		$this->query->exec("	INSERT INTO `".Tbl::get('TBL_CHAT_SESSIONS')."`
										(
											`inviter_user_id`, 
											`invited_user_id`)
								VALUES	(
											'{$session->inviterUser->userId}', 
											'{$session->invitedUser->userId}'
										)");
		
		return $this->query->getLastInsertId();
	}
	
	public function getSession($inviterUserId, $invitedUserId){
		if(empty($inviterUserId) or !is_numeric($inviterUserId)){
			throw new InvalidArgumentException("Invalid \$inviterUserId specified!");
		}
		if(empty($invitedUserId) or !is_numeric($inviterUserId)){
			throw new InvalidArgumentException("Invalid \$invitedUserId specified!");
		}
		
		$sessionRow = $this->query->exec("SELECT *
										FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` 
										WHERE `inviter_user_id`='$inviterUserId' AND `invited_user_id` = '$invitedUserId'" 
										)->fetchRecord();
										
		$session = new ChatSession();
		
		$session->id = $sessionRow['id'];
		$session->inviterUser = $this->getChatUser($sessionRow['inviter_user_id']);
		$session->invitedUser = $this->getChatUser($sessionRow['invited_user_id']);
		$session->startDate = $sessionRow['date'];
		$session->status = $sessionRow['status'];

		return $session;
	}
	
	public function getSessions($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}

		$sessionObjects = array();
		
		$sessions = $this->query->exec("SELECT *
										FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` 
										WHERE (`inviter_user_id`='$userId' OR `invited_user_id`='$userId')" 
										)->fetchRecords();
										
		foreach ($sessions as $sessionRow){
			$session = new ChatSession();
			
			$session->id = $sessionRow['id'];
			$session->inviterUser = $this->getChatUser($sessionRow['inviter_user_id']);
			$session->invitedUser = $this->getChatUser($sessionRow['invited_user_id']);
			$session->startDate = $sessionRow['date'];
			$session->status = $sessionRow['status'];
			array_push($sessionObjects, $session);
		}
		return $sessionObjects;
	}
	
	public function deleteSession(ChatSession $session){
		if(empty($session->id) or !is_numeric($session->id)){
			throw new InvalidArgumentException("Invalid session ID specified!");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_CHAT_SESSIONS')."` WHERE `id`='{$session->id}'");
	}
	
	public function updateSessionStatus(ChatSession $session, $newStatus){
		if(empty($session->id) or !is_numeric($session->id)){
			throw new InvalidArgumentException("Invalid session ID specified!");
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_CHAT_SESSIONS')."` SET `status`='$newStatus' WHERE `id`='{$session->id}'");
	}
	
	protected function getInterlocutorsIds($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}
		
		$interlocutorIds = array();
		
		$sessions = $this->getSessions($userId);
		foreach($sessions as $session){
			if($session->inviterUser->userId == $userId){
				array_push($interlocutorIds, $session->invitedUser->userId);
			}
			else{
				array_push($interlocutorIds, $session->inviterUser->userId);
			}
		}
		
		return $interlocutorIds;
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
	
	public function setAsRead($userId, $lastReceivedMessageId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid \$userId specified!");
		}

		$this->query->exec("	UPDATE `".Tbl::get('TBL_CHAT_MESSAGES')."`
								SET `read` = '".static::STATUS_READ_READ."'
								WHERE 	`receiver_user_id`='{$userId}' AND 
										`read` = '".static::STATUS_READ_UNREAD."' AND 
										`id` <= $lastReceivedMessageId");

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
	
	protected function getNewMessages($userId, $lastId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer!");
		}
		if(!is_numeric($lastId)){
			throw new InvalidArgumentException("\$lastId have to be non zero integer!");
		}
		$newMessages = array();
		$interlocutors = $this->getInterlocutorsIds($userId);
		if(!empty($interlocutors)){
			$messagesId = $this->query->exec("SELECT id
											FROM `".Tbl::get('TBL_CHAT_MESSAGES')."` 
											WHERE
												(
														(`receiver_user_id`='$userId' AND `sender_user_id` IN (".implode(",", $interlocutors).")) OR
														(`sender_user_id`='$userId' AND `receiver_user_id` IN (".implode(",", $interlocutors)."))
													) 
											AND `id` > '$lastId'"
											)->fetchFields('id');
			foreach ($messagesId as $msgId){
				$newMessages[] = $this->getChatMessage($msgId);
			}
		}
		return $newMessages;
		
	}
	
	protected function getMessagesHistory($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}
		$lastMessages = array();
		$interlocutors = $this->getInterlocutorsIds($userId);
		if(!empty($interlocutors)){
			$messages = $this->query->exec("SELECT id
											FROM `".Tbl::get('TBL_CHAT_MESSAGES')."` 
											WHERE 
												(
													(`receiver_user_id`='$userId' AND `sender_user_id` IN (".implode(",", $interlocutors).")) OR
													(`sender_user_id`='$userId' AND `receiver_user_id` IN (".implode(",", $interlocutors)."))
												)
											AND TIMESTAMPDIFF(MINUTE,datetime ,NOW()) < ".self::LOG_MINUTES
											)->fetchFields('id');
			foreach ($messages as $msgId){
				$lastMessages[] = $this->getChatMessage($msgId);
			}
		}
		return $lastMessages;
	}
	
	protected function getChatObjects($userId, $chatMessages){
		$chatObjects = array();
		foreach ($chatMessages as $chatMessage){
			if($chatMessage->senderUser->userId == $userId){
				//User's message
				if(empty($chatObjects[$chatMessage->receiverUser->userId])){
					$chat = new Chat();
					$chat->interlocutorId = $chatMessage->receiverUser->userId;
					$chat->interlocutorUserName = $chatMessage->receiverUser->userName;
					$chat->messages[] = $chatMessage;					
					$chatObjects[$chatMessage->receiverUser->userId] = $chat;
				}
				else{
					$chatObjects[$chatMessage->receiverUser->userId]->messages[] = $chatMessage;
				}
			}
			else{
				//Not user's message
				if(empty($chatObjects[$chatMessage->senderUser->userId])){
					$chat = new Chat();
					$chat->interlocutorId = $chatMessage->senderUser->userId;
					$chat->interlocutorUserName = $chatMessage->senderUser->userName;
					$chat->messages[] = $chatMessage;					
					$chatObjects[$chatMessage->senderUser->userId] = $chat;
				}
				else{
					$chatObjects[$chatMessage->senderUser->userId]->messages[] = $chatMessage;
				}
			}
		}
		return $chatObjects;
	}
	
	public function getChatMessages(ChatMessageFilter $filter = null, $pager = null, $cacheMinutes = 0){
		$chatMessages = array();
		
		if($filter == null){
			$filter = new ChatMessageFilter();
		}
		
		$sqlQuery = "SELECT `chat`.`id`
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
			foreach($this->query->fetchFields('id') as $messageId){
				array_push($chatMessages, $this->getChatMessage($messageId, $cacheMinutes));
			}
		}

		return $chatMessages;
	}
	
	protected function getChatMessage($chatMessageId){
		$messageRow = $this->query->exec("	SELECT *, UNIX_TIMESTAMP(`datetime`) as `timestamp` 
												FROM `".Tbl::get('TBL_CHAT_MESSAGES')."` 
												WHERE 	`id`='$chatMessageId'")->fetchRecord();
		$chatMessage = new ChatMessage();
		$chatMessage->id = $messageRow['id'];
		$chatMessage->senderUser = $this->getChatUser($messageRow['sender_user_id']);
		$chatMessage->receiverUser = $this->getChatUser($messageRow['receiver_user_id']);
		$chatMessage->datetime = $messageRow['datetime'];
		$chatMessage->timestamp = $messageRow['timestamp'];
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