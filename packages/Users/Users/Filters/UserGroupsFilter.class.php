<?
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
	
	public function setName($name){
		if(empty($name)){
			throw new InvalidIntegerArgumentException("\$name have to be not empty string");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('name', $this->primaryTableAlias), $name));
		return $this;
	}
	
	public function setUser(User $user){
		if($user->isEmpty()){
			throw new InvalidIntegerArgumentException("\$user have to be initialized User object");
		}
		$this->joinUsersGroupsTable();
		
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('user_id', 'users_groups'), $user->id));
		return $this;
	}
	
	
	protected function joinUsersGroupsTable(){
		$this->qb->leftJoin(Tbl::get('TBL_USERS_GROUPS', 'UserManager'),	'users_groups',
				$this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), new Field('group_id', 'users_groups')));
	}
	
}
?>