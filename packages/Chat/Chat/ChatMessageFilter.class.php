<?php
class ChatMessageFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
	
		$this->qb->select(new Field("*", "chat_msg"))
			->from(Tbl::get('TBL_CHAT_MESSAGES', 'ChatMessageManager'), "chat_msg");
	}
	
	public function setMessageId($messageId){
		if(!is_numeric($messageId)){
			throw new InvalidIntegerArgumentException("\$messageId have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "chat_msg"), $messageId));
		return $this;
	}
	
	public function setMessageIdGreater($messageId){
		if(!is_numeric($messageId)){
			throw new InvalidIntegerArgumentException("\$messageId have to be non zero integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->greater(new Field("id", "chat_msg"), $messageId));
		return $this;
	}
	
	public function setMessageTextLike($text){
		if(empty($text)){
			throw new InvalidArgumentException("\$text have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->like(new Field("message", "chat_msg"), "%$text%"));
		return $this;
	}
	
	public function setSender($senderUserId){
		if(empty($senderUserId) or !is_numeric($senderUserId)){
			throw new InvalidArgumentException("\$senderUserId have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("sender_user_id", "chat_msg"), $senderUserId));
		return $this;
	}
	
	public function setReceiver($receiverUserId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidArgumentException("\$receiverUserId have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("receiver_user_id", "chat_msg"), $receiverUserId));
		return $this;
	}
	
	public function setIsSystem($isSystem = ChatMessageManager::IS_SYSTEM_YES){
		if(!in_array($isSystem, Chat::getConstsArray('IS_SYSTEM'))){
			throw new InvalidArgumentException("Invalid \$isSystem specified");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("is_system", "chat_msg"), $isSystem));
		return $this;
	}

	public function setStartDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->greaterEqual(new Field("datetime", "chat_msg"), $date));
		return $this;
	}
	
	public function setEndDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->lessEqual(new Field("datetime", "chat_msg"), $date));
		return $this;
	}
	
	public function setConversation($userId1, $userId2){
		if(empty($userId1) or !is_numeric($userId1)){
			throw new InvalidArgumentException("\$userId1 have to be non zero integer");
		}
		if(empty($userId2) or !is_numeric($userId2)){
			throw new InvalidArgumentException("\$userId2 have to be non zero integer");
		}
		
		$andClause1 = new Andx();
		$andClause1->add($this->qb->expr()->equal(new Field('sender_user_id', 'chat_msg'), $userId1));
		$andClause1->add($this->qb->expr()->equal(new Field('receiver_user_id', 'chat_msg'), $userId2));
		
		$andClause2 = new Andx();
		$andClause2->add($this->qb->expr()->equal(new Field('sender_user_id', 'chat_msg'), $userId2));
		$andClause2->add($this->qb->expr()->equal(new Field('receiver_user_id', 'chat_msg'), $userId1));
		
		$orClause = new Orx();
		$orClause->add($andClause1);
		$orClause->add($andClause2);
		
		$this->qb->andWhere($orClause);
		
		return $this;
	}
	
	public function setAllMessagesWithInterlocutors($myUserId, $interlocutorsIds){
		if(empty($myUserId) or !is_numeric($myUserId)){
			throw new InvalidArgumentException("\$myUserId have to be non zero integer");
		}
		if(!is_array($interlocutorsIds)){
			throw new InvalidArgumentException("\$interlocutorsIds have to be array");
		}
		
		$andClause1 = new Andx();
		$andClause1->add($this->qb->expr()->equal(new Field('receiver_user_id', 'chat_msg'), $myUserId));
		$andClause1->add($this->qb->expr()->in(new Field('sender_user_id', 'chat_msg'), implode(",", $interlocutorsIds)));
		
		$andClause2 = new Andx();
		$andClause2->add($this->qb->expr()->equal(new Field('sender_user_id', 'chat_msg'), $myUserId));
		$andClause2->add($this->qb->expr()->equal(new Field('receiver_user_id', 'chat_msg'), implode(",", $interlocutorsIds)));
		
		$orClause = new Orx();
		$orClause->add($andClause1);
		$orClause->add($andClause2);
		
		$this->qb->andWhere($orClause);
		
		return $this;
	}
	
	public function setLogTime($minutes){
		if(!is_numeric($minutes)){
			throw new InvalidArgumentException("\$minutes have to be integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->less(new Func('TIMESTAMPDIFF', array('MINUTE', new Field('datetime', 'chat_msg'), 'NOW()')), $minutes));
		return $this;
	}
	
	public function setOrderDatetimeAsc(){
		$this->setOrder(new Field("datetime", "chat_msg"), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderDatetimeDesc(){
		$this->setOrder(new Field("datetime", "chat_msg"), MySqlDatabase::ORDER_DESC);
	}
}
