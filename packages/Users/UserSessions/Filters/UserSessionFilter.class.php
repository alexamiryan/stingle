<?php
class UserSessionFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_USER_SESSIONS', 'UserSessionsManager'), "usess", "user_id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setId($id) : UserSessionFilter{
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setUserId($userId) : UserSessionFilter{
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setToken($token) : UserSessionFilter{
		if(empty($token)){
			throw new InvalidIntegerArgumentException("\$token have to be non empty.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("token", $this->primaryTableAlias), $token));
		return $this;
	}
    
    public function setCreationDateGreater($date): UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greater(new Field('creation_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setCreationDateGreaterEqual($date): UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('creation_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setCreationDateLess($date): UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('creation_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setCreationDateLessEqual($date): UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('creation_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    
    public function setLastUpdateDateGreater($date): UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greater(new Field('last_update_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setLastUpdateDateGreaterEqual($date): UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('last_update_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setLastUpdateDateLess($date): UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('last_update_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setLastUpdateDateLessEqual($date) : UserSessionFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('last_update_date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function groupByUser() : UserSessionFilter {
        $this->qb->groupBy(new Field('user_id', $this->primaryTableAlias));
        $this->qb->select(new Field("user_id", $this->primaryTableAlias));
        return $this;
    }
	
}
