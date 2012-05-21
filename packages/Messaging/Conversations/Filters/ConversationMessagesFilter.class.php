<?
class ConversationMessagesFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
		
		$this->qb->select(new Field("*"))
			->from(Tbl::get('TBL_CONVERSATION_MESSAGES', 'ConversationManager'), "conv_msgs");
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "conv_msgs"), $id));
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
	
	public function setReceiverId($receiverId){
		if(empty($receiverId) or !is_numeric($receiverId)){
			throw new InvalidIntegerArgumentException("\$receiverId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("receiver_id", "conv_msgs"), $receiverId));
		return $this;
	}
	
	public function setReadStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("read", "conv_msgs"), $status));
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
				$orX = new Orx();
				$orX->add($this->qb->expr()->equal(new Field("deleted", "conv_msgs"), -1));
				$orX->add($this->qb->expr()->notEqual(new Field("deleted", "conv_msgs"), $myUserId));
				$this->qb->andWhere($orX);
				break;
		}
		
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
	
}
?>