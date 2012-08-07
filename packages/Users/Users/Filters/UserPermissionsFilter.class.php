<?
class UserPermissionsFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_PERMISSIONS', 'UserManager'), "perms", "id");
		
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
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('id', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUser(User $user){
		if($user->isEmpty()){
			throw new InvalidIntegerArgumentException("\$user have to be initialized User object");
		}
		$this->joinUsersPermissionsTable();
		
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('user_id', 'users_perms'), $user->id));
		return $this;
	}
	
	public function setGroup(UserGroup $group){
		if(empty($group->id)){
			throw new InvalidIntegerArgumentException("\$group have to be initialized UserGroup object");
		}
		$this->joinGroupsPermissionsTable();
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('group_id', 'groups_perms'), $group->id));
		return $this;
	}
	
	protected function joinUsersPermissionsTable(){
		$this->qb->leftJoin(Tbl::get('TBL_USERS_PERMISSIONS', 'UserManager'),	'users_perms',
				$this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), new Field('permission_id', 'users_perms')));
	}
	
	protected function joinGroupsPermissionsTable(){
		$this->qb->leftJoin(Tbl::get('TBL_GROUPS_PERMISSIONS', 'UserManager'),	'groups_perms',
				$this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), new Field('permission_id', 'groups_perms')));
	}
	
}
?>