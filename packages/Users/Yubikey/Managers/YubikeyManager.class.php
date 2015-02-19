<?php

/**
 * 
 *	Yubikey Manager
 */
class YubikeyManager extends DbAccessor
{
	/**
	 * Return all Yubikeys from DB
	 * @return Array()
	 */
	public function getAllYubikeys(){
		$qb = new QueryBuilder();
		$qb->select('*')
			->from(Tbl::get('TBL_KEYS', 'YubikeyUserAuthorization'));
		
		$this->query->exec($qb->getSQL());
		
		$yubikeys = array();
		if($this->query->countRecords()){
			while(($row = $this->query->fetchRecord()) != false){				
				array_push($yubikeys, $this->getFilledYubikey($row));
			}
		}
		return $yubikeys;
	}
	
	/**
	 * Return Yubikey object by given Id
	 * @param Integer $keyId
	 * @throws YubikeyException
	 * @return YubikeyObject
	 */
	public function getYubikeyById($keyId){
		if(!is_numeric($keyId)){
			throw new YubikeyException("Yubikey Id is not numeric!");
		}
		$qb = new QueryBuilder();
		$qb->select("*")
			->from(Tbl::get('TBL_KEYS', 'YubikeyUserAuthorization'))
			->where($qb->expr()->equal(new Field('id'), $keyId));
		
		$this->query->exec($qb->getSQL());
		
		if($this->query->countRecords() !== 1){
			throw new YubikeyException("There is no Yubikey object by given Id, or it is not unique"); 
		}
		
		return $this->getFilledYubikey($this->query->fetchRecord());
	}
	
	/**
	 * Add new Yubikey in DB
	 * @param YubikeyObject $key
	 * @throws YubikeyException
	 * @return Ambigous <boolean, number>
	 */
	public function addYubikey(YubikeyObject $key){
		if(empty($key)){
			throw new YubikeyException("given yey object is empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_KEYS', 'YubikeyUserAuthorization'))
			->values(array(	"key" => $key->key, "Description" => $key->description, 
							"status" => $key->status));
			
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Update current yubikey
	 * @param YubikeyObject $key
	 * @throws YubikeyException
	 * @return Ambigous <boolean, number>
	 */
	public function updateYubikey(YubikeyObject $key){
		if(empty($key)){
			throw new YubikeyException("given yey object is empty");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_KEYS', 'YubikeyUserAuthorization'))
			->set(new Field("key"), $key->key)
			->set(new Field("Description"), $key->description)
			->set(new Field("status"), $key->status)
			->where($qb->expr()->equal(new Field('id'), $key->id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Delete given yubikey from db
	 * @param YubikeyObject $yubikey
	 * @throws YubikeyRequiredException
	 * @return Ambigous <boolean, number>
	 */
	public function deleteYubikey(YubikeyObject $yubikey){
		if(empty($yubikey)){
			throw new YubikeyRequiredException("Yubikey object is empty");
		}
		if(empty($yubikey->id)){
			throw new YubikeyRequiredException("Yubikey Id have to non empty");
		}
		$qb= new QueryBuilder();
		$qb->delete(TBL::get('TBL_KEYS', 'YubikeyUserAuthorization'))
		->where($qb->expr()->equal(new Field('id'), $yubikey->id));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Check given yubikey exists on DB
	 * @param String $key
	 * @throws YubikeyException
	 * @return boolean
	 */
	public function isYubikeyExists($key){
		if(empty($key)){
			throw new YubikeyException("Yubikey is have to been non empty string!");
		}
		$qb = new QueryBuilder();
		$qb->select('*')
		->from(Tbl::get('TBL_KEYS', 'YubikeyUserAuthorization'))
		->where($qb->expr()->equal(new Field('key'), $key));
		
		$this->query->exec($qb->getSQL());
		if($this->query->countRecords() > 0){
			return true;
		}
		return false;
	}
	
	/**
	 * Return current yubikeys connected groups list
	 * @param YubikeyObject $yubikey
	 * @throws YubikeyRequiredException
	 * @return Array of UserGroup objects:NULL
	 */
	public function getYubikeyGroups(YubikeyObject $yubikey){
		if(empty($yubikey)){
			throw new YubikeyRequiredException("Yubikey object is empty");
		}
		if(empty($yubikey->id)){
			throw new YubikeyRequiredException("Yubikey Id have to non empty");
		}
		
		$qb= new QueryBuilder();
		$qb->select(new Field('group_id'))
			->from(TBL::get('TBL_KEYS_TO_GROUPS', 'YubikeyUserAuthorization'))
			->where($qb->expr()->equal(new Field('yubikey_id'), $yubikey->id));
		$this->query->exec($qb->getSQL());
		
		$groups = array();
		if($this->query->countRecords() > 0){
			while(($row = $this->query->fetchRecord()) != false){
				$ugm = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserGroupsManager);
				$groups[] = $ugm->getGroupById($row['group_id']);
			}
		}
		return $groups;
	}
	
	/**
	 * Return Users list conected given yubikey
	 * @param YubikeyObject $yubikey
	 * @throws YubikeyRequiredException
	 * @return array() of User objects
	 */
	public function getYubikeyUsers(YubikeyObject $yubikey){
		if(empty($yubikey)){
			throw new YubikeyRequiredException("Yubikey object is empty");
		}
		if(empty($yubikey->id)){
			throw new YubikeyRequiredException("Yubikey Id have to non empty");
		}
		
		$qb= new QueryBuilder();
		$qb->select(new Field('user_id'))
		->from(TBL::get('TBL_KEYS_TO_USERS', 'YubikeyUserAuthorization'))
		->where($qb->expr()->equal(new Field('yubikey_id'), $yubikey->id));
		$this->query->exec($qb->getSQL());
		
		$users = array();
		if($this->query->countRecords() > 0){
			while(($row = $this->query->fetchRecord()) != false){
				$um = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
				$users[] = $um->getUserById($row['user_id'], UserManager::INIT_NONE);
			}
		}
		return $users;
	}
	
	/**
	 * Delete current yubikey's connected gourps
	 * @param YubikeyObject $yubikey
	 * @throws YubikeyRequiredException
	 * @return Ambigous <boolean, number>
	 */
	public function deleteYubikeyGroups(YubikeyObject $yubikey){
		if(empty($yubikey)){
			throw new YubikeyRequiredException("Yubikey object is empty");
		}
		if(empty($yubikey->id)){
			throw new YubikeyRequiredException("Yubikey Id have to non empty");
		}
		$qb= new QueryBuilder();
		$qb->delete(TBL::get('TBL_KEYS_TO_GROUPS', 'YubikeyUserAuthorization'))
			->where($qb->expr()->equal(new Field('yubikey_id'), $yubikey->id));
		
		return $this->query->exec($qb->getSQL())->affected();
		
	}
	
	/**
	 * Delete current yubikey's connected users
	 * @param YubikeyObject $yubikey
	 * @throws YubikeyRequiredException
	 * @return Ambigous <boolean, number>
	 */
	public function deleteYubikeyUsers(YubikeyObject $yubikey){
		if(empty($yubikey)){
			throw new YubikeyRequiredException("Yubikey object is empty");
		}
		if(empty($yubikey->id)){
			throw new YubikeyRequiredException("Yubikey Id have to non empty");
		}
		$qb= new QueryBuilder();
		$qb->delete(TBL::get('TBL_KEYS_TO_USERS', 'YubikeyUserAuthorization'))
		->where($qb->expr()->equal(new Field('yubikey_id'), $yubikey->id));
	
		return $this->query->exec($qb->getSQL())->affected();
	
	}
	
	/**
	 * Connect given Usergroup to current yubikey
	 * @param YubikeyObject $yubikey
	 * @param UserGroup $group
	 * @throws YubikeyRequiredException
	 * @return Ambigous <boolean, number>
	 */
	public function addYubikeyGroup(YubikeyObject $yubikey, UserGroup $group){
		if(empty($yubikey)){
			throw new YubikeyRequiredException("Yubikey object is empty");
		}
		if(empty($yubikey->id)){
			throw new YubikeyRequiredException("Yubikey Id have to non empty");
		}
		if(empty($group->id)){
			throw new YubikeyRequiredException("Group Id have to non empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_KEYS_TO_GROUPS', 'YubikeyUserAuthorization'))
		->values(array(	"yubikey_id" => $yubikey->id, "group_id" => $group->id ));
			
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Connect given User to current yubikey 
	 * @param YubikeyObject $yubikey
	 * @param User $user
	 * @throws YubikeyRequiredException
	 * @return Ambigous <boolean, number>
	 */
	public function addYubikeyUser(YubikeyObject $yubikey, User $user){
		if(empty($yubikey)){
			throw new YubikeyRequiredException("Yubikey object is empty");
		}
		if(empty($yubikey->id)){
			throw new YubikeyRequiredException("Yubikey Id have to non empty");
		}
		if(empty($user->id)){
			throw new YubikeyRequiredException("User Id have to non empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_KEYS_TO_USERS', 'YubikeyUserAuthorization'))
		->values(array(	"yubikey_id" => $yubikey->id, "user_id" => $user->id ));
			
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Return required authorization groups
	 * @return array() of UsersGourp objects
	 */
	public function getAuthorizationGroups(){
		$qb = new QueryBuilder();
		$qb->select(new Field('group_id'))
			->from(TBL::get('TBL_AUTH_GROUPS', 'YubikeyUserAuthorization'));
		$this->query->exec($qb->getSQL());
		
		$groups = array();
		if($this->query->countRecords() > 0){
			while(($row = $this->query->fetchRecord()) != false){
				$ugm = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserGroupsManager);
				$groups[] = $ugm->getGroupById($row['group_id']);
			}
		}
		return $groups;
	}
	
	/**
	 * Delete all required authorization groups
	 * @return Ambigous <boolean, number>
	 */
	public function deleteAuthorizationGroups(){
		$qb = new QueryBuilder();
		$qb->delete(TBL::get('TBL_AUTH_GROUPS', 'YubikeyUserAuthorization'));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * add new required authorization group
	 * @param UserGroup $group
	 * @throws YubikeyRequiredException
	 * @return Ambigous <boolean, number>
	 */
	public function addAuthorizationGroup(UserGroup $group){
		if(empty($group->id)){
			throw new YubikeyRequiredException("Group Id have to non empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_AUTH_GROUPS', 'YubikeyUserAuthorization'))
		->values(array( "group_id" => $group->id ));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Return required authorization users
	 * @return Array of User objects
	 */
	public function getAuthorizationUsers(){
		$qb = new QueryBuilder();
		$qb->select(new Field('user_id'))
			->from(TBL::get('TBL_AUTH_USERS', 'YubikeyUserAuthorization'));
		$this->query->exec($qb->getSQL());
	
		$users = array();
		if($this->query->countRecords() > 0){
			while(($row = $this->query->fetchRecord()) != false){
				$um = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManager);
				$users[] = $um->getUserById($row['user_id'], UserManager::INIT_NONE);
			}
		}
		return $users;
	}
	
	/**
	 * delete all required authorization users
	 * @return Ambigous <boolean, number>
	 */
	public function deleteAuthorizationUsers(){
		$qb = new QueryBuilder();
		$qb->delete(TBL::get('TBL_AUTH_USERS', 'YubikeyUserAuthorization'));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * add new required authorization user
	 * @param User $user
	 * @throws YubikeyRequiredException
	 * @return Ambigous <boolean, number>
	 */
	public function addAuthorizationUser(User $user){
		if(empty($user->id)){
			throw new YubikeyRequiredException("User Id have to non empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_AUTH_USERS', 'YubikeyUserAuthorization'))
		->values(array( "user_id" => $user->id ));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	/**
	 * Check is authorization user exists
	 * @param User $user
	 * @throws YubikeyRequiredException
	 * @return boolean
	 */
	public function isAuthorizationUserExist(User $user){
		if(empty($user->id)){
			throw new YubikeyRequiredException("User Id have to non empty");
		}
		$qb = new QueryBuilder();
		$qb->select("*")
			->from(Tbl::get('TBL_AUTH_USERS', 'YubikeyUserAuthorization'))
			->where($qb->expr()->equal(new Field('user_id'), $user->id));
		
		$this->query->exec($qb->getSQL());
		if($this->query->countRecords() > 0){
			return true;
		}
		return false;
	}
	
	/**
	 * return yubikey object combained from db table row
	 * @param Array $row
	 * @throws YubikeyException
	 * @return YubikeyObject
	 */
	private function getFilledYubikey($row){
		if(!is_array($row)){
			throw new YubikeyException("given DB yubikey row is not array");
		}
		$yubikeyObj = new YubikeyObject();
		$yubikeyObj->id = $row["id"];
		$yubikeyObj->key = $row["key"];
		$yubikeyObj->description = $row["Description"];
		$yubikeyObj->status = $row["status"];
	
		return $yubikeyObj;
	}
}