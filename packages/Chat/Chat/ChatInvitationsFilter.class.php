<?
class ChatInvitationsFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
	
		$this->qb->select(new Field("*", "inv"))
			->from(Tbl::get('TBL_CHAT_INVITATIONS', 'ChatInvitationManager'), "inv");
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}

		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "inv"), $id));
		return $this;
	}
	
	public function setIdGreater($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->greater(new Field("id", "inv"), $id));
		return $this;
	}
	
	public function setSenderUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("sender_user_id", "inv"), $id));
		return $this;
	}
	
	public function setReceiverUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("receiver_user_id", "inv"), $id));
		return $this;
	}
	
	public function setInvitationStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be non zero integer");
		}
		if(!in_array($status, ChatInvitationManager::getConstsArray("STATUS"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("status", "inv"), $status));
		return $this;
	}
	
	public function setEitherUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer");
		}
		
		$orClause = new Orx();
		$orClause->add($this->qb->expr()->equal(new Field('sender_user_id', 'inv'), $userId));
		$orClause->add($this->qb->expr()->equal(new Field('receiver_user_id', 'inv'), $userId));
		
		$this->qb->andWhere($orClause);
		return $this;
	}
	
	public function setEitherInterlocutors($userId1, $userId2){
		if(empty($userId1) or !is_numeric($userId1)){
			throw new InvalidIntegerArgumentException("\$userId1 have to be non zero integer");
		}
		if(empty($userId2) or !is_numeric($userId2)){
			throw new InvalidIntegerArgumentException("\$userId2 have to be non zero integer");
		}
		
		$andClause1 = new Andx();
		$andClause1->add($this->qb->expr()->equal(new Field('sender_user_id', 'inv'), $userId1));
		$andClause1->add($this->qb->expr()->equal(new Field('receiver_user_id', 'inv'), $userId2));
		
		$andClause2 = new Andx();
		$andClause2->add($this->qb->expr()->equal(new Field('sender_user_id', 'inv'), $userId2));
		$andClause2->add($this->qb->expr()->equal(new Field('receiver_user_id', 'inv'), $userId1));
		
		$orClause = new Orx();
		$orClause->add($andClause1);
		$orClause->add($andClause2);
		
		$this->qb->andWhere($orClause);
		
		return $this;
	}
}
?>