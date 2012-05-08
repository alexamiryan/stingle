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
		
		$uuid = null;
		
		$filter = new ConversationFilter();
		$filter->setUserId($senderId);
		$filter->setInterlocutorId($receiverId);
		
		$conversationsCount = $this->getConversationsCount($filter);
		if($conversationsCount == 0){
			$uuid = $this->openConversation($senderId, $receiverId);
		}
		else{
			$filter = new ConversationFilter();
			$filter->setUserId($senderId);
			$filter->setInterlocutorId($receiverId);
			
			$conversation = $this->getConversation($filter);
			$uuid = $conversation->uuid;
		}
		
		if(!$this->isConversationBelongsToUser($uuid, $senderId)){
			throw new ConversationNotOwnException("Conversation does not belong to user");
		}
		
		return $this->addMessageToConversation($uuid, $senderId, $message);
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
		
		return $this->addMessageToConversation($uuid, $senderId, $message);
	}
	
	public function markConversationAsRead($userId, $uuid){
		$this->changeConversationReadStatus($userId, $uuid, self::STATUS_READ_READ);
	}
	
	public function markConversationAsUnread($userId, $uuid){
		$this->changeConversationReadStatus($userId, $uuid, self::STATUS_READ_UNREAD);
	}
	
	public function changeConversationReadStatus($userId, $uuid, $status){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		if(!in_array($status, $this->getConstsArray("STATUS_READ"))){
			throw new InvalidIntegerArgumentException("Invalid status specified.");
		}
	
		$qb = new QueryBuilder();
	
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('read'), $status)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
	
		$this->query->exec($qb->getSQL());
	}
	
	public function trashConversation($userId, $uuid){
		return $this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_TRAHSED);
	}
	
	public function deleteConversation($userId, $uuid){
		$this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_DELETED);
		
		$messagesLastId = $this->getMessagesLastId();
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('fetch_from'), $messagesLastId)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('receiver_id'), $userId))
			->andWhere($qb->expr()->less(new Field('id'), $messagesLastId));
		$this->query->exec($qb->getSQL());
	}
	
	public function restoreConversation($userId, $uuid){
		$this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_NOT_TRAHSED);
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
			->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
	
		$this->query->exec($qb->getSQL());
	}
	
	public function getConversations(ConversationFilter $filter, MysqlPager $pager = null, $reduced = false){
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
			array_push($conversations, $this->getConversationObject($conversationRow, $reduced));
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
	public function getConversation(ConversationFilter $filter, $reduced = false){
		$conversations = $this->getConversations($filter, null, $reduced);
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
	 * @return ConversationMessage
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
	
		// Get message
		$filter = new ConversationMessagesFilter();
		$filter->setId($conversationMessageId);
		
		$message = $this->getConversationMessage($filter, true);
		
		// Change read status
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->where($qb->expr()->equal(new Field('id'), $message->id));
		$this->query->exec($qb->getSQL())->affected();
		
		// Get Conversation of message receiver
		$conversationFilter = new ConversationFilter();
		$conversationFilter->setUUID($message->uuid);
		$conversationFilter->setUserId($message->receiverId);
		$conversation = $this->getConversation($conversationFilter);
		
		$unreadFilter = new ConversationMessagesFilter();
		$unreadFilter->setSenderId($message->senderId);
		$unreadFilter->setUUID($message->uuid);
		if($conversation->fetchFrom != null){
			$unreadFilter->setIdGreater($conversation->fetchFrom);
		}
		$unreadFilter->setReadStatus(self::STATUS_READ_UNREAD);
		
		$unreadsCount = $this->getConversationMessagesCount($unreadFilter);
		
		if($unreadsCount == 0){
			$this->markConversationAsRead($message->receiverId, $message->uuid);
		}
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
		
		$this->query->exec($qb->getSQL());
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
		
		// Get Conversation
		$filter = new ConversationFilter();
		$filter->setUUID($uuid);
		$filter->setUserId($senderId);
		$conversation = $this->getConversation($filter, true);
		
		// Insert new message into DB
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->values(array(
					'uuid'=>$uuid, 
					'sender_id'=>$senderId,
					'receiver_id'=>$conversation->interlocutorId,
					'message'=>$message));
		
		$this->query->exec($qb->getSQL());
		$messageId = $this->query->getLastInsertId();
		
		// Mark conversation as unread for interlocutor
		$this->markConversationAsUnread($conversation->interlocutorId, $uuid);
		
		// Get Interlocutors Conversation
		$filter = new ConversationFilter();
		$filter->setUUID($uuid);
		$filter->setUserId($conversation->interlocutorId);
		$interConv = $this->getConversation($filter, true);
		
		// Restore conversation if it is trashed or deleted
		if($interConv->trashed != self::STATUS_TRASHED_NOT_TRAHSED){
			$this->restoreConversation($conversation->interlocutorId, $uuid);
		}
		
		// Update Conversation last message date
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('last_msg_date'), new Literal((String)new Func("NOW")))
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
		$this->query->exec($qb->getSQL());
		
		return $messageId;
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
		
		$db = MySqlDbManager::getDbObject();
		
		$db->lockTables(Tbl::get('TBL_CONVERSATIONS'), "w");
		
		$newUUID = $this->getMaxUUID() + 1;
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_CONVERSATIONS'))
			->values(array(
					'uuid'=>$newUUID, 
					'user_id'=>$userId1, 
					'interlocutor_id'=>$userId2));
		
		$this->query->exec($qb->getSQL());
		
		$qb->insert(Tbl::get('TBL_CONVERSATIONS'))
			->values(array(
				'uuid'=>$newUUID,
				'user_id'=>$userId2,
				'interlocutor_id'=>$userId1));
		
		$this->query->exec($qb->getSQL());
		
		$db->unlockTables();
		
		return $newUUID;
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
	
	protected function getConversationObject($conversationRow, $reduced = false){
		$conversation = new Conversation();
		
		$conversation->id = $conversationRow['id'];
		$conversation->uuid = $conversationRow['uuid'];
		$conversation->userId = $conversationRow['user_id'];
		$conversation->interlocutorId = $conversationRow['interlocutor_id'];
		if(!$reduced){
			$userManagement = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManagement);
			
			$conversation->user = $userManagement->getObjectById($conversationRow['user_id']);
			$conversation->interlocutor = $userManagement->getObjectById($conversationRow['interlocutor_id']);
		}
		$conversation->lastMsgDate = $conversationRow['last_msg_date'];
		$conversation->read = $conversationRow['read'];
		$conversation->trashed = $conversationRow['trashed'];
		$conversation->fetchFrom = $conversationRow['fetch_from'];
		$conversation->hasAttachment = $conversationRow['has_attachment'];
		
		return $conversation;
	}
	
	protected function getConversationMessageObject($messageRow, $reduced = false){
		$message = new ConversationMessage();
	
		$message->id = $messageRow['id'];
		$message->uuid = $messageRow['uuid'];
		$message->date = $messageRow['date'];
		$message->senderId = $messageRow['sender_id'];
		$message->receiverId = $messageRow['receiver_id'];
		if(!$reduced){
			$userManagement = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManagement);
			
			$message->sender = $userManagement->getObjectById($messageRow['sender_id']);
			$message->receiver = $userManagement->getObjectById($messageRow['receiver_id']);
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