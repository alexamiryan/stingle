<?php
class UserProfileFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_PROFILE_SAVE', 'ProfileManager'), "prof_save", "id");
		
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
		
	}
	
	public function setUserId($value){
		if(empty($value) or !is_numeric($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('user_id', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setKeyId($value){
		if(empty($value) or !is_numeric($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), $value));
		return $this;
	}
	
	public function setValueId($value){
		if(empty($value) or !is_numeric($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('value_id', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setValueCustLike($value){
		if(empty($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->like(new Field('value_cust', $this->primaryTableAlias), "%$value%"));
		return $this;
	}
	
	
	
}
