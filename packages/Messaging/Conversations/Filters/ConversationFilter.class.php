<?
class ConversationFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
		
		$this->qb->select(new Field("*"))
			->from(Tbl::get('TBL_CONVERSATIONS', 'ConversationManager'), "conv");
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "conv"), $id));
		return $this;
	}
	
	public function setUUID($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("uuid", "conv"), $uuid));
		return $this;
	}
	
	public function setUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", "conv"), $userId));
		return $this;
	}
	
	public function setInterlocutorId($interlocutorId){
		if(empty($interlocutorId) or !is_numeric($interlocutorId)){
			throw new InvalidIntegerArgumentException("\$interlocutorId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("interlocutor_id", "conv"), $interlocutorId));
		return $this;
	}
	
	public function setReadStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("read", "conv"), $status));
		return $this;
	}
	
	public function setTrashedStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("trashed", "conv"), $status));
		return $this;
	}
	
	public function setOrderLastMsgDateAsc(){
		$this->setOrder(new Field('last_msg_date', 'conv'), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderLastMsgDateDesc(){
		$this->setOrder(new Field('last_msg_date', 'conv'), MySqlDatabase::ORDER_DESC);
	}
}
?>