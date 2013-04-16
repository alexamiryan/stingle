<?php
class UserManagerCaching extends UserManager{
	
	const USER_TAG = 'usr';
	
	public function getUserById($userId, $initObjects = self::INIT_ALL, $cacheMinutes = 0, $cacheTag = null){
		return parent::getUserById($userId, $initObjects, -1, self::USER_TAG . $userId);
	}
	
	public function createUser(User $user){
		$newUserId = parent::createUser($user);
		
		$this->invalidateUserCacheByUserId($newUserId);
		
		return $newUserId;
	}
	
	public function updateUser(User $user){
		$result = parent::updateUser($user);
		
		$this->invalidateUserCacheByUserId($user->id);
		
		return $result;
	}
	
	public function setUserPassword(User $user, $password){
		$result = parent::setUserPassword($user, $password);
		
		$this->invalidateUserCacheByUserId($user->id);
		
		return $result;
	}
	
	public function deleteUser(User $user){
		$result = parent::deleteUser($user);
		
		$this->invalidateUserCacheByUserId($user->id);
		
		return $result;
	}
	
	protected function invalidateUserCacheByUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer"); 
		}
		
		$this->query->invalidateCacheByTag(self::USER_TAG . $userId);
	}
}
