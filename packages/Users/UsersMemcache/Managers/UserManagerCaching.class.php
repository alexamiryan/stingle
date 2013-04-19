<?php
class UserManagerCaching extends UserManager{
	
	const USER_TAG = 'usr';
	
	/**
	 * @var MemcacheWrapper
	 */
	private $memcache = null;
	
	public function __construct($config, $dbInstanceKey = null){
		parent::__construct($config, $dbInstanceKey);
		
		$this->memcache = $this->query->memcache;
	}
	
	public function getUserById($userId, $initObjects = self::INIT_ALL, $cacheMinutes = 0, $cacheTag = null){
		if($this->memcache != null){
			$key = $this->memcache->getNamespacedKey(self::USER_TAG . $userId);
			$cache = $this->memcache->get($key);
			
			if($cache !== false and !empty($cache) and is_a($cache, "User") and isset($cache->id) and !empty($cache->id)){
				return $cache;
			}
		
			$user = parent::getUserById($userId, self::INIT_ALL);
			
			$this->memcache->set($key, $user, 0);
			
			return $user;
		}
		else{
			return parent::getUserById($userId, $initObjects);
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
		
		if($this->memcache != null){
			$this->memcache->invalidateCacheByTag(self::USER_TAG . $userId);
		}
	}
}
