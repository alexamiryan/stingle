<?php
class ProfileKeyFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_PROFILE_KEYS', 'ProfileManager'), "prof_keys", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias)
			->setOrder(new Field('sort_id', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
	}
	
	public function setKeyId($value){
		if(empty($value) or !is_numeric($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setKeyName($value){
		if(empty($value)){
			throw new InvalidIntegerArgumentException("\$value have to be not empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('key', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setKeyType($value){
		if(!in_array($value, ProfileManager::getConstsArray("KEY_TYPE"))){
			throw new InvalidIntegerArgumentException("Invalid \$value given");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('type', $this->primaryTableAlias), $value));
		return $this;
	}
	public function setIsEnabled($value){
		if(!in_array($value, ProfileManager::getConstsArray("KEY_STATUS"))){
			throw new InvalidIntegerArgumentException("Invalid \$value given");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('is_enabled', $this->primaryTableAlias), $value));
		return $this;
	}
	
	
	
}
