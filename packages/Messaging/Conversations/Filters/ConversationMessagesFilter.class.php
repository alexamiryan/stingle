<?php

class ConversationMessagesFilter extends MergeableFilter {

	protected $userId = null;
	
	public function __construct($userId = null) {
		parent::__construct(Tbl::get('TBL_CONVERSATION_MESSAGES', 'ConversationManager'), "conv_msgs", "id");

		$this->qb->select(new Field("*", "conv_msgs"))
				->from($this->primaryTable, $this->primaryTableAlias);
		
		if(!empty($userId)){
			$this->joinConversationMessagesPropsTable();
			$this->qb->addSelect(new Field("read", "msg_props"));
			$this->qb->addSelect(new Field("deleted", "msg_props"));
			$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", "msg_props"), $userId));
			
			$this->userId = $userId;
		}
	}

	public function setId($id) {
		if (empty($id) or ! is_numeric($id)) {
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}

		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "conv_msgs"), $id));
		return $this;
	}

	public function setIdIn($ids) {
		if (empty($ids) or ! is_array($ids)) {
			throw new InvalidIntegerArgumentException("\$id have to be non empty array");
		}

		$this->qb->andWhere($this->qb->expr()->in(new Field("id", "conv_msgs"), $ids));
		return $this;
	}

	public function setIdGreater($id) {
		if (!is_numeric($id)) {
			throw new InvalidIntegerArgumentException("\$id have to be integer.");
		}

		$this->qb->andWhere($this->qb->expr()->greater(new Field("id", "conv_msgs"), $id));
		return $this;
	}

	public function setIdLess($id) {
		if (!is_numeric($id)) {
			throw new InvalidIntegerArgumentException("\$id have to be integer.");
		}

		$this->qb->andWhere($this->qb->expr()->less(new Field("id", "conv_msgs"), $id));
		return $this;
	}

	public function setUUID($uuid) {
		if (empty($uuid) or ! is_numeric($uuid)) {
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}

		$this->qb->andWhere($this->qb->expr()->equal(new Field("uuid", "conv_msgs"), $uuid));
		return $this;
	}

	public function setUserId($userId) {
		if (empty($userId) or ! is_numeric($userId)) {
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}

		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", "conv_msgs"), $userId));
		return $this;
	}

	public function setUserIdIn($userIds) {
		if (empty($userIds) or ! is_array($userIds)) {
			throw new InvalidIntegerArgumentException("\$userIds have to be non empty array");
		}

		$this->qb->andWhere($this->qb->expr()->in(new Field("user_id", "conv_msgs"), $userIds));
		return $this;
	}
	
	public function setHasAttachment($status) {
		if (!is_numeric($status)) {
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}

		$this->qb->andWhere($this->qb->expr()->equal(new Field("has_attachment", "conv_msgs"), $status));
		return $this;
	}

	public function setReadStatus($status) {
		if (!is_numeric($status)) {
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
		if(empty($this->userId)){
			throw new RuntimeException('ConversationMessages filter is initialized without userId parameter');
		}

		$this->qb->andWhere($this->qb->expr()->equal(new Field("read", "msg_props"), $status));
		return $this;
	}

	public function setDeletedStatus($status) {
		if (!is_numeric($status) or ! in_array($status, ConversationManager::getConstsArray("STATUS_DELETED"))) {
			throw new InvalidIntegerArgumentException("Invalid \$status specified.");
		}
		if(empty($this->userId)){
			throw new RuntimeException('ConversationMessages filter is initialized without userId parameter');
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("deleted", "msg_props"), $status));
		return $this;
	}


	public function setOrderIdAsc() {
		$this->setOrder(new Field('id', 'conv_msgs'), MySqlDatabase::ORDER_ASC);
	}

	public function setOrderIdDesc() {
		$this->setOrder(new Field('id', 'conv_msgs'), MySqlDatabase::ORDER_DESC);
	}

	public function setOrderDateAsc() {
		$this->setOrder(new Field('date', 'conv_msgs'), MySqlDatabase::ORDER_ASC);
	}

	public function setOrderDateDesc() {
		$this->setOrder(new Field('date', 'conv_msgs'), MySqlDatabase::ORDER_DESC);
	}

	/**
	 * Set Date greater than given param
	 * @param string $date in DEFAULT_DATETIME_FORMAT
	 * @throws InvalidIntegerArgumentException
	 */
	public function setDateGreater($date) {
		$this->qb->andWhere($this->qb->expr()->greater(new Field('date', "conv_msgs"), $date));
		return $this;
	}

	/**
	 * Set Date less than given date parameter
	 * @param string $date in DEFAULT_DATETIME_FORMAT
	 * @throws InvalidIntegerArgumentException
	 */
	public function setDateLess($date) {
		$this->qb->andWhere($this->qb->expr()->less(new Field('date', "conv_msgs"), $date));
		return $this;
	}
	
	protected function joinConversationMessagesPropsTable(){
		$this->qb->leftJoin(
			Tbl::get('TBL_CONVERSATION_MESSAGES_PROPS', 'ConversationManager'),	
			'msg_props',
			$this->qb->expr()->equal(new Field('id', 'conv_msgs'), new Field('message_id', 'msg_props'))
		);
	}

}
