<?php
class ConversationMessagesFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_CONVERSATION_MESSAGES', 'ConversationManager'), "conv_msgs", "user_id");
		
		$this->qb->select(new Field("*"))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "conv_msgs"), $id));
		return $this;
	}
	
	public function setIdIn($ids){
		if(empty($ids) or !is_array($ids)){
			throw new InvalidIntegerArgumentException("\$id have to be non empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field("id", "conv_msgs"), $ids));
		return $this;
	}
	
	public function setIdGreater($id){
		if(!is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->greater(new Field("id", "conv_msgs"), $id));
		return $this;
	}
	
	public function setIdLess($id){
		if(!is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->less(new Field("id", "conv_msgs"), $id));
		return $this;
	}
	
	public function setUUID($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("uuid", "conv_msgs"), $uuid));
		return $this;
	}
	
	public function setSenderId($senderId){
		if(empty($senderId) or !is_numeric($senderId)){
			throw new InvalidIntegerArgumentException("\$senderId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("sender_id", "conv_msgs"), $senderId));
		return $this;
	}
	
	public function setSenderIdIn($senderIds){
		if(empty($senderIds) or !is_array($senderIds)){
			throw new InvalidIntegerArgumentException("\$senderIds have to be non empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field("sender_id", "conv_msgs"), $senderIds));
		return $this;
	}
	
	public function setReceiverId($receiverId){
		if(empty($receiverId) or !is_numeric($receiverId)){
			throw new InvalidIntegerArgumentException("\$receiverId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("receiver_id", "conv_msgs"), $receiverId));
		return $this;
	}
	
	public function setReceiverdIn($receiverds){
		if(empty($receiverds) or !is_array($receiverds)){
			throw new InvalidIntegerArgumentException("\$receiverds have to be non empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field("receiver_id", "conv_msgs"), $receiverds));
		return $this;
	}
	
	public function setReadStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("read", "conv_msgs"), $status));
		return $this;
	}
	
	public function setHasAttachment($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("has_attachment", "conv_msgs"), $status));
		return $this;
	}
	
	public function setDeletedStatus($status, $myUserId){
		if(!is_numeric($status) or !in_array($status, ConversationManager::getConstsArray("STATUS_DELETED"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified.");
		}
		if(empty($myUserId) or !is_numeric($myUserId)){
			throw new InvalidIntegerArgumentException("\$myUserId have to be non zero integer.");
		}
		
		switch($status){
			case ConversationManager::STATUS_DELETED_NO:
				$this->qb->andWhere($this->qb->expr()->in(new Field("deleted", "conv_msgs"), array(0, $myUserId)));
				break;
			case ConversationManager::STATUS_DELETED_YES:
				$this->qb->andWhere($this->qb->expr()->notIn(new Field("deleted", "conv_msgs"), array(0, $myUserId)));
				break;
		}
		
		return $this;
	}
	
	public function setDeletedStatusEqual($status){
		if(!is_numeric($status) or !in_array($status, ConversationManager::getConstsArray("STATUS_DELETED"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified.");
		}
			
		$this->qb->andWhere($this->qb->expr()->equal(new Field("deleted", "conv_msgs"), $status));
		
		return $this;
	}
	
	public function setDeletedStatusIn($statuses){
		if(empty($statuses) or !is_array($statuses)){
			throw new InvalidIntegerArgumentException("\$statuses have to be non empty array");
		}
			
		$this->qb->andWhere($this->qb->expr()->in(new Field("deleted", "conv_msgs"), $statuses));
	
		return $this;
	}
	
	public function setIsSystem(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field("system", "conv_msgs"), '1'));
	
		return $this;
	}
	
	public function setIsNotSystem(){
		$this->qb->andWhere($this->qb->expr()->equal(new Field("system", "conv_msgs"), '0'));
	
		return $this;
	}
	
	public function setDeletedStatusNotEqual($status){
		if(!is_numeric($status) or !in_array($status, ConversationManager::getConstsArray("STATUS_DELETED"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified.");
		}
			
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field("deleted", "conv_msgs"), $status));
		
		return $this;
	}
	
	public function setDeletedStatusNotDeleted(){
		$this->qb->andWhere($this->qb->expr()->greaterEqual(new Field("deleted", "conv_msgs"), 0));
	
		return $this;
	}
	
	public function setOrderIdAsc(){
		$this->setOrder(new Field('id', 'conv_msgs'), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderIdDesc(){
		$this->setOrder(new Field('id', 'conv_msgs'), MySqlDatabase::ORDER_DESC);
	}
	
	public function setOrderDateAsc(){
		$this->setOrder(new Field('date', 'conv_msgs'), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderDateDesc(){
		$this->setOrder(new Field('date', 'conv_msgs'), MySqlDatabase::ORDER_DESC);
	}
	
	/**
	 * Set Date greater than given param
	 * @param string $date in DEFAULT_DATETIME_FORMAT
	 * @throws InvalidIntegerArgumentException
	 */
	public function setDateGreater($date){
		$this->qb->andWhere($this->qb->expr()->greater(new Field('date'), $date));
		return $this;
	}
	
	/**
	 * Set Date less than given date parameter
	 * @param string $date in DEFAULT_DATETIME_FORMAT
	 * @throws InvalidIntegerArgumentException
	 */
	public function setDateLess($date){
		$this->qb->andWhere($this->qb->expr()->less(new Field('date'), $date));
		return $this;
	}
}
