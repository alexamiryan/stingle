<?
class ChatMessageFilter extends Filter {
	
	public function setMessageId($messageId, $match = Filter::MATCH_EQUAL){
		if(!is_numeric($messageId)){
			throw new InvalidIntegerArgumentException("\$messageId have to be non zero integer");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(ChatMessageManager::FILTER_ID_FIELD, $match, $messageId);
		return $this;
	}
	
	public function setMessageTextLike($text){
		if(empty($text)){
			throw new InvalidArgumentException("\$text have to be non empty string");
		}
		$this->setCondition(ChatMessageManager::FILTER_MESSAGE_FIELD, Filter::MATCH_CONTAINS, $text);
		return $this;
	}
	
	public function setSender($senderUserId){
		if(empty($senderUserId) or !is_numeric($senderUserId)){
			throw new InvalidArgumentException("\$senderUserId have to be non zero integer");
		}
		$this->setCondition(ChatMessageManager::FILTER_SENDER_USER_ID_FIELD, Filter::MATCH_EQUAL, $senderUserId);
		return $this;
	}
	
	public function setReceiver($receiverUserId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidArgumentException("\$receiverUserId have to be non zero integer");
		}
		$this->setCondition(ChatMessageManager::FILTER_RECEIVER_USER_ID_FIELD, Filter::MATCH_EQUAL, $receiverUserId);
		return $this;
	}
	
	public function setIsSystem($isSystem = Chat::IS_SYSTEM_YES){
		if(!in_array($isSystem, Chat::getConstsArray('IS_SYSTEM'))){
			throw new InvalidArgumentException("Invalid \$isSystem specified");
		}
		$this->setCondition(ChatMessageManager::FILTER_IS_SYSTEM_FIELD, Filter::MATCH_EQUAL, $isSystem);
		return $this;
	}

	public function setReadStatus($readStatus){
		if(!is_numeric($readStatus)){
			throw new InvalidArgumentException("\$readStatus have to be integer");
		}
		if(!in_array($readStatus, Chat::getConstsArray('STATUS_READ'))){
			throw new InvalidArgumentException("Invalid \$readStatus specified");
		}
		$this->setCondition(ChatMessageManager::FILTER_READ_FIELD, Filter::MATCH_EQUAL, $readStatus);
		return $this;
	}
	
	public function setStartDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->setCondition(ChatMessageManager::FILTER_DATETIME_FIELD, Filter::MATCH_GREATER_EQUAL, $date);
		return $this;
	}
	
	public function setEndDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->setCondition(ChatMessageManager::FILTER_DATETIME_FIELD, Filter::MATCH_LESS_EQUAL, $date);
		return $this;
	}
	
	public function setConversation($userId1, $userId2){
		if(empty($userId1) or !is_numeric($userId1)){
			throw new InvalidArgumentException("\$userId1 have to be non zero integer");
		}
		if(empty($userId2) or !is_numeric($userId2)){
			throw new InvalidArgumentException("\$userId2 have to be non zero integer");
		}
		
		$this->setCustomWhere("(
									( 
										`chat_messages`.`".ChatMessageManager::FILTER_RECEIVER_USER_ID_FIELD."` = '$userId1' 
										AND
										`chat_messages`.`".ChatMessageManager::FILTER_SENDER_USER_ID_FIELD."` = '$userId2'
									) 
									OR 
									( 
										`chat_messages`.`".ChatMessageManager::FILTER_RECEIVER_USER_ID_FIELD."` = '$userId2'
										AND 
										`chat_messages`.`".ChatMessageManager::FILTER_SENDER_USER_ID_FIELD."` = '$userId1'
									)
								)");
		return $this;
	}
	
	public function setAllMessagesWithInterlocutors($myUserId, $interlocutorsIds){
		if(empty($myUserId) or !is_numeric($myUserId)){
			throw new InvalidArgumentException("\$myUserId have to be non zero integer");
		}
		if(!is_array($interlocutorsIds)){
			throw new InvalidArgumentException("\$interlocutorsIds have to be array");
		}
		
		$this->setCustomWhere("(
									(
										`chat_messages`.`".ChatMessageManager::FILTER_RECEIVER_USER_ID_FIELD."`='$myUserId' AND 
										`chat_messages`.`".ChatMessageManager::FILTER_SENDER_USER_ID_FIELD."` IN (".implode(",", $interlocutorsIds).")
									) 
									OR
									(
										`chat_messages`.`".ChatMessageManager::FILTER_SENDER_USER_ID_FIELD."`='$myUserId' AND 
										`chat_messages`.`".ChatMessageManager::FILTER_RECEIVER_USER_ID_FIELD."` IN (".implode(",", $interlocutorsIds).")
									)
								)");
		return $this;
	}
	
	public function setLogTime($minutes){
		if(!is_numeric($minutes)){
			throw new InvalidArgumentException("\$minutes have to be integer");
		}
		
		$this->setCustomWhere("TIMESTAMPDIFF(MINUTE,`chat_messages`.`".ChatMessageManager::FILTER_DATETIME_FIELD."` ,NOW()) < $minutes");
		return $this;
	}
}
?>