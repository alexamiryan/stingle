<?php
class ChatManager extends Filterable
{
	const TBL_CHAT = 'chat';
	
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
	

	public function getNewChats($userId){
		return $this->getChatObjects($userId, $this->getNewMessages($userId));
	}
	
	public function getHistoryChats($userId){
		return $this->getChatObjects($userId, $this->getMessagesHistory($userId));
	}
	
	public function getInvitations($userId){
		$invitations = array();
		
		$chatMessages = $this->getInvitationMessages($userId);
		foreach ($chatMessages as $chatMessage){
			if(empty($invitations[$chatMessage->senderUser->userId])){
				$invitation = new ChatInvitation();
				$invitation->inviterId = $chatMessage->senderUser->userId;
				$invitation->inviterUserName = $chatMessage->senderUser->userName;
				$invitations[$chatMessage->senderUser->userId] = $invitation;
			}
		}
		return $invitations;
	}
	
	/**
	 * Insert ChatMessage object to database 
	 *
	 * @param ChatMessage $chatMessage
	 * @return int inserted message Id
	 */
	public function insertMessage(ChatMessage $chatMessage){
		if(empty($chatMessage->senderUserId) or !is_numeric($chatMessage->senderUserId)){
			throw new InvalidArgumentException("Invalid senderUserId specified!");
		}
		if(empty($chatMessage->receiverUserId) or !is_numeric($chatMessage->receiverUserId)){
			throw new InvalidArgumentException("Invalid receiverUserId specified!");
		}
		
		$this->query->exec("	INSERT INTO `".Tbl::get('TBL_CHAT')."`
										(
											`sender_user_id`, 
											`receiver_user_id`, 
											`message`, 
											`is_system`) 
								VALUES	(
											'{$chatMessage->senderUserId}', 
											'{$chatMessage->receiverUserId}', 
											'{$chatMessage->message}', 
											'{$chatMessage->is_system}'
										)");
		
		return $this->query->getLastInsertId();
	}
	
	public function setAsRead($userId, $lastReceivedMessageId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidArgumentException("Invalid receiverUserId specified!");
		}
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}

		$this->query->exec("	UPDATE `".Tbl::get('TBL_CHAT')."`
								SET `read` = '".static::STATUS_READ_READ."'
								WHERE `receiver_user_id`='{$userId}' and `read` = '".static::STATUS_READ_UNREAD."'");

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
	
	protected function getNewMessages($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer!");
		}
		$newMessages = array();
		$openChats = $this->getOpenChatsFromCookie();
		if(!empty($openChats)){
			$messagesId = $this->query->exec("SELECT id
											FROM `".Tbl::get('TBL_CHAT')."` 
											WHERE
												(
														(`receiver_user_id`='$userId' AND `sender_user_id` IN (".implode(",", $openChats).")) OR
														(`sender_user_id`='$userId' AND `receiver_user_id` IN (".implode(",", $openChats)."))
													) 
											AND `read`='".static::STATUS_READ_UNREAD."'"
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
		$openChats = $this->getOpenChatsFromCookie();
		if(!empty($openChats)){
			$messages = $this->query->exec("SELECT id
											FROM `".Tbl::get('TBL_CHAT')."` 
											WHERE 
												(
													(`receiver_user_id`='$userId' AND `sender_user_id` IN (".implode(",", $openChats).")) OR
													(`sender_user_id`='$userId' AND `receiver_user_id` IN (".implode(",", $openChats)."))
												)
											AND TIMESTAMPDIFF(MINUTE,datetime ,NOW()) < ".self::LOG_MINUTES." AND `read`='".static::STATUS_READ_READ."'"
											)->fetchFields('id');
			foreach ($messages as $msgId){
				$lastMessages[] = $this->getChatMessage($msgId);
			}
		}
		return $lastMessages;
	}
	
	protected function getInvitationMessages($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}
		$invitations = array();
		$openChats = $this->getOpenChatsFromCookie();
		
		$additionalWhere = "";
		if(!empty($openChats)){
			$additionalWhere = "AND `sender_user_id` NOT IN (".implode(",", $openChats) . ")";
		}
		
		$messages = $this->query->exec("SELECT id
										FROM `".Tbl::get('TBL_CHAT')."` 
										WHERE (`receiver_user_id`='$userId' $additionalWhere) 
										AND `read`='".static::STATUS_READ_UNREAD."'"
										)->fetchFields('id');
		foreach ($messages as $msgId){
			$invitations[] = $this->getChatMessage($msgId);
		}
		
		return $invitations;
	}
	
	protected function getOpenChatsFromCookie(){
		if(!empty($_COOKIE['OpenChats'])){
			return explode(',', $_COOKIE['OpenChats']);
		}
		else{
			return array();
		}
	}
	
	protected function getChatObjects($userId, $chatMessages){
		$chatObjects = array();
		foreach ($chatMessages as $chatMessage){
			if($chatMessage->senderUserId == $userId){
				//User's message
				if(empty($chatObjects[$chatMessage->receiverUser->userId])){
					$chat = new Chat();
					$chat->interlocutorId = $chatMessage->receiverUser->userId;
					$chat->interlocutorUserName = $chatMessage->receiverUser->userName;
					$chat->messages[] = $chatMessage;					
					$chatObjects[$chatMessage->receiverUserId] = $chat;
				}
				else{
					$chatObjects[$chatMessage->receiverUserId]->messages[] = $chatMessage;
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
	
	protected function getChatMessage($chatMessageId){
		$messageRow = $this->query->exec("	SELECT *, UNIX_TIMESTAMP(`datetime`) as `timestamp` 
												FROM `".Tbl::get('TBL_CHAT')."` 
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