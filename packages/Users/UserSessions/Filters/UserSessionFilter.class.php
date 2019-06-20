<?php
class UserSessionFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_USER_SESSIONS', 'UserSessionsManager'), "usess", "user_id");
		
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
	
	public function setUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setToken($token){
		if(empty($token)){
			throw new InvalidIntegerArgumentException("\$token have to be non empty.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("token", $this->primaryTableAlias), $token));
		return $this;
	}
	
}
