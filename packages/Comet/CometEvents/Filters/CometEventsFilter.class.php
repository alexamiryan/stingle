<?
class CometEventsFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
		
		$this->qb->select(new Field("*"))
			->from(Tbl::get("TBL_COMET_EVENTS", "CometEvents"), "comet");
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "comet"), $id));
		return $this;
	}
	
	public function setIdGreater($id){
		if(!is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->greater(new Field("id", "comet"), $id));
		return $this;
	}
	
	public function setSelfUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("self_user_id", "comet"), $userId));
		return $this;
	}
	
	public function setSelfUserIdIn($userIds){
		if(empty($userIds) or !is_array($userIds)){
			throw new InvalidIntegerArgumentException("\$userIds have to be non empty array.");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field("self_user_id", "comet"), $userIds));
		return $this;
	}
	
	public function setUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", "comet"), $userId));
		return $this;
	}
	
	public function setUserIdNull(){
		$this->qb->andWhere($this->qb->expr()->isNull(new Field("user_id", "comet")));
		return $this;
	}
}
?>