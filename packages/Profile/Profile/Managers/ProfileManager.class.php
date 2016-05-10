<?php
class ProfileManager extends DbAccessor{
	
	const TBL_PROFILE_KEYS 		= "profile_keys";
	const TBL_PROFILE_VALUES 	= "profile_values";
	const TBL_PROFILE_SAVE 		= "profile_save";
	
	const KEY_TYPE_SINGLE 		= "single";
	const KEY_TYPE_MULTI 		= "multi";
	const KEY_TYPE_CUSTOM 		= "custom";
	
	const KEY_STATUS_ENABLED 	= "1";
	const KEY_STATUS_DISABLED	= "0";

	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_KEYS = 1;
	const INIT_VALUES = 2;
	const INIT_ALL_WITHOUT_CHILDREN = 4;
	
	// INIT_ALL Should be next power of 2 minus 1
	const INIT_ALL = 7;
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	
	public function getKeys(){
		
	}
	
	public function getUserProfile($userId, $initObjects = self::INIT_ALL, $cacheMinutes = 0, $cacheTag = null){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$filter = new UserProfileFilter();
		$filter->setUserId($userId);
		
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
	
		$userProfile = new UserProfile();
		$userProfile->userId = $userId;
		
		if($sql->countRecords()){
			while(($profileDbRow = $sql->fetchRecord()) != false){
				array_push($userProfile->keysValuePairs, $this->getUserProfileFromData($profileDbRow, $initObjects, $cacheMinutes, $cacheTag));
			}
		}
	
		return $userProfile;
	}
	
	public function getKeys(ProfileKeyFilter $filter = null, $cacheMinutes = 0, $cacheTag = null){
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
				$key->key = $keyDbRow['key'];
				$key->type = $keyDbRow['type'];
				$key->sortId = $keyDbRow['sort_id'];
				$key->isEnabled = $keyDbRow['is_enabled'];
				array_push($keys, $key);
			}
		}
		
		return $keys;
	}
	
	public function getKeyById($keyId){
		$filter = new ProfileKeyFilter();
		$filter->setKeyId($keyId);
		
		$keys = $this->getKeys($filter);
		
		if(count($keys) == 1){
			return $keys[0];
		}
		return false;
	}
	
	public function getValues(ProfileValueFilter $filter = null, $cacheMinutes = 0, $cacheTag = null){
		if(empty($filter)){
			$filter = new ProfileValueFilter();
		}
	
		$sqlQuery = $filter->getSQL();
		$sql = MySqlDbManager::getQueryObject();
		$sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
	
		$values = array();
	
		if($sql->countRecords()){
			while(($valueDbRow = $sql->fetchRecord()) != false){
				$value = new ProfileKey();
				$value->id = $valueDbRow['id'];
				$value->keyId = $valueDbRow['key_id'];
				$value->childKeyId = $valueDbRow['child_key_id'];
				$value->value = $valueDbRow['value'];
				$value->sortId = $valueDbRow['sort_id'];
				array_push($values, $value);
			}
		}
	
		return $values;
	}
	
	public function getValueById($valueId){
		$filter = new ProfileValueFilter();
		$filter->setValueId($valueId);
	
		$values = $this->getValues($filter);
	
		if(count($values) == 1){
			return $values[0];
		}
		return false;
	}
	
	protected function getUserProfileFromData($data, $initObjects = self::INIT_ALL, $cacheMinutes = 0, $cacheTag = null){
		$keyValue = new ProfileKeyValuePair();
		
		$keyValue->keyId = $data['key_id'];
		
		if(($initObjects & self::INIT_KEYS) != 0){
			$keyValue->key = $this->getKeyById($data['key_id']);
		}
		
		$keyValue->valueId = $data['value_id'];
		
		if(!empty($data['value_id']) and ($initObjects & self::INIT_VALUES) != 0){
			$keyValue->value = $this->getValueById($data['value_id']);
		}
		elseif(!empty($data['value_cust'])){
			$keyValue->valueCust = $data['value_cust'];
		}
	
		return $keyValue;
	}
	
}