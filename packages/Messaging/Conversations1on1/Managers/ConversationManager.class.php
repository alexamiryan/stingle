<?php
class ConversationManager extends DbAccessor{
	
	const TBL_CONVERSATIONS 			= "conversations";
	const TBL_CONVERSATION_MESSAGES 	= "conversation_messages";
	
	const STATUS_READ_UNREAD 			= 0;
	const STATUS_READ_READ 				= 1;
	
	const STATUS_TRASHED_NOT_TRAHSED 	= 0;
	const STATUS_TRASHED_TRAHSED 		= 1;
	const STATUS_TRASHED_DELETED 		= 2;
	
	const STATUS_DELETED_NO 			= 0;
	const STATUS_DELETED_YES			= 1;
	const STATUS_DELETED_BOTH			= -1;
	
	const STATUS_HAS_ATTACHMENT_NO		= 0;
	const STATUS_HAS_ATTACHMENT_YES		= 1;
	
	const IS_SYSTEM_NO					= '0';
	const IS_SYSTEM_YES					= '1';
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	public function sendMessage($senderId, $receiverId, $message){
		if(!$this->isConversationExists($senderId, $receiverId)){
			$this->openConversation($senderId, $receiverId);
		}
		
		$filter = new ConversationFilter();
		$filter->setUserId($senderId);
		$filter->setInterlocutorId($receiverId);
		
		$conversation = $this->getConversation($filter);
		$uuid = $conversation->uuid;
		
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
	
	public function sendSystemMessage($uuid, $message){
		if(empty($uuid) or !is_numeric($uuid)){
			throw InvalidArgumentException("UUID have to be non zero integer.");
		}
		
		// Get Conversation
		$filter = new ConversationFilter();
		$filter->setUUID($uuid);
		$filter->setLimit(1);
		$conversation = $this->getConversation($filter, true);
		
		// Insert new message into DB
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->values(
					array(
						'uuid' => $uuid, 
						'message' => $message,
						'system' => self::IS_SYSTEM_YES
						)
					);
		
		$this->query->exec($qb->getSQL());
		$messageId = $this->query->getLastInsertId();
		
		// Get Interlocutors Conversation
		$interFilter = new ConversationFilter();
		$interFilter->setUUID($uuid);
		$interFilter->setUserId($conversation->interlocutorId);
		$interConv = $this->getConversation($interFilter, true);
		
		// Mark conversation as unread for both parties
		$this->markConversationAsUnread($conversation->userId, $uuid);
		$this->markConversationAsUnread($conversation->interlocutorId, $uuid);
		
		// Increment unreads count for both parties
		$this->incrementConversationUnreadCount($conversation);
		$this->incrementConversationUnreadCount($interConv);
		
		// Restore conversation if it is trashed or deleted for user
		if($conversation->trashed != self::STATUS_TRASHED_NOT_TRAHSED){
			$this->restoreConversation($conversation->userId, $uuid);
		}
		
		// Restore conversation if it is trashed or deleted for interlocutor
		if($interConv->trashed != self::STATUS_TRASHED_NOT_TRAHSED){
			$this->restoreConversation($conversation->interlocutorId, $uuid);
		}
		
		// Update Conversation last message date
		$this->updateConversationLastMsgDate($uuid);
		
		return $messageId;
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
		
		$hookParams = array('type'=> 'readStatus', 'uuid' => $uuid, 'userId' => $userId, 'newStatus' => $status);
		HookManager::callHook("ConversationUpdate", $hookParams);
	}
	
	public function trashConversation($userId, $uuid){
		return $this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_TRAHSED);
	}
	
	public function removeConversation($conversation){
		if(empty($conversation->uuid) or !is_numeric($conversation->uuid)){
			throw new InvalidIntegerArgumentException("conversation uuid have to be non zero integer.");
		}
		$this->removeAllConversationMessages($conversation->uuid);
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATIONS'))
			->where($qb->expr()->equal(new Field('uuid'), $conversation->uuid));

		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function removeAllConversationMessages($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
			
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function deleteConversation($userId, $uuid){
		$this->changeTrashedStatus($userId, $uuid, self::STATUS_TRASHED_DELETED);
		
		$messagesLastId = $this->getMessagesLastId();
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('fetch_from'), $messagesLastId)
			->set(new Field('read'), self::STATUS_READ_READ)
			->set(new Field('unread_count'), 0)
			->set(new Field('has_attachment'), self::STATUS_HAS_ATTACHMENT_NO)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('receiver_id'), $userId))
			->andWhere($qb->expr()->lessEqual(new Field('id'), $messagesLastId));
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
		
		$hookParams = array('type'=>'trashStatus','uuid' => $uuid, 'userId' => $userId, 'newStatus' => $status);
		HookManager::callHook("ConversationUpdate", $hookParams);
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
		
		$affected = 0;
		if($message->read == self::STATUS_READ_UNREAD){
			// Change read status
			$qb = new QueryBuilder();
			$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
				->set(new Field('read'), self::STATUS_READ_READ)
				->where($qb->expr()->equal(new Field('id'), $message->id));
			$affected = $this->query->exec($qb->getSQL())->affected();
			
			$this->correctConversationReadStatus($message->uuid, $message->receiverId);
		}
		
		$hookParams = array('msgId' => $message->id,'newStatus' => self::STATUS_READ_READ);
		HookManager::callHook("ConversationMessageUpdate", $hookParams);
		
		return $affected;
	}
	
	public function deleteConversationMessage($conversationMessageId, $myUserId){
		return $this->changeConversationMessageDeletedStatus($conversationMessageId, $myUserId, self::STATUS_DELETED_YES);
	}
	
	public function undeleteConversationMessage($conversationMessageId, $myUserId){
		return $this->changeConversationMessageDeletedStatus($conversationMessageId, $myUserId, self::STATUS_DELETED_NO);
	}
	
	/**
	 * Change Conversation message deleted status.
	 * 
	 * Example:
	 * 
	 * Id1 sends message to Id2
	 * Message is not deleted by anyone 	-> deleted = 0
	 * Message deleted only by Id1 			-> deleted = Id2
	 * Message deleted only by Id2			-> deleted = Id1
	 * Message deleted by both				-> deleted = -1
	 * 
	 * @param integer $conversationMessageId
	 * @param integer $myUserId
	 * @param integer $status
	 * @throws InvalidIntegerArgumentException
	 */
	public function changeConversationMessageDeletedStatus($conversationMessageId, $myUserId, $status){
		if(empty($conversationMessageId) or !is_numeric($conversationMessageId)){
			throw new InvalidIntegerArgumentException("\$conversationMessageId have to be non zero integer.");
		}
		if(!is_numeric($status) or !in_array($status, self::getConstsArray("STATUS_DELETED"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified.");
		}
		if(empty($myUserId) or !is_numeric($myUserId)){
			throw new InvalidIntegerArgumentException("\$myUserId have to be non zero integer.");
		}
	
		// Get message
		$filter = new ConversationMessagesFilter();
		$filter->setId($conversationMessageId);
	
		$message = $this->getConversationMessage($filter, true);
		
		$interlocutorId = null;
		if($message->senderId == $myUserId){
			$interlocutorId = $message->receiverId;
		}
		else{
			$interlocutorId = $message->senderId;
		}
		
		$finalDeletedStatus = null;
		switch($status){
			case self::STATUS_DELETED_YES :
				switch($message->deleted){
					case 0:
						$finalDeletedStatus = $interlocutorId;
						break;
					case -1:
						$finalDeletedStatus = -1;
						break;
					case $myUserId:
						$finalDeletedStatus = -1;
						break;
					case $interlocutorId:
						$finalDeletedStatus = $interlocutorId;
						break;
				}
				break;
			case self::STATUS_DELETED_NO :
				switch($message->deleted){
					case 0:
						$finalDeletedStatus = 0;
						break;
					case -1:
						$finalDeletedStatus = $myUserId;
						break;
					case $myUserId:
						$finalDeletedStatus = $myUserId;
						break;
					case $interlocutorId:
						$finalDeletedStatus = 0;
						break;
				}
				break;
		}
		
		// Change read status
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('deleted'), $finalDeletedStatus)
			->where($qb->expr()->equal(new Field('id'), $message->id));
		
		if($status == self::STATUS_DELETED_YES and $message->receiverId == $myUserId){
			$qb->set(new Field('read'), self::STATUS_READ_READ);
		}
		
		$affected = $this->query->exec($qb->getSQL())->affected();
	
		$this->correctConversationReadStatus($message->uuid, $myUserId);
		$this->correctConversationHasAttachment($message->uuid, $myUserId);
		
		$hookParams = array('type'=>'deletedStatus','msgId' => $message->id,'newStatus' => $finalDeletedStatus);
		HookManager::callHook("ConversationMessageUpdate", $hookParams);
		
		return $affected;
	}
	
	public function wipeConversationMessage($conversationMessageId){
		if(empty($conversationMessageId) or !is_numeric($conversationMessageId)){
			throw new InvalidIntegerArgumentException("\$conversationMessageId have to be non zero integer.");
		}
		
		// Get message
		$filter = new ConversationMessagesFilter();
		$filter->setId($conversationMessageId);
		
		$message = $this->getConversationMessage($filter, true);
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->where($qb->expr()->equal(new Field('id'), $message->id));
		
		$affected = $this->query->exec($qb->getSQL())->affected();
		
		$this->correctConversationReadStatus($message->uuid, $message->senderId);
		$this->correctConversationHasAttachment($message->uuid, $message->senderId);
		
		$this->correctConversationReadStatus($message->uuid, $message->receiverId);
		$this->correctConversationHasAttachment($message->uuid, $message->receiverId);
		
		return $affected;
	}
	
	public function correctConversationReadStatus($uuid, $userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$receiverUserId have to be non zero integer.");
		}
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		
		// Get Conversation of message receiver
		$conversationFilter = new ConversationFilter();
		$conversationFilter->setUUID($uuid);
		$conversationFilter->setUserId($userId);
		$conversation = $this->getConversation($conversationFilter, true);
		
		$unreadFilter = new ConversationMessagesFilter();
		$unreadFilter->setReceiverId($userId);
		$unreadFilter->setDeletedStatus(ConversationManager::STATUS_DELETED_NO, $userId);
		$unreadFilter->setUUID($uuid);
		if($conversation->fetchFrom != null){
			$unreadFilter->setIdGreater($conversation->fetchFrom);
		}
		$unreadFilter->setReadStatus(self::STATUS_READ_UNREAD);
		
		$unreadsCount = $this->getConversationMessagesCount($unreadFilter);
		
		if($unreadsCount == 0){
			$this->markConversationAsRead($userId, $uuid);
		}
		$this->setConversationUnreadCount($conversation, $unreadsCount);
	}
	
	public function correctConversationHasAttachment($uuid, $userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$receiverUserId have to be non zero integer.");
		}
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		// Get Conversation of user
		$conversationFilter = new ConversationFilter();
		$conversationFilter->setUUID($uuid);
		$conversationFilter->setUserId($userId);
		$conversation = $this->getConversation($conversationFilter);
	
		// Find how much messages with attachments in conversation
		$filter = new ConversationMessagesFilter();
		$filter->setUUID($uuid);
		$filter->setDeletedStatus(ConversationManager::STATUS_DELETED_NO, $userId);
		$filter->setHasAttachment(self::STATUS_HAS_ATTACHMENT_YES);
		if($conversation->fetchFrom != null){
			$filter->setIdGreater($conversation->fetchFrom);
		}
	
		$msgsWithAttachmentsCount = $this->getConversationMessagesCount($filter);
		
		// Correct has attachment status accordingly
		if($msgsWithAttachmentsCount == 0 and $conversation->hasAttachment == self::STATUS_HAS_ATTACHMENT_YES){
			$this->setConversationHasNoAttachment($uuid, $userId);
		}
		elseif($msgsWithAttachmentsCount > 0 and $conversation->hasAttachment == self::STATUS_HAS_ATTACHMENT_NO){
			$this->setConversationHasAttachment($uuid, $userId);
		}
	}
	
	public function getConversationsUnreadCountsSum(ConversationFilter $filter){
		$filter->setSelectSum('unread_count');
		
		$sqlQuery = $filter->getSQL();
		
		return $this->query->exec($sqlQuery)->fetchField("sum");
	}
	
	protected function setConversationUnreadCount(Conversation $conversation, $unreadCount){
		if(!is_numeric($unreadCount)){
			throw new InvalidIntegerArgumentException("\$unreadCount have to be integer.");
		}
		
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('unread_count'), $unreadCount)
			->where($qb->expr()->equal(new Field('id'), $conversation->id));
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function incrementConversationUnreadCount(Conversation $conversation){
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('unread_count'), $qb->expr()->sum(new Field('unread_count'), 1))
			->where($qb->expr()->equal(new Field('id'), $conversation->id));
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function decrementConversationUnreadCount(Conversation $conversation){
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('unread_count'), $qb->expr()->diff(new Field('unread_count'), 1))
			->where($qb->expr()->equal(new Field('id'), $conversation->id));
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function markAllMessagesAsRead($uuid, $receiverUserId){
		if(empty($receiverUserId) or !is_numeric($receiverUserId)){
			throw new InvalidIntegerArgumentException("\$receiverUserId have to be non zero integer.");
		}
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		// Get message IDs that we gona mark as read to be able to call update hook on them
		$qb = new QueryBuilder();
		$qb->select(new Field('id'))
			->from(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->where($qb->expr()->equal(new Field('read'), self::STATUS_READ_UNREAD))
			->andWhere($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('receiver_id'), $receiverUserId));
		$this->query->exec($qb->getSQL());
		
		$msgIds = $this->query->fetchFields('id');
		
		// Change read status on all messages
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('receiver_id'), $receiverUserId));
		$this->query->exec($qb->getSQL());
		
		// Correct read fields in Conversations table
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->set(new Field('unread_count'), 0)
			->where($qb->expr()->equal(new Field('uuid'), $uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), $receiverUserId));
		$this->query->exec($qb->getSQL());
		
		$hookParams = array('type'=>'allMessagesRead');
		HookManager::callHook("ConversationUpdate", $hookParams);
		
		foreach ($msgIds as $msgId){
			$hookParams = array('type'=> 'readStatus', 'uuid' => $uuid, 'userId' => $receiverUserId, 'newStatus' => self::STATUS_READ_READ);
			HookManager::callHook("ConversationMessageUpdate", $hookParams);
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
			->set(new Field('last_msg_date'), ($date !== null ? $date : new Func("NOW")))
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
		$this->query->exec($qb->getSQL());
		
		$hookParams = array('type'=> 'lastMsgDate', 'uuid' => $uuid, 'date' => ($date !== null ? $date : date(DEFAULT_DATE_FORMAT)));
		HookManager::callHook("ConversationUpdate", $hookParams);
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
		
		$this->setConversationHasAttachment($message->uuid);
		
		$hookParams = array('type'=> 'hasAttach', 'msgId' => $message->id, 'hasAttach' => 1);
		HookManager::callHook("ConversationMessageUpdate", $hookParams);
	}
	
	public function setConversationHasAttachment($uuid, $userId = null){
		$this->changeConversationHasAttachmentStatus($uuid, self::STATUS_HAS_ATTACHMENT_YES, $userId);
	}
	
	public function setConversationHasNoAttachment($uuid, $userId = null){
		$this->changeConversationHasAttachmentStatus($uuid, self::STATUS_HAS_ATTACHMENT_NO, $userId);
	}
	
	public function changeConversationHasAttachmentStatus($uuid, $status, $userId = null){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		if(!is_numeric($status) or !in_array($status, $this->getConstsArray("STATUS_HAS_ATTACHMENT"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		if($userId !== null and (empty($userId) or !is_numeric($userId))){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('has_attachment'), $status)
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
		
		if($userId !== null and !empty($userId) and is_numeric($userId)){
			$qb->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
		}
		
		$this->query->exec($qb->getSQL());
		
		$hookParams = array('type'=> 'hasAttach', 'uuid' => $uuid, 'hasAttach' => $status);
		HookManager::callHook("ConversationUpdate", $hookParams);
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
			->values(
					array(
						'uuid' => $uuid, 
						'sender_id' => $senderId,
						'receiver_id' => $conversation->interlocutorId,
						'message' => $message
						)
					);
		
		$this->query->exec($qb->getSQL());
		$messageId = $this->query->getLastInsertId();
		
		// Get Interlocutors Conversation
		$interFilter = new ConversationFilter();
		$interFilter->setUUID($uuid);
		$interFilter->setUserId($conversation->interlocutorId);
		$interConv = $this->getConversation($interFilter, true);
		
		// Mark conversation as unread for interlocutor
		$this->markConversationAsUnread($interConv->userId, $uuid);
		
		// Increment unreads count for interlocutor
		$this->incrementConversationUnreadCount($interConv);
		
		// Restore conversation if it is trashed or deleted for user
		if($conversation->trashed != self::STATUS_TRASHED_NOT_TRAHSED){
			$this->restoreConversation($conversation->userId, $uuid);
		}
		
		// Restore conversation if it is trashed or deleted for interlocutor
		if($interConv->trashed != self::STATUS_TRASHED_NOT_TRAHSED){
			$this->restoreConversation($conversation->interlocutorId, $uuid);
		}
		
		// Update Conversation last message date
		$this->updateConversationLastMsgDate($uuid);
		
		return $messageId;
	}
	
	public function getMaxUUID(){
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
	
	public function openConversation($userId1, $userId2){
		if(empty($userId1) or !is_numeric($userId1)){
			throw new InvalidIntegerArgumentException("\$userId1 have to be non zero integer.");
		}
		if(empty($userId2) or !is_numeric($userId2)){
			throw new InvalidIntegerArgumentException("\$userId2 have to be non zero integer.");
		}
				
		$db = MySqlDbManager::getDbObject();
		
		$db->startTransaction(true);
		
		$newUUID = $this->getMaxUUID() + 1;
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_CONVERSATIONS'))
			->values(
					array(
						'uuid' => $newUUID, 
						'user_id' => $userId1, 
						'interlocutor_id' => $userId2
						)
					);
		
		$this->query->exec($qb->getSQL());
		
		$qb->insert(Tbl::get('TBL_CONVERSATIONS'))
			->values(
					array(
						'uuid' => $newUUID,
						'user_id' => $userId2,
						'interlocutor_id' => $userId1
						)
					);
		
		$this->query->exec($qb->getSQL());
		
		if(!$db->commit()){
			$db->rollBack();
			return false;
		}
		
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
			$UserManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
			
			try{
				$conversation->user = $UserManager->getUserById($conversationRow['user_id']);
				$conversation->interlocutor = $UserManager->getUserById($conversationRow['interlocutor_id']);
			}
			catch(UserNotFoundException $e){ }
		}
		$conversation->lastMsgDate = $conversationRow['last_msg_date'];
		$conversation->read = $conversationRow['read'];
		$conversation->unreadCount = $conversationRow['unread_count'];
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
			$UserManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
			
			try{
				if(!empty($messageRow['sender_id'])){
					$message->sender = $UserManager->getUserById($messageRow['sender_id']);
				}
				if(!empty($messageRow['receiver_id'])){
					$message->receiver = $UserManager->getUserById($messageRow['receiver_id']);
				}
			}
			catch(UserNotFoundException $e){ }
			catch(InvalidArgumentException $e){ }
		}
		$message->message = $messageRow['message'];
		$message->read = $messageRow['read'];
		$message->deleted = $messageRow['deleted'];
		$message->hasAttachment = $messageRow['has_attachment'];
		$message->data = $messageRow["data"];
		$message->system = $messageRow["system"];
		
		if(!$reduced and $message->hasAttachment == '1'){
			$attachMgr = Reg::get(ConfigManager::getConfig("Messaging", "Conversations")->Objects->ConversationAttachmentManager);
			
			$filter = new ConversationAttachmentFilter();
			$filter->setMessageId($message->id);
			
			$message->attachments = $attachMgr->getAttachments($filter);
		}
	
		return $message;
	}
}
