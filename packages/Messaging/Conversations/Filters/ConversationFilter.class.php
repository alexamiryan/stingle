<?
class ConversationFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_CONVERSATIONS', 'ConversationManager'), "conv", "user_id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setUUID($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("uuid", $this->primaryTableAlias), $uuid));
		return $this;
	}
	
	public function setUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setInterlocutorId($interlocutorId){
		if(empty($interlocutorId) or !is_numeric($interlocutorId)){
			throw new InvalidIntegerArgumentException("\$interlocutorId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("interlocutor_id", $this->primaryTableAlias), $interlocutorId));
		return $this;
	}
	
	public function setReadStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("read", $this->primaryTableAlias), $status));
		return $this;
	}
	
	public function setTrashedStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("trashed", $this->primaryTableAlias), $status));
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