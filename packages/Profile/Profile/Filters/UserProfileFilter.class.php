<?php
class UserProfileFilter extends Filter{
	
	public function __construct(){
		parent::__construct();
		
		/*$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias)
			->leftJoin(Tbl::get('TBL_PROFILE_KEYS', 'ProfileManager'),	'prof_keys',
					$this->qb->expr()->equal(new Field('key_id', '$this->primaryTableAlias'), new Field('id', 'prof_keys')))
			->leftJoin(Tbl::get('TBL_PROFILE_VALUES', 'ProfileManager'),	'prof_vals',
					$this->qb->expr()->equal(new Field('value_id', '$this->primaryTableAlias'), new Field('id', 'prof_vals')))
			->setOrder(new Field('sort_id', "prof_keys"), MySqlDatabase::ORDER_ASC)
			->setOrder(new Field('sort_id', "prof_vals"), MySqlDatabase::ORDER_ASC);*/
		
		$this->qb->select(new Field("*", "prof_save"))
			->from($this->primaryTable, "prof_save");
		
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
