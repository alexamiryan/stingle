<?php
class UserGroupsFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_GROUPS', 'UserManager'), "grps", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
		
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setIdIn($ids){
		if(empty($ids) or !is_array($ids)){
			throw new InvalidIntegerArgumentException("\$ids have to be non empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field('id', $this->primaryTableAlias), $ids));
		return $this;
	}
	
	public function setIdNotIn($ids){
		if(empty($ids) or !is_array($ids)){
			throw new InvalidIntegerArgumentException("\$ids have to be non empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->notIn(new Field('id', $this->primaryTableAlias), $ids));
		return $this;
	}
	
	public function setName($name){
		if(empty($name)){
			throw new InvalidIntegerArgumentException("\$name have to be not empty string");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('name', $this->primaryTableAlias), $name));
		return $this;
	}
	
	public function setUser(User $user){
		$this->joinUsersGroupsTable();
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('user_id', 'users_groups'), $user->id));
		return $this;
	}
	
	
	protected function joinUsersGroupsTable(){
		$this->qb->leftJoin(Tbl::get('TBL_USERS_GROUPS', 'UserManager'),	'users_groups',
				$this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), new Field('group_id', 'users_groups')));
	}
	
}
