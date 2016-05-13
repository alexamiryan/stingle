<?php
class ProfileValueFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_PROFILE_VALUES', 'ProfileManager'), "prof_vals", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias)
			->orderBy(new Field('sort_id', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
	}
	
	public function setValueId($value){
		if(empty($value) or !is_numeric($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setKeyId($value){
		if(empty($value) or !is_numeric($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('key_id', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setChildKeyId($value){
		if(empty($value) or !is_numeric($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('child_key_id', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setValueName($value){
		if(empty($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('value', $this->primaryTableAlias), $value));
		return $this;
	}
	
	public function setOrderAsc(){
		$this->qb->orderBy(new Field('sort_id', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
	}
	public function setOrderDesc(){
		$this->qb->orderBy(new Field('sort_id', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
	}
}
