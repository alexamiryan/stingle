<?
class UsersFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_USERS', 'UserManager'), "users", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setUserIdEqual($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('id', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUserIdNotEqual($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('id', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUserIdIn(array $userIds){
		if(empty($userIds) or !is_array($userIds)){
			throw new InvalidIntegerArgumentException("\$userIds have to be not empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field('id', $this->primaryTableAlias), $userIds));
		return $this;
	}
	
	public function setUserIdNotIn(array $userIds){
		if(empty($userIds) or !is_array($userIds)){
			throw new InvalidIntegerArgumentException("\$userIds have to be not empty array");
		}
	
		$this->qb->andWhere($this->qb->expr()->notIn(new Field('id', $this->primaryTableAlias), $userIds));
		return $this;
	}
	
	public function setEnabledStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$state have to be integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('enabled', $this->primaryTableAlias), $status));
		return $this;
	}
	
	public function setLogin($login){
		if(empty($login)){
			throw new InvalidArgumentException("\$login have to be non empty string");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('login', $this->primaryTableAlias), $login));
		return $this;
	}
	
	public function setLoginLike($login){
		if(empty($login)){
			throw new InvalidArgumentException("\$login have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->like(new Field('login', $this->primaryTableAlias), "$login%"));
		return $this;
	}
	
	public function setEmail($email){
		if(empty($email)){
			throw new InvalidArgumentException("\$email have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('email', $this->primaryTableAlias), $email));
		return $this;
	}
	
	public function setGroupId($groupId){
		if(empty($groupId) or !is_numeric($groupId)){
			throw new InvalidArgumentException("\$groupId have to be non empty integer");
		}
		
		$this->joinUsersGroupsTable();
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('group_id', "users_groups"), $groupId));
		
		return $this;
	}
	
	public function setGroupIds($groupIds){
		if(empty($groupIds) or !is_array($groupIds)){
			throw new InvalidArgumentException("\$groupIds have to be non empty array");
		}
	
		$this->joinUsersGroupsTable();
	
		$this->qb->andWhere($this->qb->expr()->in(new Field('group_id', "users_groups"), $groupIds));
	
		return $this;
	}
	
	public function setGroupName($groupName){
		if(empty($groupName) or !is_string($groupName)){
			throw new InvalidArgumentException("\$groupName have to be non empty string");
		}
	
		$this->joinUsersGroupsTable();
		$this->joinGroupsTable();
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('name', "groups"), $groupName));
	
		return $this;
	}
	
	public function setGroupNames($groupNames){
		if(empty($groupNames) or !is_array($groupNames)){
			throw new InvalidArgumentException("\$groupNames have to be non empty string");
		}
	
		$this->joinUsersGroupsTable();
		$this->joinGroupsTable();
	
		$this->qb->andWhere($this->qb->expr()->in(new Field('name', "groups"), $groupNames));
	
		return $this;
	}
	
	public function setCreationDate($date){
		if(empty($date)){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('creation_date', $this->primaryTableAlias), $date));
		return $this;
	}
	
	public function setCreationDateGreater($date){
		if(empty($date)){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field('creation_date', $this->primaryTableAlias), $date));
		return $this;
	}
	
	public function setCreationDateLess($date){
		if(empty($date)){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
	
		$this->qb->andWhere($this->qb->expr()->less(new Field('creation_date', $this->primaryTableAlias), $date));
		return $this;
	}
	
	public function setCreationDateGreaterEqual($date){
		if(empty($date)){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
	
		$this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('creation_date', $this->primaryTableAlias), $date));
		return $this;
	}
	
	public function setCreationDateLessEqual($date){
		if(empty($date)){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
	
		$this->qb->andWhere($this->qb->expr()->lessEqual(new Field('creation_date', $this->primaryTableAlias), $date));
		return $this;
	}
	
	public function setOrderCreationDateAsc(){
		$this->setOrder(new Field('creation_date', 'users'), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderCreationDateDesc(){
		$this->setOrder(new Field('creation_date', 'users'), MySqlDatabase::ORDER_DESC);
	}
	
	public function setOrderIdDesc(){
		$this->setOrder(new Field('id', 'users'), MySqlDatabase::ORDER_DESC);
	}
	
	public function setOrderIdAsc(){
		$this->setOrder(new Field('id', 'users'), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderLastLoginDateAsc(){
		$this->setOrder(new Field('last_login_date', 'users'), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderLastLoginDateDesc(){
		$this->setOrder(new Field('last_login_date', 'users'), MySqlDatabase::ORDER_DESC);
	}
	
	public function setEmailConfirmationStatus($status = UserManager::STATE_EMAIL_CONFIRMED){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field('email_confirmed', "users"), $status));
		return $this;
	}
	
	protected function joinUsersPropertiesTable(){
		$this->qb->leftJoin(Tbl::get('TBL_USERS_PROPERTIES', 'UserManager'),	'users_prop',
				$this->qb->expr()->equal(new Field('id', 'users'), new Field('user_id', 'users_prop')));
	}
	
	protected function joinUsersGroupsTable(){
		$this->qb->leftJoin(Tbl::get('TBL_USERS_GROUPS', 'UserManager'),	'users_groups',
				$this->qb->expr()->equal(new Field('id', 'users'), new Field('user_id', 'users_groups')));
	}
	
	protected function joinGroupsTable(){
		$this->qb->leftJoin(Tbl::get('TBL_GROUPS', 'UserManager'),	'groups',
				$this->qb->expr()->equal(new Field('group_id', 'users_groups'), new Field('id', 'groups')));
	}
}
?>