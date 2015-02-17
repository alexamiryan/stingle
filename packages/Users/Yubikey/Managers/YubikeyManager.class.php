<?php

class YubikeyManager extends DbAccessor
{
	public function getAllKeys(){
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
	
	public function getKeyById($keyId){
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
	
	public function addKey(YubikeyObject $key){
		if(empty($key)){
			throw new YubikeyException("given yey object is empty");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_KEYS', 'YubikeyUserAuthorization'))
			->values(array(	"key" => $key->key, "Description" => $key->description, 
							"status" => $key->status));
			
		$this->query->exec($qb->getSQL());	
	}
	
	public function updateKey(YubikeyObject $key){
		if(empty($key)){
			throw new YubikeyException("given yey object is empty");
		}
		$qb = new QueryBuilder();
		$qb->update(Tbl::get('TBL_KEYS', 'YubikeyUserAuthorization'))
			->set(new Field("key"), $key->key)
			->set(new Field("Description"), $key->description)
			->set(new Field("status"), $key->status)
			->where($qb->expr()->equal(new Field('id'), $key->id));
		
		$this->query->exec($qb->getSQL());
	}
	
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
	
	public function isKeyExist($key){
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
	
	public function getYubikeyToGroupMap(YubikeyObject $yubikey){
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
	
	public function getYubikeyToUserMap(YubikeyObject $yubikey){
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
}