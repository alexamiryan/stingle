<?
class ConversationManager extends DbAccessor{
	
	const TBL_CONVERSATIONS 			= "conversations";
	const TBL_CONVERSATION_MESSAGES 	= "conversation_messages";
	
	const STATUS_READ_UNREAD 			= 0;
	const STATUS_READ_READ 				= 1;
	
	const STATUS_TRASHED_NOT_TRAHSED 	= 0;
	const STATUS_TRASHED_TRAHSED 		= 1;
	const STATUS_TRASHED_DELETED 		= 2;
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	public function sendMessage($senderId, $receiverId, $message){
		if(!$this->isConversationExists($senderId, $receiverId)){
			$this->openConversation($senderId, $receiverId);
		}
		
		$filter = new ConversationFilter();
		$filter->setUserId($senderId)->setInterlocutorId($receiverId);
		
		$conversation = $this->getConversation($filter);
		
		if(!$this->isConversationBelongsToUser($conversation->uuid, $senderId)){
			throw new ConversationNotOwnException("Conversation does not belong to user");
		}
		
		$this->addMessageToConversation($conversation->uuid, $senderId, $message);
	}
	
	public function sendMessageByUUID($uuid, $senderId, $message){
		$filter = new ConversationFilter();
		$filter->setUUID($uuid);
		
		$count = $this->getConversationsCount($filter);
		if($count == 0){
			throw new ConversationNotExistException("There is no conversation with uuid $uuid");
		}
		
		if(!$this->isConversationBelongsToUser($uuid, $senderId)){
			throw new ConversationNotOwnException("Conversation does not belong to user");
		}
		
		$this->addMessageToConversation($uuid, $senderId, $message);
	}
	
	public function markConversationAsRead($userId, $uuid){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		$qb = new QueryBuilder();
		
		$maxMessagesId = $this->query->exec(
				$qb->select($qb->expr()->max(new Field('uuid')))->from(Tbl::get('TBL_CONVERSATION_MESSAGES'))->getSQL()
			)->fetchField('maxId');
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), userId));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function trashConversation($userId, $uuid){
		return $this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_TRAHSED);
	}
	
	public function deleteConversation($userId, $uuid){
		return $this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_DELETED);
	}
	
	public function restoreConversation($userId, $uuid){
		return $this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_NOT_TRAHSED);
	}
	
	public function changeTrashedStatus($userId, $uuid, $status){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		if(!in_array($status, $this->getConstsArray("STATUS_TRASHED"))){
			throw new InvalidIntegerArgumentException("Invalid status specified.");
		}
	
		$qb = new QueryBuilder();
	
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('trashed'), $status)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), userId));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function getConversations(ConversationFilter $filter, MysqlPager $pager = null){
		$conversations = array();
		
		$sqlQuery = $filter->getSQL();
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery);
		}
		else{
			$this->query->exec($sqlQuery);
		}
		
		$conversationRows = $this->query->fetchRecords();
		
		foreach ($conversationRows as $conversationRow){
			array_push($conversations, $this->getConversationObject($conversationRow));
		}
		
		return $conversations;
	}
	
	/**
	 * Get single conversation if based on filter
	 * 
	 * @param ConversationFilter $filter
	 * @throws ConversationNotUniqueException
	 * @return Conversation
	 */
	public function getConversation(ConversationFilter $filter){
		$conversations = $this->getConversations($filter);
		if(count($conversations) !== 1){
			throw new ConversationNotUniqueException("There is no such conversation or it is not unique.");
		}
		return $conversations[0];
	}
	
	public function getConversationsCount(ConversationFilter $filter){
		$filter->setSelectCount();
		
		$sqlQuery = $filter->getSQL();

		return $this->query->exec($sqlQuery)->fetchField("cnt");
	}
	
	public function getConversationMessages(ConversationMessagesFilter $filter, MysqlPager $pager = null, $reduced = false){
		$messages = array();
	
		$sqlQuery = $filter->getSQL();
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery);
		}
		else{
			$this->query->exec($sqlQuery);
		}
		
		$messageRows = $this->query->fetchRecords();
	
		foreach ($messageRows as $messageRow){
			array_push($messages, $this->getConversationMessageObject($messageRow, $reduced));
		}
	
		return $messages;
	}
	
	/**
	 * Get single conversation if based on filter
	 *
	 * @param ConversationFilter $filter
	 * @throws ConversationNotUniqueException
	 * @return Conversation
	 */
	public function getConversationMessage(ConversationMessagesFilter $filter, $reduced = false){
		$messages = $this->getConversationMessages($filter, null, $reduced);
		if(count($messages) !== 1){
			throw new ConversationNotUniqueException("There is no conversation message or it is not unique.");
		}
		return $messages[0];
	}
	
	public function getConversationMessagesCount(ConversationMessagesFilter $filter){
		$filter->setSelectCount();
	
		$sqlQuery = $filter->getSQL();
	
		return $this->query->exec($sqlQuery)->fetchField("cnt");
	}
	
	public function getMessagesLastId(){
		$qb = new QueryBuilder();
		
		$qb->select($qb->expr()->max(new Field('id'), 'maxId'))
			->from(Tbl::get('TBL_CONVERSATION_MESSAGES'));
		
		return $this->query->exec($qb->getSQL())->fetchField('maxId');
	}
	
	public function markConversationMessageAsRead($conversationMessageId){
		if(empty($conversationMessageId) or !is_numeric($conversationMessageId)){
			throw new InvalidIntegerArgumentException("\$conversationMessageId have to be non zero integer.");
		}
	
		$qb = new QueryBuilder();
	
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->where($qb->expr()->equal(new Field('id'), $conversationMessageId));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function isConversationExists($userId1, $userId2){
		if(empty($userId1) or !is_numeric($userId1)){
			throw new InvalidIntegerArgumentException("\$userId1 have to be non zero integer.");
		}
		if(empty($userId2) or !is_numeric($userId2)){
			throw new InvalidIntegerArgumentException("\$userId2 have to be non zero integer.");
		}
		
		$filter = new ConversationFilter();
		$filter->setUserId($userId1);
		$filter->setInterlocutorId($userId2);
		
		$count = $this->getConversationsCount($filter);
		
		return ($count == 1 ? true : false);
	}
	
	public function updateConversationLastMsgDate($uuid, $date = null){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('last_msg_date'), ($date !== null ? $date : 'NOW()'))
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function isConversationBelongsToUser($uuid, $userId){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
		
		$filter = new ConversationFilter();
		$filter->setUserId($userId);
		$filter->setUUID($uuid);
		
		$count = $this->getConversationsCount($filter);
		
		return ($count == 1 ? true : false);
	}
	
	public function setMessageHasAttachment(ConversationMessage $message){
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('has_attachment'), 1)
			->where($qb->expr()->equal(new Field('id'), $message->id));
		
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('has_attachment'), 1)
			->where($qb->expr()->equal(new Field('uuid'), $message->uuid));
		
		$this->query->exec($qb->getSQL());
	}
	
	protected function addMessageToConversation($uuid, $senderId, $message){
		if(empty($uuid) or !is_numeric($uuid)){
			throw InvalidArgumentException("UUID have to be non zero integer.");
		}
		if(empty($senderId) or !is_numeric($senderId)){
			throw new InvalidIntegerArgumentException("senderId have to be non zero integer.");
		}
		if(empty($message)){
			throw InvalidArgumentException("message have to be non empty string.");
		}
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->values(array(
					'uuid'=>$uuid, 
					'sender_id'=>$senderId,
					'message'=>$message));
		
		$this->query->exec($qb->getSQL());
		
		return $this->query->affected();
	}
	
	protected function getMaxUUID(){
		$qb = new QueryBuilder();
	
		$sqlQuery = $qb->select($qb->expr()->max(new Field('uuid'), 'maxId'))
			->from(Tbl::get('TBL_CONVERSATIONS'))
			->getSQL();
	
		return $this->query->exec($sqlQuery)->fetchField('maxId');
	}
	
	protected function getMaxMessagesId(){
		$qb = new QueryBuilder();
		
		$sqlQuery = $qb->select($qb->expr()->max(new Field('id'), 'maxId'))
			->from(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->getSQL();
		
		return $this->query->exec($sqlQuery)->fetchField('maxId');
	}
	
	protected function openConversation($userId1, $userId2){
		if(empty($userId1) or !is_numeric($userId1)){
			throw new InvalidIntegerArgumentException("\$userId1 have to be non zero integer.");
		}
		if(empty($userId2) or !is_numeric($userId2)){
			throw new InvalidIntegerArgumentException("\$userId2 have to be non zero integer.");
		}
		
		$newUUID = $this->getMaxUUID() + 1;
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_CONVERSATIONS'))
			->values(array(
					'uuid'=>$newUUID, 
					'user_id'=>$userId1, 
					'interlocutor_id'=>$userId2));
		
		$this->query->exec($qb->getSQL());
		
		return $this->query->affected();
	}
	
	protected function getNewUUID(){
		$found = false;
		$uuid = '';
		while(!$found){
			$uuid = generateRandomString(32);
			
			$qb = new QueryBuilder();
			
			$qb->select($qb->expr()->count("*", 'cnt'))
				->from(Tbl::get('TBL_CONVERSATIONS'))
				->where($qb->expr()->equal(new Field('uuid'), $uuid));
			
			$count = $this->query->exec($qb->getSQL())->fetchField('cnt');
			
			if($count == 0){
				$found = true;
			}
		}
		
		return $uuid;
	}
	
	protected function getConversationObject($conversationRow){
		$conversation = new Conversation();
		
		$userManagement = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManagement);
		
		$conversation->id = $conversationRow['id'];
		$conversation->uuid = $conversationRow['uuid'];
		$conversation->user = $userManagement->getObjectById($conversationRow['user_id']);
		$conversation->interlocutor = $userManagement->getObjectById($conversationRow['interlocutor_id']);
		$conversation->lastMsgDate = $conversationRow['last_msg_date'];
		$conversation->read = $conversationRow['read'];
		$conversation->trashed = $conversationRow['trashed'];
		$conversation->fetchFrom = $conversationRow['fetch_from'];
		$conversation->hasAttachment = $conversationRow['has_attachment'];
		
		return $conversation;
	}
	
	protected function getConversationMessageObject($messageRow, $reduced = false){
		$message = new ConversationMessage();
	
		$userManagement = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManagement);
	
		$message->id = $messageRow['id'];
		$message->uuid = $messageRow['uuid'];
		$message->date = $messageRow['date'];
		$message->senderId = $messageRow['sender_id'];
		if(!$reduced){
			$message->sender = $userManagement->getObjectById($messageRow['sender_id']);
		}
		$message->message = $messageRow['message'];
		$message->read = $messageRow['read'];
		$message->hasAttachment = $messageRow['has_attachment'];
		
		if(!$reduced and $message->hasAttachment == '1'){
			$attachMgr = Reg::get(ConfigManager::getConfig("Messaging", "Conversations")->Objects->ConversationAttachmentManager);
			
			$filter = new ConversationAttachmentFilter();
			$filter->setMessageId($message->id);
			
			$message->attachments = $attachMgr->getAttachments($filter);
		}
	
		return $message;
	}
}
?>