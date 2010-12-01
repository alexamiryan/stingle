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
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
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
	
	public function getLastId(){
		$this->query->exec("SELECT id FROM `".Tbl::get('TBL_CHAT')."` ORDER BY id DESC LIMIT 1");
		return $this->query->fetchField('id');
	}
	
	protected function getNewMessages($lastId, $userId){
		if(empty($userId) or !is_numeric($lastId)){
			throw new InvalidArgumentException("Invalid arguments specified!");
		}
		$newMessages = array();
		$messagesId = $this->query->exec("SELECT id
										FROM `".Tbl::get('TBL_CHAT')."` 
										WHERE id> '$lastId'
										AND  (`receiver_user_id`='$userId' OR `sender_user_id`='$userId')"
										)->fetchFields('id');
		foreach ($messagesId as $msgId){
			$newMessages[] = $this->getChatMessage($msgId);
		}
		return $newMessages;													
		
	}
	
	protected function getLastMessages($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("Invalid userId specified!");
		}
		$lastMessages = array();
		$messages = $this->query->exec("SELECT id
										FROM `".Tbl::get('TBL_CHAT')."` 
										WHERE (`receiver_user_id`='$userId' OR `sender_user_id`='$userId')
										AND TIMESTAMPDIFF(MINUTE,datetime ,NOW()) < ".self::LOG_MINUTES.""
										)->fetchFields('id');
		foreach ($messages as $msgId){
			$lastMessages[] = $this->getChatMessage($msgId);
		}
		return $lastMessages;
	}
	
	public function getChatState($userId, $lastId = null){
		$chatState = array('chats'=>array(),'invitations'=>array(),'lastId'=>$this->getLastId());
		if($lastId === null){
			$chatMessages = $this->getLastMessages($userId);
		}
		else{
			$chatMessages = $this->getNewMessages($lastId, $userId);
		}
		//Get Ids of open(accepted) chats
		$openChats = explode(',', $_COOKIE['OpenChats']);
		foreach ($chatMessages as $chatMessage){
			if($chatMessage->senderUserId == $userId){
				//User's message
				if(in_array($chatMessage->receiverUserId, $openChats)){
					//Open chat
					if(empty($chatState['chats'][$chatMessage->receiverUserId])){
						$chat = new Chat();
						$chat->interlocutorId = $chatMessage->receiverUserId;
						$chat->interlocutorUserName = $chatMessage->receiverUserName;
						$chat->messages[] = $chatMessage;					
						$chatState['chats'][$chatMessage->receiverUserId] = $chat;
					}
					else{
						$chatState['chats'][$chatMessage->receiverUserId]->messages[] = $chatMessage;
					}
				}
			}
			else{
				//Not user's message
				if(in_array($chatMessage->senderUserId, $openChats)){
					//Open chat
					if(empty($chatState['chats'][$chatMessage->senderUserId])){
						$chat = new Chat();
						$chat->interlocutorId = $chatMessage->senderUserId;
						$chat->interlocutorUserName = $chatMessage->senderUserName;
						$chat->messages[] = $chatMessage;					
						$chatState['chats'][$chatMessage->senderUserId] = $chat;
					}
					else{
						$chatState['chats'][$chatMessage->senderUserId]->messages[] = $chatMessage;
					}	
				}
				else{
					//Invitation
					if($chatMessage->read == 0){
						if(empty($chatState['invitations'][$chatMessage->senderUserId])){
							$invitation = new ChatInvitation();
							$invitation->inviterId = $chatMessage->senderUserId;
							$invitation->inviterUserName = $chatMessage->senderUserName;
							$chatState['invitations'][$chatMessage->senderUserId] = $invitation;
						}
					}
				}
			}
		}
		return $chatState;
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
	
	
	public function getChatMessage($chatMessageId){
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