<?php
class UserManagerCaching extends UserManager{
	
	const USER_TAG = 'uo:usr';
	
	public function __construct($config, $dbInstanceKey = null){
		parent::__construct($config, $dbInstanceKey);
	}
	
	public function getUserById($userId, $initObjects = self::INIT_ALL, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null){
		$enabledStatus = ConfigManager::getConfig("Db", "Memcache")->AuxConfig->enabled;
		$enabledUsersCachingStatus = ConfigManager::getConfig("Users", "UsersMemcache")->AuxConfig->enabled;
		$objectCacheMinutes = ConfigManager::getConfig("Users", "UsersMemcache")->AuxConfig->objectCacheMinutes;
		if($enabledStatus and $enabledUsersCachingStatus and $objectCacheMinutes !== MemcacheWrapper::MEMCACHE_OFF){
			$key =  self::USER_TAG . $userId;
			$cache = Reg::get('memcache')->getObject($key);
			
			if($cache !== false and !empty($cache) and is_a($cache, "User") and isset($cache->id) and !empty($cache->id)){
				return $cache;
			}
			
			$user = parent::getUserById($userId, self::INIT_ALL, $cacheMinutes, $cacheTag);
			
			Reg::get('memcache')->setObject($key, '', $user, $objectCacheMinutes);
			
			return $user;
		}
		else{
			return parent::getUserById($userId, $initObjects, $cacheMinutes, $cacheTag);
		}
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
		
		Reg::get('memcache')->invalidateCacheByTag(self::USER_TAG . $userId);
		
		$hookParams = array('userId'=>$userId);
		HookManager::callHook("ClearUserCache", $hookParams);
	}
}
