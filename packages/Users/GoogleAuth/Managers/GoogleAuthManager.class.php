<?php
/**
 * 
 *	GoogleAuth Manager
 */
class GoogleAuthManager extends DbAccessor
{

	public function isEnabled($userId){
		if(!is_numeric($userId) or empty($userId)){
			throw new GoogleAuthException("User Id is not valid!");
		}
		
		$qb = new QueryBuilder();
		$qb->select('enabled')
			->from(Tbl::get('TBL_GOOGLE_AUTH_MAP', 'GoogleAuthUserAuthorization'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
		
		$this->query->exec($qb->getSQL());
		
		if($this->query->countRecords()){
			$enabled = $this->query->fetchField('enabled');
			if($enabled == GoogleAuthUserAuthorization::STATUS_GOOGLE_AUTH_ENABLED){
				return true;
			}
		}
		return false;
	}
	
	public function isHasSecret($userId){
		if(!is_numeric($userId) or empty($userId)){
			throw new GoogleAuthException("User Id is not valid!");
		}
	
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count(new Field('*'), 'cnt'))
			->from(Tbl::get('TBL_GOOGLE_AUTH_MAP', 'GoogleAuthUserAuthorization'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
	
		$count = $this->query->fetchField('cnt');
	
		if($count > 0){
			return true;
		}
		return false;
	}
	
	public function getSecret($userId){
		if(!is_numeric($userId) or empty($userId)){
			throw new GoogleAuthException("User Id is not valid!");
		}
	
		$qb = new QueryBuilder();
		$qb->select('secret')
			->from(Tbl::get('TBL_GOOGLE_AUTH_MAP', 'GoogleAuthUserAuthorization'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
	
		$this->query->exec($qb->getSQL());
	
		if($this->query->countRecords()){
			return $this->query->fetchField('secret');
		}
		return false;
	}
	
	public function getQrCodeURLFromSecret($secret){
		return $gAuth->getQRCodeGoogleUrl(ConfigManager::getConfig("Users", "GoogleAuth")->AuxConfig->siteName, $secret);
	}
	
	public function getQrCodeURLForUser($userId){
		return $this->getQrCodeURLFromSecret($this->getSecret($userId));
	}
	
	
	public function setNewGoogleAuthForUser($userId, $returnQrCodeLink = true){
		if(!is_numeric($userId) or empty($userId)){
			throw new GoogleAuthException("User Id is not valid!");
		}
	
		$this->deleteGoogleAuthForUser($userId);
		
		$gAuth = new GoogleAuthenticator();
		
		$newSecret = $gAuth->createSecret();
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_GOOGLE_AUTH_MAP', 'GoogleAuthUserAuthorization'))
			->values(array(
				'user_id' => $userId,
				'secret' => $newSecret
		));
		
		$this->query->exec($qb->getSQL());
		
		if($returnQrCodeLink){
			return $this->getQrCodeURLFromSecret($newSecret);
		}
		else{
			return $newSecret;
		}
	}
	
	public function deleteGoogleAuthForUser($userId){
		if(!is_numeric($userId) or empty($userId)){
			throw new GoogleAuthException("User Id is not valid!");
		}
	
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_GOOGLE_AUTH_MAP', 'GoogleAuthUserAuthorization'))
			->where($qb->expr()->equal(new Field('user_id'), $userId));
	
		$this->query->exec($qb->getSQL());
	
		return $this->query->affected();
	}
	
	public function enableGoogleAuthForUser($userId){
		return $this->changeEnabledStatusForUser($userId, GoogleAuthUserAuthorization::STATUS_GOOGLE_AUTH_ENABLED);
	}
	
	public function disableGoogleAuthForUser($userId){
		return $this->changeEnabledStatusForUser($userId, GoogleAuthUserAuthorization::STATUS_GOOGLE_AUTH_DISABLED);
	}
	
	protected function changeEnabledStatusForUser($userId, $status){
		if(!is_numeric($userId) or empty($userId)){
			throw new GoogleAuthException("User Id is not valid!");
		}
	
		$secret = $this->getSecret($userId);
		
		if(!empty($secret)){
			$qb = new QueryBuilder();
			
			$qb->update(Tbl::get('TBL_GOOGLE_AUTH_MAP', 'GoogleAuthUserAuthorization'))
			->set(new Field('enabled'), $status)
			->where($qb->expr()->equal(new Field('user_id'), $userId));
			
			$this->query->exec($qb->getSQL());
			
			return $this->query->affected();
		}
		
		throw new GoogleAuthException("User has no GoogleAuth set. Cannot change enabled status.");
	}
	
	
}