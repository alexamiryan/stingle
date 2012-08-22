<?
class UserManager extends DbAccessor{
	
	const TBL_USERS 				= "wum_users";
	const TBL_USERS_PROPERTIES		= "wum_users_properties";
	const TBL_USERS_GROUPS 			= "wum_users_groups";
	const TBL_USERS_PERMISSIONS 	= "wum_users_permissions";
	const TBL_PERMISSIONS 			= "wum_permissions";
	const TBL_GROUPS 				= "wum_groups";
	const TBL_GROUPS_PERMISSIONS 	= "wum_groups_permissions";
	
	const STATE_ENABLED_ENABLED = 1;
	const STATE_ENABLED_DISABLED = 0;

	const STATE_EMAIL_CONFIRMED = 1;
	const STATE_EMAIL_UNCONFIRMED = 0;
	
	const INIT_ALL = -1;
	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_PROPERTIES = 1;
	const INIT_PERMISSIONS = 2;
	
	protected $config;

	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->config = $config;
	}

	public function getUsersList(UsersFilter $filter = null, MysqlPager $pager = null, $initObjects = self::INIT_ALL, $cacheMinutes = 0){
		if($filter == null){
			$filter = new UsersFilter();
		}

		$sqlQuery = $filter->getSQL();
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$users = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				array_push($users, $this->getUserObjectFromData($row, $initObjects, $cacheMinutes));
			}
		}

		return $users;
	}
	
	/**
	 * Get user list count
	 *
	 * @param UsersFilter $filter
	 * @return integer
	 */
	public function getUsersListCount(UsersFilter $filter = null, $cacheMinutes = 0){
		if($filter === null){
			$filter = new UsersFilter();
		}
	
		$filter->setSelectCount();
	
		$sqlQuery = $filter->getSQL();
	
		$this->query->exec($sqlQuery, $cacheMinutes);
		return $this->query->fetchField('cnt');
	}
	
	public function getUser(UsersFilter $filter, $initObjects = self::INIT_ALL, $cacheMinutes = 0){
		$users = $this->getUsersList($filter, null, $initObjects, $cacheMinutes);
		if(count($users) !== 1){
			throw new UserNotUniqueException("There is no such user or user is not unique.");
		}
		return $users[0];
	}
	
	public function getUserById($userId, $initObjects = self::INIT_ALL, $cacheMinutes = 0){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		
		$filter = new UsersFilter();
		$filter->setUserIdEqual($userId);
		$users = $this->getUsersList($filter, null, $initObjects, $cacheMinutes);
		if(count($users) !== 1){
			throw new UserNotUniqueException("There is no such user or user is not unique.");
		}
		return $users[0];
	}
	
	public function getIdByLogin($login, $cacheMinutes = 0){
		if(empty($login)){
			throw new InvalidArgumentException("\$login have to be non empty string");
		}
	
		$filter = new UsersFilter();
		$filter->setLogin($login);
		$users = $this->getUsersList($filter, null, static::INIT_NONE, $cacheMinutes);
		if(count($users) !== 1){
			throw new UserNotUniqueException("There is no such user or user is not unique.");
		}
		return $users[0]->id;
	}
	
	public function isUserExists($login, $cacheMinutes = 0){
		if(empty($login)){
			throw new InvalidArgumentException("\$login have to be non empty string");
		}
	
		$filter = new UsersFilter();
		$filter->setLogin($login);
		$usersCount = $this->getUsersListCount($filter, $cacheMinutes);
		if($usersCount > 0){
			return true;
		}
		return false;
	}
	
	public function createUser(User $user){
		$qb = new QueryBuilder();
		
		$user->salt = Crypto::secureRandom(512);
		$user->password = UserAuthorization::getUserPasswordHash($user->password, $user->salt);
		
		$qb->insert(Tbl::get('TBL_USERS'))
			->values(array(
					'login' => $user->login,
					'password' => $user->password,
					'salt' => $user->salt,
					'enabled' => $user->enabled,
					'email' => $user->email,
					'email_confirmed' => $user->emailConfirmed,
					'last_login_ip' => $user->lastLoginIP
					));
		
		$newUserId = $this->query->exec($qb->getSQL())->getLastInsertId();
		
		if($user->props != null){
			$qb = new QueryBuilder();
		
			$values = array();		
			foreach($this->config->userPropertiesMap as $objectKey => $dbKey){
				if(isset($user->props->$objectKey)){
					$values[$dbKey] = $user->props->$objectKey;
				}
			}
			
			$values['user_id']  = $newUserId;
			$qb->insert(Tbl::get('TBL_USERS_PROPERTIES'))->values($values);
			
			$this->query->exec($qb->getSQL());
		}
		
		return $newUserId;
	}
	
	public function updateUser(User $user){
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_USERS'))
			->set(new Field('login'), $user->login)
			->set(new Field('password'), $user->password)
			->set(new Field('salt'), $user->salt)
			->set(new Field('creation_date'), $user->creationDate)
			->set(new Field('enabled'), $user->enabled)
			->set(new Field('email'), $user->email)
			->set(new Field('email_confirmed'), $user->emailConfirmed)
			->set(new Field('last_login_date'), $user->lastLoginDate)
			->set(new Field('last_login_ip'), $user->lastLoginIP)
			->where($qb->expr()->equal(new Field('id'), $user->id));
		
		if($user->props != null){
			$this->editUserProperties($user->props);
		}
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function setUserPassword(User $user, $password){
		if(empty($password)){
			throw new InvalidArgumentException("\$password can't be empty");
		}
		
		$salt = Crypto::secureRandom(512);
		$passwordHash = UserAuthorization::getUserPasswordHash($password, $salt);
		
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_USERS'))
			->set(new Field('password'), $passwordHash)
			->set(new Field('salt'), $salt)
			->where($qb->expr()->equal(new Field('id'), $user->id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function deleteUser(User $user){
		$qb = new QueryBuilder();
		
		$qb->delete(Tbl::get('TBL_USERS'))
			->where($qb->expr()->equal(new Field('id'), $user->id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function editUserProperties(UserProperties $properties){
		$qb = new QueryBuilder();
	
		$qb->update(Tbl::get('TBL_USERS_PROPERTIES'));
		
		foreach($this->config->userPropertiesMap as $objectKey => $dbKey){
			$qb->set(new Field($dbKey), $properties->$objectKey);
		}
		
		$qb->where($qb->expr()->equal(new Field('user_id'), $properties->userId));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	protected function getPermissionsObject($userId, $cacheMinutes = 0){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		
		$qb = new QueryBuilder();
		$qb1 = new QueryBuilder();
		$qb2 = new QueryBuilder();
		
		$qb1->select(new Field('permission_id'))
			->from(Tbl::get('TBL_USERS_PERMISSIONS'))
			->where($qb1->expr()->equal(new Field('user_id'), $userId));
		
		$qb2->select(new Field('permission_id', 'gp'))
			->from(Tbl::get('TBL_USERS_GROUPS'), 'ug')
			->leftJoin(Tbl::get('TBL_GROUPS_PERMISSIONS'), 'gp', $qb2->expr()->equal(new Field('group_id', 'ug'), new Field('group_id', 'gp')))
			->where($qb1->expr()->equal(new Field('user_id', 'ug'), $userId));
		
		$union = new Unionx();
		$union->add($qb1);
		$union->add($qb2);
		
		$qb->select(new Field('name'))
			->from(Tbl::get('TBL_PERMISSIONS'))
			->where($qb->expr()->in(new Field('id'), $union));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
		$permissionsList = $this->query->fetchFields("name");
		
		return new UserPermissions($permissionsList);
	}
	
	protected function getPropertiesObject($userId, $cacheMinutes = 0){

		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		
		$qb = new QueryBuilder();
		
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_USERS_PROPERTIES'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
		$this->query->exec($qb->getSQL());
		
		$properties = new UserProperties();
		
		$properties->userId = $userId;
		
		if($this->query->countRecords()){
			$data = $this->query->fetchRecord();
			foreach($this->config->userPropertiesMap as $objectKey => $dbKey){
				if(isset($data[$dbKey])){
					$properties->$objectKey = $data[$dbKey];
				}
			}
		}		
		
		return $properties;
	}
	
	protected function getUserObjectFromData($data, $initObjects = self::INIT_ALL, $cacheMinutes = 0){
		$user = new User();
		
		$user->id = $data['id'];
		$user->enabled = $data['enabled'];
		$user->creationDate = $data['creation_date'];
		$user->login = $data['login'];
		$user->password = $data['password'];
		$user->salt = $data['salt'];
		$user->lastLoginIP = $data['last_login_ip'];
		$user->lastLoginDate = $data['last_login_date'];
		$user->email = $data['email'];
		$user->emailConfirmed = $data['email_confirmed'];
		
		if($initObjects !== 0){
			$isInitAll = ($initObjects == -1 ? true : false);
			if($isInitAll or ($initObjects & self::INIT_PROPERTIES) != 0){
				$user->props = $this->getPropertiesObject($user->id, $cacheMinutes);
			}
			if($isInitAll or ($initObjects & self::INIT_PERMISSIONS) != 0){
				$user->perms = $this->getPermissionsObject($user->id, $cacheMinutes);
			}
		}
		
		return $user;
	}
	
}
?>