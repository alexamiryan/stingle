<?
class UserAuthorization extends DbAccessor{
	
	protected $um;
	protected $config;
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->config = $config;
		$this->um = Reg::get(ConfigManager::getConfig("Users", "Users")->Objects->UserManager);
	}
	
	/**
	 * Check validity of username, password and other auth factors
	 * 
	 * @param string $username
	 * @param string $password
	 * @param array $additionalCredentials
	 * @param boolean $writeCookie
	 * @throws UserAuthFailedException
	 * @return User
	 */
	public function checkCredentials($username, $password, $additionalCredentials = array(), $writeCookie = false){
		$qb = new QueryBuilder();
		
		$qb->select(new Field('id'), new Field('password'), new Field('salt'))
			->from(Tbl::get('TBL_USERS', 'UserManager'))
			->where($qb->expr()->equal(new Field('login'), $username));
		
		$this->query->exec($qb->getSQL());
		
		if($this->query->countRecords() == 1){
			$userData = $this->query->fetchRecord();
				
			$hashToCheck = static::getUserPasswordHash($password, $userData['salt']);
				
			if($userData['password'] === $hashToCheck){
				$usr = $this->doLogin($userData['id'], $additionalCredentials, $writeCookie);
				
				try{
					$hookParams = array("user" => $usr, "additionalCredentials" => $additionalCredentials);
					HookManager::callHook("UserAuthSuccess", $hookParams);
				}
				catch(UserAuthFailedException $e){
					$this->doLogout();
					throw $e;
				}
				
				return $usr;
			}
		}
		
		// Failed login nothing returned from above code
		$hookParams = array("username" => $username);
		HookManager::callHook("UserAuthFail", $hookParams);
		
		throw new UserAuthFailedException("Incorrect login/password combination");
	}
	
	/**
	 * Login user of given user id
	 * 
	 * @param integer $userId
	 * @param boolean $writeCookie
	 *
	 * @return User
	 */
	public function doLogin($userId, $additionalCredentials = array(), $writeCookie = false){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId have to be non zero integer!");
		}
		
		$usr = $this->um->getUserById($userId, UserManager::INIT_ALL, false);
		
		$this->checkIfLoginIsAllowed($usr);
		
		$hookParams = array("user" => $usr, "additionalCredentials" => $additionalCredentials);
		HookManager::callHook("OnUserLogin", $hookParams);
		
		$this->saveUserIdInSession($usr);
		
		if($writeCookie){
			$this->writeLoginCookie($usr);
		}
			
		return $usr;
		
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
	 * @return User
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
	
	/**
	 * Get user passwowrd hash using plain password and salt
	 * 
	 * @param string $password
	 * @param string $salt
	 */
	public static function getUserPasswordHash($password, $salt){
		$config = ConfigManager::getConfig("Users", "Users")->AuxConfig;
		return Crypto::byte2hex(Crypto::pbkdf2("SHA512", $password, $config->siteSalt . $salt, $config->pbdkf2IterationCount, 64));
	}
	
	/**
	 * Save userId in session to indicate 
	 * that user is logged in
	 *
	 * @param integer $userId
	 */
	protected function saveUserIdInSession(User $usr){
		$_SESSION[$this->config->sessionVarName] = $usr->id;
	}
	
	/**
	 * Write long term login cookie for the user.
	 * Ususally used in remember me functionality in login forms.
	 * 
	 * @param User $usr
	 */
	protected function writeLoginCookie(User $usr){
		$dateInfo = getdate();
		$expTime = $dateInfo[0] + (60 * 60 * 24 * $this->config->rememberDaysCount);
		$cookieValue = AES256::encrypt($usr->id . ":" . hash('sha256', $usr->login . ":" . $usr->password));
		
		setcookie($this->config->loginCookieName, $cookieValue, $expTime, '/');
	}
	
	/**
	 * Check if user is enabled and allowed to login
	 * 
	 * @param User $usr
	 * @throws UserDisabledException
	 */
	protected function checkIfLoginIsAllowed(User $usr){
		if($usr->enabled == UserManager::STATE_ENABLED_DISABLED){
			$this->doLogout();
			throw new UserDisabledException("Account is disabled");
		}
	}
	
}
?>