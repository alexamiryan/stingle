<?php
class ProfileManager extends DbAccessor{
	
	const TBL_PROFILE_KEYS 		= "profile_keys";
	const TBL_PROFILE_VALUES 	= "profile_values";
	const TBL_PROFILE_SAVE 		= "profile_save";
	
	const KEY_TYPE_SINGLE 		= "single";
	const KEY_TYPE_MULTI 		= "multi";
	const KEY_TYPE_RANGE 		= "range";
	const KEY_TYPE_CUSTOM 		= "custom";
	const KEY_TYPE_CUSTOMBIG	= "customBig";
	
	const KEY_STATUS_ENABLED 	= "1";
	const KEY_STATUS_DISABLED	= "0";

	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_KEYS = 1;
	const INIT_VALUES = 2;
	
	// INIT_ALL Should be next power of 2 minus 1
	const INIT_ALL = 3;
	
	public function __construct($instanceName = null){
		parent::__construct($instanceName);
	}
	
	
	/**
	 * Get profile
	 * 
	 * @param type $userId
	 * @param type $group
	 * @param type $subgroup (Array(keyName => subgroup(s)))
	 * @param type $type
	 * @param type $cacheMinutes
	 * @param type $cacheTag
	 * @return \Profile
	 */
	public function getProfile($userId = null, $group = null, $subgroup = null, $type=null, $cacheMinutes = 0, $cacheTag = null){
		$profile = new Profile();
		
		$userSaves = null;
		if(!empty($userId) and is_numeric($userId)){
			$profile->saves = $this->getUserProfileSave($userId, $group);
		}
		
		$keysFilter = new ProfileKeyFilter();
		
		if(!empty($group)){
			if(is_array($group)){
				$keysFilter->setGroups($group);
			}
			else{
				$keysFilter->setGroup($group);
			}
		}
		
		if(!empty($type)){
			if(is_array($type)){
				$keysFilter->setKeyTypes($type);
			}
			else{
				$keysFilter->setKeyType($type);
			}
		}
		
		$keyValuePairs = array();
		
		$keys = $this->getKeys($keysFilter, $cacheMinutes, $cacheTag);
		foreach ($keys as $key){
			$keyValue = new ProfileKeyValuePair();
			
			$keyValue->key = $key;
			
			$valuesFilter = new ProfileValueFilter();
			$valuesFilter->setKeyId($key->id);
			if(!empty($subgroup) and isset($subgroup[$key->name]) and !empty($subgroup[$key->name])){
				$valuesFilter->setSubgroup($subgroup[$key->name]);
			}
			
			$keyValue->values = $this->getValues($valuesFilter);
			
			$keyValuePairs[$key->id] = $keyValue;
		}
		
		$profile->keyValues = $keyValuePairs;
		
		return $profile;
	}
	
	public function getUserProfileSave($userId, $group = null, $cacheMinutes = 0, $cacheTag = null){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$filter = new UserProfileFilter();
		$filter->setUserId($userId);
                
                if(!empty($group)){
			if(is_array($group)){
				$filter->setGroups($group);
			}
			else{
				$filter->setGroup($group);
			}
		}
		
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
	
		$userSaves = array();
		
		if($sql->countRecords()){
			while(($profileDbRow = $sql->fetchRecord()) != false){
				$userSave = null;
				if(isset($userSaves[$profileDbRow['key_id']])){
					$userSave = $userSaves[$profileDbRow['key_id']];
				}
				else{
					$userSave = new ProfileUserSave();
					$userSave->keyId = $profileDbRow['key_id'];
				}
				
				if(!empty($profileDbRow['key_group'])){
					$userSave->keyGroup = $profileDbRow['key_group'];
				}
                                
				if(!empty($profileDbRow['value_id'])){
					array_push($userSave->valueIds, $profileDbRow['value_id']);
				}
				
				if(!empty($profileDbRow['value_cust'])){
					$userSave->custValue = $profileDbRow['value_cust'];
				}

				$userSaves[$profileDbRow['key_id']] = $userSave;
			}
		}
	
		return $userSaves;
	}
	
	public function getSavedUserProfile($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$filter = new UserProfileFilter();
		$filter->setUserId($userId);
		
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
		
		if($sql->countRecords()){
			return $sql->fetchRecords();
		}
		return false;
	}
	
	public function getKeys(ProfileKeyFilter $filter = null, $cacheMinutes = MemcacheWrapper::MEMCACHE_UNLIMITED, $cacheTag = null){
		if(empty($filter)){
			$filter = new ProfileKeyFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
		
		$keys = array();
		
		if($sql->countRecords()){
			while(($keyDbRow = $sql->fetchRecord()) != false){
				$key = new ProfileKey();
				$key->id = $keyDbRow['id'];
				$key->name = $keyDbRow['name'];
				$key->altName = $keyDbRow['alt_name'];
				$key->type = $keyDbRow['type'];
				$key->rangeMin = $keyDbRow['range_min'];
				$key->rangeMax = $keyDbRow['range_max'];
				$key->group = $keyDbRow['grp'];
				$key->sortId = $keyDbRow['sort_id'];
				$key->isEnabled = $keyDbRow['is_enabled'];
				$keys[$keyDbRow['id']] = $key;
			}
		}
		
		return $keys;
	}
	
	public function getKeyById($keyId, $cacheMinutes = MemcacheWrapper::MEMCACHE_UNLIMITED, $cacheTag = null){
		$filter = new ProfileKeyFilter();
		$filter->setKeyId($keyId);
		
		$keys = $this->getKeys($filter, $cacheMinutes, $cacheTag);
		
		if(count($keys) == 1){
			$slice = array_slice($keys, 0, 1);
			return $slice[0];
		}
		return false;
	}
	
	public function getValues(ProfileValueFilter $filter = null, $initKeyObject = false, $cacheMinutes = MemcacheWrapper::MEMCACHE_UNLIMITED, $cacheTag = null){
		if(empty($filter)){
			$filter = new ProfileValueFilter();
		}
	
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
	
		$values = array();
	
		if($sql->countRecords()){
			while(($valueDbRow = $sql->fetchRecord()) != false){
				$value = new ProfileValue();
				$value->id = $valueDbRow['id'];
				$value->keyId = $valueDbRow['key_id'];
				$value->subgroup = $valueDbRow['subgroup'];
				$value->name = $valueDbRow['name'];
				$value->sortId = $valueDbRow['sort_id'];
				
				if($initKeyObject){
					$value->key = $this->getKeyById($value->keyId);
				}
				
				$values[$valueDbRow['id']] = $value;
			}
		}
	
		return $values;
	}
	
	public function getValueById($valueId, $cacheMinutes = MemcacheWrapper::MEMCACHE_UNLIMITED, $cacheTag = null){
		$filter = new ProfileValueFilter();
		$filter->setValueId($valueId);
	
		$values = $this->getValues($filter, $cacheMinutes, $cacheTag);
	
		if(count($values) == 1){
			return $values[0];
		}
		return false;
	}
	
	public function editProfile($userId, $profileSaves = array()){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
			
		$this->deleteProfile($userId);
		
		if(count($profileSaves)){
		
			foreach($profileSaves as $save){
				if(is_a($save, "ProfileUserSave")){
					if($save->custValue === null){
						foreach($save->valueIds as $valueId){
							$qb = new QueryBuilder();
							
							$qb->insert(Tbl::get('TBL_PROFILE_SAVE'))
								->values(array(
										'user_id' => $userId,
										'key_id' => $save->keyId,
										'key_group' => $save->keyGroup,
										'value_id' => $valueId
							));
							$this->query->exec($qb->getSQL());
						}
					}
					else{
						$qb = new QueryBuilder();
							
						$qb->insert(Tbl::get('TBL_PROFILE_SAVE'))
							->values(array(
								'user_id' => $userId,
								'key_id' => $save->keyId,
                                                                'key_group' => $save->keyGroup,
								'value_cust' => $save->custValue
						));
						$this->query->exec($qb->getSQL());
					}
					
				}
			}
		}
	}
	
	public function deleteProfile($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$qb = new QueryBuilder();
		
		$qb->delete(Tbl::get('TBL_PROFILE_SAVE'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	
}