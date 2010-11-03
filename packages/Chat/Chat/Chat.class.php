<?php
class Chat extends Filterable
{
	const TBL_CHAT = 'chat';
	
	const STATUS_READ_UNREAD = 0;
	const STATUS_READ_READ = 1;
	
	const IS_SYSTEM_YES = 1;
	const IS_SYSTEM_NO = 0;
	
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
	
	public function isUnreadMessages($receiverUserId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidArgumentException("Invalid receiverUserId specified!");
		}
		
		$unreadsCount = $this->query->exec("	SELECT COUNT(*) as `cnt` 
												FROM `".Tbl::get('TBL_CHAT')."` 
												WHERE `receiver_user_id`='$receiverUserId' and `read`=0"
											)->fetchField('cnt');
		if($unreadsCount > 0){
			return true;
		}
		return false;
	}
	
	public function getUnreadMessagesInfo($receiverUserId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidArgumentException("Invalid receiverUserId specified!");
		}
		
		$unreadsInfo = $this->query->exec("	SELECT `sender_user_id`, COUNT(`sender_user_id`) as `unread_count` 
												FROM `".Tbl::get('TBL_CHAT')."` 
												WHERE 	`receiver_user_id`='$receiverUserId' and `read`=0
												GROUP BY `sender_user_id`")->fetchRecords();
		
		return $unreadsInfo;
	}
	
	public function getUnreadMessages($receiverUserId, $senderUserId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidArgumentException("Invalid receiverUserId specified!");
		}
		if(empty($senderUserId) or !is_numeric($senderUserId)){
			throw new InvalidArgumentException("Invalid senderUserId specified!");
		}
		
		$filter = new ChatMessageFilter();
		$filter->setReceiver($receiverUserId);
		$filter->setSenderAndSystem($senderUserId);
		$filter->setReadStatus(static::STATUS_READ_UNREAD);
		
		return $this->getChatMessages($filter);
	}
	
	public function setAsRead($receiverUserId, $senderUserId, $lastReceivedMessageId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidArgumentException("Invalid receiverUserId specified!");
		}
		if(empty($senderUserId) or !is_numeric($senderUserId)){
			throw new InvalidArgumentException("Invalid senderUserId specified!");
		}
		if(empty($lastReceivedMessageId) or !is_numeric($lastReceivedMessageId)){
			throw new InvalidArgumentException("Invalid lastReceivedMessageId specified!");
		}
		
		$filter = new ChatMessageFilter();
		$filter->setReceiver($receiverUserId);
		$filter->setSenderAndSystem($senderUserId);
		$filter->setReadStatus(static::STATUS_READ_UNREAD);
		$filter->setMessageId($lastReceivedMessageId, Filter::MATCH_LESS_EQUAL);
		
		$messagesToMark = $this->getChatMessages($filter);
		
		foreach($messagesToMark as $message){
			$this->query->exec("	UPDATE `".Tbl::get('TBL_CHAT')."`
									SET `read` = 1 
									WHERE 	`id`='{$message->id}'");
		}
	}
	
	public function sendMessage(ChatMessage $chatMessage){
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
	
	public function getChatMessages(ChatMessageFilter $filter = null, $pager = null, $cacheMinutes = 0){
		$chatMessages = array();
		
		if($filter == null){
			$filter = new ChatMessageFilter();
		}
		
		$sqlQuery = "SELECT `chat`.`id`
						FROM `".Tbl::get('TBL_CHAT')."` `chat`
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
	
	public function getChatMessage($chatMessageId){
		$messageRow = $this->query->exec("	SELECT *, UNIX_TIMESTAMP(`datetime`) as `timestamp` 
												FROM `".Tbl::get('TBL_CHAT')."` 
												WHERE 	`id`='$chatMessageId'")->fetchRecord();
		$chatMessageObj = new ChatMessage();
		$chatMessageObj->id = $messageRow['id'];
		$chatMessageObj->senderUserId = $messageRow['sender_user_id'];
		$chatMessageObj->receiverUserId = $messageRow['receiver_user_id'];
		$chatMessageObj->datetime = $messageRow['datetime'];
		$chatMessageObj->timestamp = $messageRow['timestamp'];
		$chatMessageObj->message = $messageRow['message'];
		$chatMessageObj->read = $messageRow['read'];
		$chatMessageObj->is_system = $messageRow['is_system'];
		
		return $chatMessageObj;
	}
}
?>