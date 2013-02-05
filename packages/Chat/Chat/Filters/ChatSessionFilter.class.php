<?php
class ChatSessionFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
	
		$this->qb->select(new Field("*", "chat_sess"))
			->from(Tbl::get('TBL_CHAT_SESSIONS', 'ChatSessionManager'), "chat_sess");
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "chat_sess"), $id));
		return $this;
	}
	
	public function setInviterUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("inviter_user_id", "chat_sess"), $id));
		return $this;
	}
	
	public function setInvitedUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("invited_user_id", "chat_sess"), $id));
		return $this;
	}
	
	public function setSessionStartDate($time){
		if(empty($time)){
			throw new InvalidArgumentException("\$time have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field("date", "chat_sess"), time));
		return $this;
	}
	
	public function setSessionCloseDateGreater($time){
		if(empty($time)){
			throw new InvalidArgumentException("\$time have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field("closed_date", "chat_sess"), time));
		return $this;
	}
	
	public function setSessionClosedStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be non zero integer");
		}
		if(!in_array($status, ChatSessionManager::getConstsArray("CLOSED_STATUS"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("closed", "chat_sess"), $status));
		return $this;
	}
	
	public function setSessionClosedReason($reason){
		if(!is_numeric($reason)){
			throw new InvalidIntegerArgumentException("\$status have to be non zero integer");
		}
		if(!in_array($reason, ChatSessionManager::getConstsArray("CLOSED_REASON"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("closed_reason", "chat_sess"), $reason));
		return $this;
	}
	
	public function setEitherUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer");
		}
		
		$orClause = new Orx();
		$orClause->add($this->qb->expr()->equal(new Field("inviter_user_id", "chat_sess"), $userId));
		$orClause->add($this->qb->expr()->equal(new Field("invited_user_id", "chat_sess"), $userId));
		
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
		$andClause1->add($this->qb->expr()->equal(new Field('inviter_user_id', 'chat_sess'), $userId1));
		$andClause1->add($this->qb->expr()->equal(new Field('invited_user_id', 'chat_sess'), $userId2));
		
		$andClause2 = new Andx();
		$andClause2->add($this->qb->expr()->equal(new Field('inviter_user_id', 'chat_sess'), $userId2));
		$andClause2->add($this->qb->expr()->equal(new Field('invited_user_id', 'chat_sess'), $userId1));
		
		$orClause = new Orx();
		$orClause->add($andClause1);
		$orClause->add($andClause2);
		
		$this->qb->andWhere($orClause);
		
		return $this;
	}
	
	public function setOrderDateAsc(){
		$this->setOrder(new Field("date", "chat_sess"), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderDateDesc(){
		$this->setOrder(new Field("date", "chat_sess"), MySqlDatabase::ORDER_DESC);
	}
}
