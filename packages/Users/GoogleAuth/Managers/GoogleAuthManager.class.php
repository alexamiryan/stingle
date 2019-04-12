<?php
/**
 * 
 *	GoogleAuth Manager
 */
class GoogleAuthManager extends DbAccessor
{
	
	protected $gAuth;
	
	public function __construct($instanceName = null){
		parent::__construct($instanceName);
		
		$this->gAuth = new GoogleAuthenticator();
	}

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
		
		$this->query->exec($qb->getSQL());
	
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
	
	public function getQrCodeURLFromSecret($secret, $login = null){
		$siteName = ConfigManager::getConfig("Users", "GoogleAuth")->AuxConfig->siteName;
		if(!empty($login)){
			$siteName .= ": $login";
		}
		return $this->gAuth->getQRCodeGoogleUrl($siteName, $secret);
	}
	
	public function getQrCodeURLForUser($userId){
		$um = Reg::get(ConfigManager::getConfig("Users", "Users")->Objects->UserManager);
		$user = $um->getUserById($userId, UserManager::INIT_NONE);
		
		return $this->getQrCodeURLFromSecret($this->getSecret($userId), $user->login);
	}
	
	
	public function generateSecret(){
		$secret = $this->gAuth->createSecret();
		
		return $secret;
	}
	
	public function verifyCode($secret, $code){
		
		return $this->gAuth->verifyCode($secret, $code);
	}
	
	public function setNewGoogleAuthForUser($userId, $secret){
		if(!is_numeric($userId) or empty($userId)){
			throw new GoogleAuthException("User Id is not valid!");
		}
		if(empty($secret)){
			throw new GoogleAuthException("Secret is empty");
		}
	
		$this->deleteGoogleAuthForUser($userId);
		
		$qb = new QueryBuilder();
		
		$qb->insert(Tbl::get('TBL_GOOGLE_AUTH_MAP', 'GoogleAuthUserAuthorization'))
			->values(array(
				'user_id' => $userId,
				'secret' => $secret
		));
		
		$this->query->exec($qb->getSQL());
		
		return $this->query->affected();
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