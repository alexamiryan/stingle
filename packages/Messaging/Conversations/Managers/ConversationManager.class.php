<?php
class ConversationManager extends DbAccessor{
	
	const TBL_CONVERSATIONS					= "conversations";
	const TBL_CONVERSATION_MESSAGES			= "conversation_messages";
	const TBL_CONVERSATION_MESSAGES_PROPS 	= "conversation_messages_props";
	
	const MEMCACHE_CONV_TAG = 'conv:uuid';
	const MEMCACHE_CONV_MSG_TAG = 'convmsg:uuid';
	
	const STATUS_READ_UNREAD 			= 0;
	const STATUS_READ_READ 				= 1;
	
	const STATUS_TRASHED_NOT_TRASHED 	= 0;
	const STATUS_TRASHED_TRASHED 		= 1;
	const STATUS_TRASHED_DELETED 		= 2;
	
	const STATUS_DELETED_NO 			= 0;
	const STATUS_DELETED_YES			= 1;
	
	const STATUS_HAS_ATTACHMENT_NO		= 0;
	const STATUS_HAS_ATTACHMENT_YES		= 1;
	
	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_USERS = 1;
	const INIT_PROPS = 2;
	const INIT_ATTACHMENTS = 4;
	const INIT_PARTICIPANTS = 8;
	const INIT_PARTICIPANT_USERS = 16;
	
	// INIT_ALL Should be next power of 2 minus 1
	const INIT_ALL = 31;
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	public function getConversations(ConversationFilter $filter, MysqlPager $pager = null, $initObjects = self::INIT_ALL){
		$conversations = array();
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		
		if($pager !== null){
			$sql = $pager->executePagedSQL($sqlQuery);
		}
		else{
			$sql->exec($sqlQuery);
		}
		
		if ($sql->countRecords()) {
			while (($dbRow = $sql->fetchRecord()) != false) {
				array_push($conversations, $this->getConversationObject($dbRow, $initObjects));
			}
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
	public function getConversation(ConversationFilter $filter, $initObjects = self::INIT_ALL){
		$conversations = $this->getConversations($filter, null, $initObjects);
		if(count($conversations) !== 1){
			throw new ConversationNotUniqueException("There is no such conversation or it is not unique.");
		}
		return $conversations[0];
	}
	
	public function getConversationByUUID($uuid, $userId, $initObjects = self::INIT_ALL){
		$conv = Reg::get('memcache')->getObject(self::MEMCACHE_CONV_TAG . $uuid, $userId . '-' . $initObjects);
		
		if(!empty($conv)){
			return $conv;
		}
		else{
			$filter = new ConversationFilter();
			$filter->setUUID($uuid);
			$filter->setUserId($userId);

			$conv = $this->getConversation($filter, $initObjects);
			Reg::get('memcache')->setObject(self::MEMCACHE_CONV_TAG . $uuid, $userId . '-' . $initObjects, $conv, MemcacheWrapper::MEMCACHE_UNLIMITED);
			return $conv;
		}
	}
	
	public function getConversationsCount(ConversationFilter $filter){
		$filter->setSelectCount();
		
		$sqlQuery = $filter->getSQL();

		return $this->query->exec($sqlQuery)->fetchField("cnt");
	}
	
	public function getConversationMessages(ConversationMessagesFilter $filter, $userId = null, MysqlPager $pager = null, $initObjects = self::INIT_ALL){
		$messages = array();
	
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		
		if($pager !== null){
			$sql = $pager->executePagedSQL($sqlQuery);
		}
		else{
			$sql->exec($sqlQuery);
		}

		if ($sql->countRecords()) {
			while (($dbRow = $sql->fetchRecord()) != false) {
				$msg = Reg::get('memcache')->getObject(self::MEMCACHE_CONV_MSG_TAG . $dbRow['uuid'], $dbRow['id'] . '-' . $userId . '-' . $initObjects);
				if(empty($msg)){
					$msg = $this->getConversationMessageObject($dbRow, $userId, $initObjects);
					Reg::get('memcache')->setObject(self::MEMCACHE_CONV_MSG_TAG . $msg->uuid, $msg->id . '-' . $userId . '-' . $initObjects, $msg, MemcacheWrapper::MEMCACHE_UNLIMITED);
				}
				array_push($messages, $msg);
			}
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
	public function getConversationMessage(ConversationMessagesFilter $filter, $userId = null, $initObjects = self::INIT_ALL){
		$messages = $this->getConversationMessages($filter, $userId, null, $initObjects);
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
	
	public function getConversationMessageProps($messageId, $userId){
		if(empty($messageId) or !is_numeric($messageId)){
			throw new InvalidIntegerArgumentException("\$messageId have to be non zero integer.");
		}
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
			->where($qb->expr()->equal(new Field('message_id'), $messageId))
			->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
		$this->query->exec($qb->getSQL());
		
		$propsRow = $this->query->fetchRecord();
		
		$props = new ConversationMessageProps();
		$props->id = $propsRow['id'];
		$props->uuid = $propsRow['uuid'];
		$props->messageId = $propsRow['message_id'];
		$props->userId = $propsRow['user_id'];
		$props->read = $propsRow['read'];
		$props->deleted = $propsRow['deleted'];
		
		return $props;
	}
	
	public function sendMessage($senderId, $receivers, $message){
		if(empty($receivers) or ! is_array($receivers)){
			throw new InvalidArgumentException("\$receivers have to be non empty array");
		}
		
		$uuids = $this->findExistingConversationUUIDs(array_merge(array($senderId), $receivers));
		
		if(count($uuids) === 0){
			throw new ConversationNotUniqueException("Conversation with selected participants doesn't exist");
		}
		elseif(count($uuids) > 1){
			throw new ConversationNotUniqueException("Conversation with selected participants is not unique");
		}
		
		$uuid = $uuids[0];
		
		if(!$this->isConversationBelongsToUser($uuid, $senderId)){
			throw new ConversationNotOwnException("Conversation does not belong to user");
		}
		
		return $this->addMessageToConversation($uuid, $senderId, $message);
	}
	
	public function sendMessageByUUID($uuid, $senderId, $message){
		if(!$this->isConversationExists($uuid)){
			throw new ConversationNotExistException("There is no conversation with uuid $uuid");
		}
		
		if(!$this->isConversationBelongsToUser($uuid, $senderId)){
			throw new ConversationNotOwnException("Conversation does not belong to user");
		}
		
		return $this->addMessageToConversation($uuid, $senderId, $message);
	}
	
	public function findExistingConversationUUIDs($participants = array()){
		if(empty($participants) or ! is_array($participants)){
			throw new InvalidArgumentException("\$participants have to be non empty array");
		}
		
		$qb = new QueryBuilder();
		
		$qb->select(new Field('uuid', "tbl1"));
		
		$count = 1;
		foreach($participants as $userId){
			$subQb = new QueryBuilder();
			$subQb->select("*")
					->from(Tbl::get('TBL_CONVERSATIONS'))
					->where($subQb->expr()->equal(new Field("user_id"), $userId));
			
			if($count == 1){
				$qb->from($subQb, "tbl$count");
			}
			else{
				$qb->innerJoin($subQb, "tbl$count", $qb->expr()->equal(new Field("uuid", "tbl" . $count), new Field("uuid", "tbl" . ($count-1))));
			}
			$count++;
		}
		
		$this->query->exec($qb->getSQL());
		
		return $this->query->fetchFields('uuid');
	}
	
	public function updateConversation(Conversation $conv){
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('uuid'), $conv->uuid)
			->set(new Field('user_id'), $conv->userId)
			->set(new Field('last_msg_date'), $conv->lastMsgDate)
			->set(new Field('read'), $conv->read)
			->set(new Field('unread_count'), $conv->unreadCount)
			->set(new Field('trashed'), $conv->trashed)
			->set(new Field('has_attachment'), $conv->hasAttachment)
			->where($qb->expr()->equal(new Field('id'), $conv->id));
		
		$this->query->exec($qb->getSQL());
		
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_TAG . $conv->uuid);
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $conv->uuid);
		
		return $this->query->affected();
	}
	
	public function updateConversationMessage(ConversationMessage $msg){
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->set(new Field('uuid'), $msg->uuid)
			->set(new Field('date'), $msg->date)
			->set(new Field('user_id'), $msg->userId)
			->set(new Field('message'), $msg->message)
			->set(new Field('has_attachment'), $msg->hasAttachment)
			->set(new Field('data'), serialize($msg->data))
			->where($qb->expr()->equal(new Field('id'), $msg->id));
		
		if(!empty($msg->props)){
			$this->updateConversationMessageProps($msg->props);
		}
		
		$this->query->exec($qb->getSQL());
		
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $msg->uuid);
		
		return $this->query->affected();
	}
	
	public function updateConversationMessageProps(ConversationMessageProps $props){
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
			->set(new Field('uuid'), $props->uuid)
			->set(new Field('message_id'), $props->messageId)
			->set(new Field('user_id'), $props->userId)
			->set(new Field('read'), $props->read)
			->set(new Field('deleted'), $props->deleted)
			->where($qb->expr()->equal(new Field('id'), $props->id));
		
		$this->query->exec($qb->getSQL());
		
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $props->uuid);
		
		return $this->query->affected();
	}
	
	
	public function markConversationAsRead(Conversation $conv){
		$conv->read = self::STATUS_READ_READ;
		return $this->updateConversation($conv);
	}
	
	public function markConversationAsUnread(Conversation $conv){
		$conv->read = self::STATUS_READ_UNREAD;
		return $this->updateConversation($conv);
	}
	
	
	public function trashConversation(Conversation $conv){
		$conv->trashed = self::STATUS_TRASHED_TRASHED;
		return $this->updateConversation($conv);
	}
	
	public function restoreConversation(Conversation $conv){
		$conv->trashed = self::STATUS_TRASHED_NOT_TRASHED;
		
		return $this->updateConversation($conv);
	}
	
	public function deleteConversation(Conversation $conv){
		$this->markAllMessagesAsRead($conv);
		$this->markAllMessagesAsDeleted($conv);
		$conv->trashed = self::STATUS_TRASHED_DELETED;
		
		return $this->updateConversation($conv);
	}
	
	public function wipeConversation(Conversation $conv){
		if(empty($conv->uuid) or !is_numeric($conv->uuid)){
			throw new InvalidIntegerArgumentException("\$conv uuid have to be non zero integer.");
		}
		$this->wipeAllConversationMessageProps($conv->uuid);
		$this->wipeAllConversationMessages($conv->uuid);
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATIONS'))
			->where($qb->expr()->equal(new Field('uuid'), $conv->uuid));

		$this->query->exec($qb->getSQL());
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_TAG . $conv->uuid);
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $conv->uuid);
		return $this->query->affected();
	}
	
	public function wipeAllConversationMessages($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		
		$attMgr = Reg::get(ConfigManager::getConfig('Messaging', 'Conversations')->Objects->ConversationAttachmentManager);
		$attFilter = new ConversationAttachmentFilter();
		$attFilter->setUUID($uuid);
		
		$attachments = $attMgr->getAttachments($attFilter);
		foreach($attachments as $attachment){
			$attMgr->deleteAttachment($attachment);
		}
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
			
		$this->query->exec($qb->getSQL());
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_TAG . $uuid);
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $uuid);
		return $this->query->affected();
	}
	
	public function wipeAllConversationMessageProps($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
			
		$this->query->exec($qb->getSQL());
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_TAG . $uuid);
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $uuid);
		return $this->query->affected();
	}
	
	public function markMessageAsRead(ConversationMessage $msg){
		if(!empty($msg->props) and $msg->props->read == self::STATUS_READ_UNREAD){
			$msg->props->read = self::STATUS_READ_READ;
			$this->updateConversationMessage($msg);

			$conv = $this->getConversationByUUID($msg->uuid, $msg->props->userId);
			if($conv->unreadCount > 0){
				$conv->unreadCount--;
				if($conv->unreadCount == 0){
					$conv->read = self::STATUS_READ_READ;
				}
				$this->updateConversation($conv);
			}
			return true;
		}
		return false;
	}
	
	public function deleteConversationMessage(ConversationMessage $msg){
		if(!empty($msg->props)){
			$this->markMessageAsRead($msg);
			$msg->props->deleted = self::STATUS_DELETED_YES;
			return $this->updateConversationMessage($msg);
		}
		return false;
	}
	
	public function undeleteConversationMessage(ConversationMessage $msg){
		$msg->props->deleted = self::STATUS_DELETED_NO;
		return $this->updateConversationMessage($msg);
	}
	
	public function markAllMessagesAsRead(Conversation $conv){
		$conv->read = self::STATUS_READ_READ;
		$conv->unreadCount = 0;
		$this->updateConversation($conv);
		
		// Change read status on all message props
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
			->set(new Field('read'), self::STATUS_READ_READ)
			->where($qb->expr()->equal(new Field('uuid'), $conv->uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), $conv->userId));
		
		$this->query->exec($qb->getSQL());
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $conv->uuid);
		return $this->query->affected();
	}
	
	public function markAllMessagesAsDeleted(Conversation $conv){
		// Change deleted status on all message props
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
			->set(new Field('deleted'), self::STATUS_DELETED_YES)
			->where($qb->expr()->equal(new Field('uuid'), $conv->uuid))
			->andWhere($qb->expr()->equal(new Field('user_id'), $conv->userId));
		$this->query->exec($qb->getSQL());
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_TAG . $conv->uuid);
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $conv->uuid);
		return $this->query->affected();
	}
	
	public function isConversationExists($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		
		$filter = new ConversationFilter();
		$filter->setUUID($uuid);
		
		$count = $this->getConversationsCount($filter);
		
		return ($count == 0 ? false : true);
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
	
	public function setMessageHasAttachment(ConversationMessage $msg){
		$this->setConversationHasAttachment($msg->uuid);
		$msg->hasAttachment = self::STATUS_HAS_ATTACHMENT_YES;
		return $this->updateConversationMessage($msg);
	}
	
	public function setConversationHasAttachment($uuid){
		$this->changeConversationHasAttachmentStatus($uuid, self::STATUS_HAS_ATTACHMENT_YES);
	}
	
	public function setConversationHasNoAttachment($uuid){
		$this->changeConversationHasAttachmentStatus($uuid, self::STATUS_HAS_ATTACHMENT_NO);
	}
	
	public function changeConversationHasAttachmentStatus($uuid, $status, $userId = null){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		if(!is_numeric($status) or !in_array($status, $this->getConstsArray("STATUS_HAS_ATTACHMENT"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_CONVERSATIONS'))
			->set(new Field('has_attachment'), $status)
			->where($qb->expr()->equal(new Field('uuid'), $uuid));
		
		if(!empty($userId)){
			$qb->andWhere($qb->expr()->equal(new Field('user_id'), $userId));
		}
		
		$this->query->exec($qb->getSQL());
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_TAG . $uuid);
	}
	
	protected function addMessageToConversation($uuid, $userId, $message){
		if(empty($uuid) or !is_numeric($uuid)){
			throw InvalidArgumentException("UUID have to be non zero integer.");
		}
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
		
		$conversation = $this->getConversationByUUID($uuid, $userId, self::INIT_PARTICIPANTS);
		
		// Insert new message into DB
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->values(
				array(
					'uuid' => $conversation->uuid, 
					'user_id' => $conversation->userId,
					'message' => $message
				)
			);
		
		$this->query->exec($qb->getSQL());
		$messageId = $this->query->getLastInsertId();
		
		$newMessage = $this->getConversationMessage((new ConversationMessagesFilter())->setId($messageId), null, self::INIT_NONE);
		
		foreach($conversation->participantIds as $participantId){
			$readStatus = self::STATUS_READ_UNREAD;
			if($participantId == $conversation->userId){
				$readStatus = self::STATUS_READ_READ;
			}
			else{
				$partConv = $this->getConversationByUUID($uuid, $participantId, self::INIT_NONE);
				$partConv->read = self::STATUS_READ_UNREAD;
				$partConv->unreadCount++;
				$partConv->trashed = self::STATUS_TRASHED_NOT_TRASHED;
				$partConv->lastMsgDate = $newMessage->date;
				$this->updateConversation($partConv);
			}
			
			$qb = new QueryBuilder();
			$qb->insert(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
				->values(
						array(
								'uuid' => $uuid,
								'message_id' => $messageId,
								'user_id' => $participantId,
								'read' => $readStatus
							)
						);

			$this->query->exec($qb->getSQL());
		}
		
		$conversation->trashed = self::STATUS_TRASHED_NOT_TRASHED;
		$conversation->lastMsgDate = $newMessage->date;
		$this->updateConversation($conversation);
		
		return $messageId;
	}
	
	public function addParticipant(Conversation $conv, User $user){
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_CONVERSATIONS'))
			->values(
					array(
						'uuid' => $conv->uuid, 
						'user_id' => $user->id,
						'last_msg_date' => $conv->lastMsgDate,
						'read' => self::STATUS_READ_READ
					)
		);
		$this->query->exec($qb->getSQL);
		
		$qb = new QueryBuilder();
		$qb->select(new Field('id'))
			->from(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->where($qb->expr()->equal(new Field("uuid"), $conv->uuid)
		);
		$this->query->exec($qb->getSQL());
		
		$sql2 = MySqlDbManager::getQueryObject();
		
		while($msgId = $this->query->fetchField('id')){
			$qb = new QueryBuilder();
			$qb->insert(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
				->values(
						array(
							'uuid' => $conv->uuid, 
							'message_id' => $msgId, 
							'user_id' => $user->id,
							'read' => self::STATUS_READ_UHREAD
						)
			);
			$sql2->exec($qb->getSQL());
		}
		
		$this->correctConversationHasAttachment($conv->uuid);
		$this->correctConversationReadStatus($conv->uuid);
	}
	
	public function removeParticipant(Conversation $conv, User $user){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS'))
			->where($qb->expr()->equal(new Field('user_id'), $user->id))
			->andWhere($qb->expr()->equal(new Field('uuid'), $conv->uuid));
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->where($qb->expr()->equal(new Field('user_id'), $user->id))
			->andWhere($qb->expr()->equal(new Field('uuid'), $conv->uuid));
		$this->query->exec($qb->getSQL());
		
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATIONS'))
			->where($qb->expr()->equal(new Field('user_id'), $user->id))
			->andWhere($qb->expr()->equal(new Field('uuid'), $conv->uuid));
		$this->query->exec($qb->getSQL());
		
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_TAG . $conv->uuid);
		Reg::get('memcache')->invalidateCacheByTag(self::MEMCACHE_CONV_MSG_TAG . $conv->uuid);
	}
	
	public function getMaxUUID(){
		$qb = new QueryBuilder();
	
		$sqlQuery = $qb->select($qb->expr()->max(new Field('uuid'), 'maxId'))
			->from(Tbl::get('TBL_CONVERSATIONS'))
			->getSQL();
	
		return $this->query->exec($sqlQuery)->fetchField('maxId');
	}
	
	public function openConversation($userIds = array()){
		if(empty($userIds) or !is_array($userIds)){
			throw new InvalidIntegerArgumentException("\$userIds have to be non empty array.");
		}
				
		$db = MySqlDbManager::getDbObject();
		
		$db->startTransaction(true);
		
		$newUUID = $this->getMaxUUID() + 1;
		
		foreach($userIds as $userId){
			$qb = new QueryBuilder();
			$qb->insert(Tbl::get('TBL_CONVERSATIONS'))
				->values(
						array(
							'uuid' => $newUUID, 
							'user_id' => $userId
							)
						);

			$this->query->exec($qb->getSQL());
		}
		
		if(!$db->commit()){
			$db->rollBack();
			return false;
		}
		
		return $newUUID;
	}
	
	public function getParticipantIds($uuid){
		$qb = new QueryBuilder();
			
		$qb->select(new Field("user_id"))
			->from(Tbl::get('TBL_CONVERSATIONS'))
			->where($qb->expr()->equal(new Field('uuid'), $uuid));

		$sql = MySqlDbManager::getQueryObject();
		return $sql->exec($qb->getSQL())->fetchFields('user_id');
	}
	
	public function getParticipants($uuid){
		$participants = array();
		$userManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
		
		$participantIds = $this->getParticipantIds($uuid);
		foreach($participantIds as $participantId){
			try{
				array_push($participants, $userManager->getUserById($participantId));
			}
			catch(UserNotFoundException $e){ }
		}
		return $participants;
	}
	
	
	public function wipeConversationMessage(ConversationMessage $msg){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CONVERSATION_MESSAGES'))
			->where($qb->expr()->equal(new Field('id'), $msg->id));
		
		$affected = $this->query->exec($qb->getSQL())->affected();
		
		$this->correctConversationReadStatus($msg->uuid);
		$this->correctConversationHasAttachment($msg->uuid);
		
		return $affected;
	}
	
	public function correctConversationReadStatus($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
		
		// Get Conversation of message receiver
		$conversationFilter = new ConversationFilter();
		$conversationFilter->setUUID($uuid);
		$conversations = $this->getConversations($conversationFilter, null, self::INIT_NONE);
		
		foreach($conversations as $conv){
			$unreadFilter = new ConversationMessagesFilter($conv->userId);
			$unreadFilter->setDeletedStatus(ConversationManager::STATUS_DELETED_NO);
			$unreadFilter->setUUID($uuid);
			$unreadFilter->setReadStatus(self::STATUS_READ_UNREAD);

			$unreadsCount = $this->getConversationMessagesCount($unreadFilter);

			if($unreadsCount == 0){
				$conv->read = self::STATUS_READ_READ;
			}
			else{
				$conv->read = self::STATUS_READ_UNREAD;
			}
			$conv->unreadCount = $unreadsCount;
			$this->updateConversation($conv);
		}
	}
	
	public function correctConversationHasAttachment($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		// Get Conversations with given UUID
		$conversationFilter = new ConversationFilter();
		$conversationFilter->setUUID($uuid);
		$conversations = $this->getConversations($conversationFilter, null, self::INIT_NONE);
	
		foreach($conversations as $conv){
			// Find how much messages with attachments in conversation
			$filter = new ConversationMessagesFilter($conv->userId);
			$filter->setUUID($uuid);
			$filter->setDeletedStatus(ConversationManager::STATUS_DELETED_NO);
			$filter->setHasAttachment(self::STATUS_HAS_ATTACHMENT_YES);

			$count = $this->getConversationMessagesCount($filter);

			// Correct has attachment status accordingly
			if($count == 0){
				$conv->hasAttachment = self::STATUS_HAS_ATTACHMENT_NO;
			}
			else{
				$conv->hasAttachment = self::STATUS_HAS_ATTACHMENT_YES;
			}
			$this->updateConversation($conv);
		}
	}
	
	
	protected function getConversationObject($conversationRow, $initObjects = self::INIT_ALL){
		$userManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
		$conversation = new Conversation();
		
		$conversation->id = $conversationRow['id'];
		$conversation->uuid = $conversationRow['uuid'];
		$conversation->userId = $conversationRow['user_id'];
		if (($initObjects & self::INIT_USERS) != 0) {
			try{
				$conversation->user = $userManager->getUserById($conversationRow['user_id']);
			}
			catch(UserNotFoundException $e){ }
		}
		if (($initObjects & self::INIT_PARTICIPANTS) != 0) {
			$conversation->participantIds = $this->getParticipantIds($conversation->uuid);
			if (($initObjects & self::INIT_PARTICIPANT_USERS) != 0) {
				foreach($conversation->participantIds as $participantId){
					try{
						array_push($conversation->participants, $userManager->getUserById($participantId));
					}
					catch(UserNotFoundException $e){ }
				}
			}
		}
		$conversation->lastMsgDate = $conversationRow['last_msg_date'];
		$conversation->read = $conversationRow['read'];
		$conversation->unreadCount = $conversationRow['unread_count'];
		$conversation->trashed = $conversationRow['trashed'];
		$conversation->hasAttachment = $conversationRow['has_attachment'];
		
		
		return $conversation;
	}
	
	protected function getConversationMessageObject($messageRow, $userId = null, $initObjects = self::INIT_ALL){
		$message = new ConversationMessage();
	
		$message->id = $messageRow['id'];
		$message->uuid = $messageRow['uuid'];
		$message->date = $messageRow['date'];
		$message->userId = $messageRow['user_id'];
		if (($initObjects & self::INIT_USERS) != 0) {
			$UserManager = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
			
			try{
				if(!empty($messageRow['user_id'])){
					$message->user = $UserManager->getUserById($messageRow['user_id']);
				}
			}
			catch(UserNotFoundException $e){ }
			catch(InvalidArgumentException $e){ }
		}
		$message->message = $messageRow['message'];
		$message->hasAttachment = $messageRow['has_attachment'];
		$message->data = unserialize($messageRow["data"]);
		
		
		if (($initObjects & self::INIT_PROPS) != 0 and !empty($userId)) {
			$message->props = $this->getConversationMessageProps($message->id, $userId);
		}
		
		if (($initObjects & self::INIT_ATTACHMENTS) != 0) {
			if($message->hasAttachment == self::STATUS_HAS_ATTACHMENT_YES){
				$attachMgr = Reg::get(ConfigManager::getConfig("Messaging", "Conversations")->Objects->ConversationAttachmentManager);

				$filter = new ConversationAttachmentFilter();
				$filter->setMessageId($message->id);

				$message->attachments = $attachMgr->getAttachments($filter);
			}
		}
	
		return $message;
	}
}
