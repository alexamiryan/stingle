<?php
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
	
	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_PROPERTIES = 1;
	const INIT_PERMISSIONS = 2;
	
	// INIT_ALL Should be next power of 2 minus 1
	const INIT_ALL = 3;
	
	protected $config;

	/**
	 * Contructor
	 * 
	 * @param Config $config
	 * @param integer $instanceName
	 */
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		$this->config = $config;
	}

	/**
	 * Get Users list according to filter and pager
	 * 
	 * @param UsersFilter $filter
	 * @param MysqlPager $pager
	 * @param integer $initObjects For ex. INIT_PROPERTIES | INIT_PERMISSIONS
	 * @param integer $cacheMinutes
	 */
	public function getUsersList(UsersFilter $filter = null, MysqlPager $pager = null, $initObjects = self::INIT_ALL, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		$sql = $this->getUsersListQueryObject($filter, $pager, $cacheMinutes, $cacheTag);
		
		$users = array();
		if($sql->countRecords()){
			while(($userId = $sql->fetchField('id')) != false){
				array_push($users, $this->getUserById($userId, $initObjects, $cacheMinutes, $cacheTag));
			}
		}

		return $users;
	}
	
	/**
	 * Get users list query object to manually get user ids
	 * 
	 * @param UsersFilter $filter
	 * @param MysqlPager $pager
	 * @param integer $cacheMinutes
	 * @param string $cacheTag
	 * @return MysqlQuery
	 */
	public function getUsersListQueryObject(UsersFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		if($filter == null){
			$filter = new UsersFilter();
		}

		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		if($pager !== null){
			$sql = $pager->executePagedSQL($sqlQuery, $cacheMinutes, null, $cacheTag);
		}
		else{
			$sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
		}
		
		return $sql;
	}
	
	/**
	 * Get user list count
	 *
	 * @param UsersFilter $filter
	 * @param integer $cacheMinutes
	 * @return integer
	 */
	public function getUsersListCount(UsersFilter $filter = null, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		if($filter === null){
			$filter = new UsersFilter();
		}
	
		$filter->setSelectCount();
	
		$sqlQuery = $filter->getSQL();
	
		$this->query->exec($sqlQuery, $cacheMinutes, $cacheTag);
		return $this->query->fetchField('cnt');
	}
	
	/**
	 * Get User according to filter
	 * 
	 * @param UsersFilter $filter
	 * @param @param integer $initObjects For ex. INIT_PROPERTIES | INIT_PERMISSIONS
	 * @param integer $cacheMinutes
	 * @throws UserNotFoundException
	 * @return User
	 */
	public function getUser(UsersFilter $filter, $initObjects = self::INIT_ALL, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		$users = $this->getUsersList($filter, null, $initObjects, $cacheMinutes, $cacheTag);
		if(count($users) !== 1){
			throw new UserNotFoundException("There is no such user or user is not unique.");
		}
		return $users[0];
	}
	
	/**
	 * Get User By ID
	 * 
	 * @param integer $userId
	 * @param @param integer $initObjects For ex. INIT_PROPERTIES | INIT_PERMISSIONS
	 * @param integer $cacheMinutes
	 * @param string $cacheTag
	 * @throws InvalidArgumentException
	 * @throws UserNotFoundException
	 * @return User
	 */
	public function getUserById($userId, $initObjects = self::INIT_ALL, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		
		$this->query->lockEndpoint();
		
		$qb = new QueryBuilder();
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_USERS'))
			->where($qb->expr()->equal(new Field('id'), $userId));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes, $cacheTag);
		
		if($this->query->countRecords() == 1){
			$user = $this->getUserObjectFromData($this->query->fetchRecord(), $initObjects, $cacheMinutes, $cacheTag);
			$this->query->unlockEndpoint();
			return $user;
		}
		
		throw new UserNotFoundException("There is no such user or user is not unique.");
	}
	

	/**
	 * Check if user with given login exists
	 * 
	 * @param string $login
	 * @param integer $cacheMinutes
	 * @throws InvalidArgumentException
	 * @return boolean
	 */
	public function isLoginExists($login, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		if(empty($login)){
			throw new InvalidArgumentException("\$login have to be non empty string");
		}
	
		$filter = new UsersFilter();
		$filter->setLogin($login);
		$usersCount = $this->getUsersListCount($filter, $cacheMinutes, $cacheTag);
		if($usersCount > 0){
			return true;
		}
		return false;
	}
	
	/**
	 * Create User from given User object
	 * 
	 * @param User $user
	 * @return integer Created User ID
	 */
	public function createUser(User $user){
		$this->query->lockEndpoint();
		
		$qb = new QueryBuilder();
		
		$user->salt = Crypto::secureRandom(512);
		$user->password = UserAuthorization::getUserPasswordHash($user->password, $user->salt);
		
		$insertArr = array(
            'enabled' => $user->enabled,
            'creation_date' => new Func("NOW"),
            'creation_time' => new Func("NOW"),
            'login' => $user->login,
            'password' => $user->password,
            'salt' => $user->salt,
            'email' => $user->email,
            'email_confirmed' => $user->emailConfirmed
        );
		
		if($this->config->saveLastLoginDateIP){
		    $insertArr['last_login_ip'] = $user->lastLoginIP;
        }
		
		$qb->insert(Tbl::get('TBL_USERS'))
			->values($insertArr);
		
		$newUserId = $this->query->exec($qb->getSQL())->getLastInsertId();
		$user->id = $newUserId;
		
		if($user->props != null){
			$qb = new QueryBuilder();
		
			$values = array();		
			foreach($this->config->userPropertiesMap as $objectKey => $dbKey){
				if(isset($user->props->$objectKey)){
					if($user->props->$objectKey === '' or $user->props->$objectKey === null){
						$values[$dbKey] = new Literal("NULL");
					}
					else{
						$values[$dbKey] = $user->props->$objectKey;
					}
				}
			}
			
			$values['user_id']  = $newUserId;
			$qb->insert(Tbl::get('TBL_USERS_PROPERTIES'))->values($values);
			
			$this->query->exec($qb->getSQL());
		}
		
		$newUser = $this->getUserById($newUserId);
		$this->query->unlockEndpoint();
		return $newUser;
	}
	
	/**
	 * Update user. Syncs given user object with DB 
	 * 
	 * @param User $user
	 * @return integer Affected
	 */
	public function updateUser(User $user){
		$qb = new QueryBuilder();
		
		$qb->update(Tbl::get('TBL_USERS'))
			->set(new Field('login'), $user->login)
			->set(new Field('creation_date'), $user->creationDate)
			->set(new Field('enabled'), $user->enabled)
			->set(new Field('email'), $user->email)
			->set(new Field('email_confirmed'), $user->emailConfirmed)
			->where($qb->expr()->equal(new Field('id'), $user->id));
		
        if($this->config->saveLastLoginDateIP) {
            $qb ->set(new Field('last_login_date'), (!empty($user->lastLoginDate) ? $user->lastLoginDate : new Literal('NULL')))
                ->set(new Field('last_login_ip'), $user->lastLoginIP);
        }
		
		if($user->props != null){
			$this->updateUserProperties($user->props);
		}
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Set User Password overriding the old one
	 * 
	 * @param User $user
	 * @param string $password
	 * @throws InvalidArgumentException
	 * @return integer Affected
	 */
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
	
	/**
	 * Delete given user from DB
	 * 
	 * @param User $user
	 * @return integer Affected
	 */
	public function deleteUser(User $user){
		$qb = new QueryBuilder();
		
		$qb->delete(Tbl::get('TBL_USERS'))
			->where($qb->expr()->equal(new Field('id'), $user->id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Updated User Properties
	 * 
	 * @param UserProperties $properties
	 * @return integer Affected
	 */
	protected function updateUserProperties(UserProperties $properties){
		if(count($this->config->userPropertiesMap->toArray()) > 0){
			$qb = new QueryBuilder();
		
			$qb->update(Tbl::get('TBL_USERS_PROPERTIES'));
			
			foreach($this->config->userPropertiesMap as $objectKey => $dbKey){
				if($properties->$objectKey === '' or $properties->$objectKey === null){
					$qb->set(new Field($dbKey), new Literal("NULL"));
				}
				else{
					$qb->set(new Field($dbKey), $properties->$objectKey);
				}
			}
			
			$qb->where($qb->expr()->equal(new Field('user_id'), $properties->userId));
			return $this->query->exec($qb->getSQL())->affected();
		}
		return 0;
	}
	
	/**
	 * Get Users permissions
	 * 
	 * @param integer $userId
	 * @param integer $cacheMinutes
	 * @throws InvalidArgumentException
	 * @return UserPermissions
	 */
	protected function getPermissionsObject($userId, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		
		$qb = new QueryBuilder();
		$qb1 = new QueryBuilder();
		$qb2 = new QueryBuilder();
		
		$qb1->select(array(new Field('permission_id'), new Field('args')))
			->from(Tbl::get('TBL_USERS_PERMISSIONS'))
			->where($qb1->expr()->equal(new Field('user_id'), $userId));
		
		$qb2->select(array(new Field('permission_id', 'gp'), new Field('args', 'gp')))
			->from(Tbl::get('TBL_USERS_GROUPS'), 'ug')
			->innerJoin(Tbl::get('TBL_GROUPS_PERMISSIONS'), 'gp', $qb2->expr()->equal(new Field('group_id', 'ug'), new Field('group_id', 'gp')))
			->where($qb1->expr()->equal(new Field('user_id', 'ug'), $userId));
		
		$union = new Unionx();
		$union->add($qb1);
		$union->add($qb2);
		
		$qb->select(array(new Field('*', 'perms'), new Field('args', 'tbl')))
			->from($union, 'tbl')
			->leftJoin(Tbl::get('TBL_PERMISSIONS'), 'perms', $qb2->expr()->equal(new Field('permission_id', 'tbl'), new Field('id', 'perms')));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes, $cacheTag);
		
		$permissionsData = $this->query->fetchRecords();
		
		$perms =  new UserPermissions();
		foreach($permissionsData as $row){
			$permObject = UserPermissionsManager::getPermissionsObjectFromData($row);
			$perms->permissionsList[$permObject->name] = $permObject;
		}
		
		return $perms;
	}
	
	/**
	 * Get User Properties
	 * 
	 * @param integer $userId
	 * @param integer $cacheMinutes
	 * @throws InvalidArgumentException
	 * @return UserProperties
	 */
	protected function getPropertiesObject($userId, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){

		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer");
		}
		
		$qb = new QueryBuilder();
		
		$qb->select(new Field('*'))
			->from(Tbl::get('TBL_USERS_PROPERTIES'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
		$this->query->exec($qb->getSQL(), $cacheMinutes, $cacheTag);
		
		$properties = new UserProperties();
		
		$properties->userId = $userId;
		
		if($this->query->countRecords()){
			$data = $this->query->fetchRecord();
			foreach($this->config->userPropertiesMap as $objectKey => $dbKey){
				if(isset($data[$dbKey])){
					$properties->$objectKey = $data[$dbKey];
				}
				else{
					$properties->$objectKey = null;
				}
			}
		}		
		
		return $properties;
	}
	
	/**
	 * Get empty user object.
	 * If you overload this method in child manager class you can use extended User object
	 * 
	 *  @return User
	 */
	protected function getNewUserObject(){
		return new User();
	}
	
	/**
	 * Get user Object from raw DB data
	 * 
	 * @param array $data
	 * @param @param integer $initObjects For ex. INIT_PROPERTIES | INIT_PERMISSIONS
	 * @param integer $cacheMinutes
	 * @return User
	 */
	protected function getUserObjectFromData($data, $initObjects = self::INIT_ALL, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		$user = $this->getNewUserObject();
		
		$user->id = $data['id'];
		$user->enabled = $data['enabled'];
		$user->creationDate = $data['creation_date'];
		$user->creationTime = $data['creation_time'];
		$user->login = $data['login'];
		$user->password = $data['password'];
		$user->salt = $data['salt'];
        if($this->config->saveLastLoginDateIP) {
            $user->lastLoginIP = $data['last_login_ip'];
            $user->lastLoginDate = $data['last_login_date'];
        }
		$user->email = $data['email'];
		$user->emailConfirmed = $data['email_confirmed'];
		
		if(($initObjects & self::INIT_PROPERTIES) != 0){
			$user->props = $this->getPropertiesObject($user->id, $cacheMinutes, $cacheTag);
		}
		if(($initObjects & self::INIT_PERMISSIONS) != 0){
			$user->perms = $this->getPermissionsObject($user->id, $cacheMinutes, $cacheTag);
		}
		
		return $user;
	}
	
}
