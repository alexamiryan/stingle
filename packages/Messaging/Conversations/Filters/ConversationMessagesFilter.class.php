<?
class ConversationMessagesFilter extends Filter {
	
	public function __construct($headersOnly = true){
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
	
	public function setReadStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("read", "conv_msgs"), $status));
		return $this;
	}
	
	public function setOrderDateAsc(){
		$this->setOrder(new Field('date', 'conv_msgs'), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderDateDesc(){
		$this->setOrder(new Field('date', 'conv_msgs'), MySqlDatabase::ORDER_DESC);
	}
	
}
?>