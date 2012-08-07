<?
class UserAuthorization extends DbAccessor{
	
	protected $um;
	protected $config;
	
	const TBL_SECURITY_INVALID_LOGINS_LOG 	= 'security_invalid_logins_log';

	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->config = $config;
		$this->um = Reg::get(ConfigManager::getConfig("Users", "Users")->Objects->UserManager);
	}
	
	/**
	 * Does login operation
	 * @param string $username
	 * @param string $password
	 * @param bool $writeCookie
	 * @param bool $isPasswordEncrypted
	 *
	 * @throws RuntimeException (Codes: 1 - Incorrect login/password combination, 2 - Account is disabled)
	 */
	public function doLogin($username, $password, $writeCookie = false){

		$qb = new QueryBuilder();
		
		$qb->select(new Field('id'), new Field('password'), new Field('salt'))
			->from(Tbl::get('TBL_USERS', 'UserManager'))
			->where($qb->expr()->equal(new Field('login'), $username));
		
		$this->query->exec($qb->getSQL());
		
		if($this->query->countRecords() == 1){
			$userData = $this->query->fetchRecord();
			
			$hashToCheck = static::getUserPasswordHash($password, $userData['salt']);
			
			if($userData['password'] === $hashToCheck){
				$usr = $this->getUserOnSuccessAuth($userData['id'], $writeCookie);
				
				HookManager::callHook("UserAuthSuccess", array("user" => $usr));
			}
		}
		
		// Failed login nothing returned from above code
		$this->handleInvalidLoginAttempt();
		HookManager::callHook("UserAuthFail", array("username" => $username));
		throw new UserAuthFailedException("Incorrect login/password combination");
	}
	
	/**
	 * Logs in User only by knowing User ID
	 * @param int $userId
	 * @param bool $writeCookie
	 */
	public function doLoginByUserId($userId, $writeCookie = false){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer!");
		}
		return $this->getUserOnSuccessAuth($userId, $writeCookie);
	}
	
	/**
	 * Does logout operation
	 */
	public function doLogout(){
		unset($_SESSION[$this->config->sessionVarName]);
		setcookie($this->config->loginCookieName, null, null, '/');
	}
	
	/**
	 * Get User from request data.
	 *
	 *  @return User
	 */
	public function getUserFromRequest(){
		if(isset($_SESSION[$this->config->sessionVarName]) and is_numeric($_SESSION[$this->config->sessionVarName])){
			$usr = $this->um->getUserById($_SESSION[$this->config->sessionVarName]);
			$this->checkIfLoginIsAllowed($usr);
			return $usr;
		}
		elseif(!empty($_COOKIE[$this->config->loginCookieName])){
			try{
				$cookieData = explode(":", AES256::decrypt($_COOKIE[$this->config->loginCookieName]));
				if(count($cookieData) == 2){
					list($userId, $hash) = $cookieData;
		
					$usr = $this->um->getUserById($userData['id'], UserManager::INIT_NONE);
		
					$correctHashFotUser = hash('sha256', $usr->login . ":" . $usr->password);
		
					if($encryptedSalt === $correctSalt){
						return $this->getUserOnSuccessAuth($usr->id);
					}
				}
			}
			catch(Exception $e) {}
		}
		return null;
	}
	
	public static function getUserPasswordHash($password, $salt){
		$config = ConfigManager::getConfig("Users", "Users")->AuxConfig;
		return Crypto::pbkdf2("SHA512", $password, $config->siteSalt . $salt, $config->pbdkf2IterationCount, 512);
	}
	
	protected function getUserOnSuccessAuth($userId, $writeCookie = false){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer!");
		}
		
		$usr = $this->um->getUserById($userId);
		
		$this->checkIfLoginIsAllowed($usr);
		
		$this->saveUserIdInSession($usr->getId());
		if($writeCookie){
			$dateInfo = getdate();
			$expTime = $dateInfo[0] + (60 * 60 * 24 * $this->config->rememberDaysCount);
			$cookieValue = AES256::encrypt($usr->getId() . ":" . hash('sha256', $usr->login . ":" . $usr->password));
		
			setcookie($this->config->loginCookieName, $cookieValue, $expTime, '/');
		}
			
		if(Reg::get('packageMgr')->isPluginLoaded("Security", "RequestLimiter") and $this->config->bruteForceProtectionEnabled){
			if(isset($_SERVER['REMOTE_ADDR'])){
				$qb = new QueryBuilder();
				$this->query->exec(
						$qb->delete(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG'))
						->where($qb->expr()->equal(new Field('ip'), $_SERVER['REMOTE_ADDR']))
						->getSQL()
				);
			}
		}
		
		return $usr;
	}
	
	protected function checkIfLoginIsAllowed(User $usr){
		if(!$usr->isEnabled()){
			$this->doLogout();
			throw new UserDisabledException("Account is disabled");
		}
	}
	
	protected function handleInvalidLoginAttempt(){
		if(Reg::get('packageMgr')->isPluginLoaded("Security", "RequestLimiter") and $this->config->bruteForceProtectionEnabled){
			if(isset($_SERVER['REMOTE_ADDR'])){
				$qb = new QueryBuilder();
					
				$this->query->exec(
						$qb->select(new Field('count'))
							->from(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG'))
							->where($qb->expr()->equal(new Field('ip'), $_SERVER['REMOTE_ADDR']))
							->getSQL()
				);
					
				$failedAuthCount = $this->query->fetchField('count');
			
				$newFailedAuthCount = $failedAuthCount + 1;
			
				if($newFailedAuthCount >= $this->config->failedAuthLimit){
					Reg::get(ConfigManager::getConfig("Security", "RequestLimiter")->Objects->RequestLimiter)->blockIP();
						
					$qb = new QueryBuilder();
					$this->query->exec(
							$qb->delete(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG'))
								->where($qb->expr()->equal(new Field('ip'), $_SERVER['REMOTE_ADDR']))
								->getSQL()
					);
						
					throw new RequestLimiterTooManyAuthTriesException("Too many unsucessful authorization tries.");
				}
			
				$qb = new QueryBuilder();
				$this->query->exec(
						$qb->insert(Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG'))
							->values(array('ip' => $_SERVER['REMOTE_ADDR']))
							->onDuplicateKeyUpdate()
							->set(new Field('count'), $qb->expr()->sum(new Field('count'), 1))
							->getSQL()
				);
			}
		}
	}
	
	/**
	 * Save userId for next requests
	 * 
	 * @param integer $userId
	 */
	protected function saveUserIdInSession($userId){
		$_SESSION[$this->config->sessionVarName] = $userId;
	}
}
?>