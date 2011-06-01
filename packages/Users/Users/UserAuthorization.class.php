<?
class UserAuthorization extends DbAccessor{
	
	protected $usr;
	protected $um;
	protected $config;
	
	const TBL_SECURITY_INVALID_LOGINS_LOG 	= 'security_invalid_logins_log';

	const EXCEPTION_INCORRECT_LOGIN_PASSWORD = 1;
	const EXCEPTION_ACCOUNT_DISABLED = 2;
	
	public function __construct(UserManagement $um, Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->um = $um;
		$this->config = $config;
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
	public function doLogin($username, $password, $writeCookie = false, $isPasswordEncrypted = false){

		if($this->um->checkCredentials($username, $password, $isPasswordEncrypted)){
			
			$this->usr = $this->um->getObjectByLogin($username);
			
			$this->authorize($this->usr);
			
			$this->saveUserId($this->usr->getId());
			if($writeCookie){
				$secs = getdate();
				$exp_time = $secs[0] + (60 * 60 * 24 * $this->config->rememberDaysCount);
				$cookie_value = $this->usr->getId() . ":" . hash('sha256', $username . ":" . md5($password));
				
				setcookie($this->config->loginCookieName, $cookie_value, $exp_time, '/');
			}
			
			if(Reg::get('packageMgr')->isPluginLoaded("Security", "RequestLimiter") and $this->config->bruteForceProtectionEnabled){
				$this->query->exec("DELETE FROM `".Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG')."` WHERE `ip`='".$_SERVER['REMOTE_ADDR']."'");
			}
		}
		else{
			if(Reg::get('packageMgr')->isPluginLoaded("Security", "RequestLimiter") and $this->config->bruteForceProtectionEnabled){
				$this->query->exec("SELECT `count` 
											FROM `".Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG')."` 
											WHERE `ip`='".$_SERVER['REMOTE_ADDR']."'");
				
				$failedAuthCount = $this->query->fetchField('count');
				
				$newFailedAuthCount = $failedAuthCount + 1;
				
				if($newFailedAuthCount >= $this->config->failedAuthLimit){
					Reg::get(ConfigManager::getConfig("Security", "RequestLimiter")->Objects->RequestLimiter)->blockIP();
					
					$this->query->exec("DELETE FROM `".Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG')."` WHERE `ip`='".$_SERVER['REMOTE_ADDR']."'");
					
					throw new RequestLimiterTooManyAuthTriesException("Too many unsucessful authorization tries.");
				}
				
				$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_INVALID_LOGINS_LOG')."` (`ip`) 
										VALUES ('".$_SERVER['REMOTE_ADDR']."')
										ON DUPLICATE KEY UPDATE `count` = `count` + 1");
			}
			
			throw new RuntimeException("Incorrect login/password combination", static::EXCEPTION_INCORRECT_LOGIN_PASSWORD);
		}
	}
	
	/**
	 * Logs in User only by knowing User ID
	 * @param int $userId
	 * @param bool $writeCookie
	 */
	public function doLoginByUserId($userId, $writeCookie = false){
		if(empty($userId)){
			throw new InvalidArgumentException("\$userId is empty!");
		}
		$this->doLogin($this->um->getLoginById($userId), $this->um->getPassword($userId), $writeCookie, true);
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
		$this->usr = new User();
		
		if(array_key_exists($this->config->sessionVarName, $_SESSION) and is_numeric($_SESSION[$this->config->sessionVarName])){
			$this->usr = $this->um->getObjectById($_SESSION[$this->config->sessionVarName]);
			$this->authorize($this->usr);
		}
		elseif(!empty($_COOKIE[$this->config->loginCookieName])){
			$cookieData = explode(":", $_COOKIE[$this->config->loginCookieName]);
			$userId = $cookieData[0];
			$encryptedSalt = $cookieData[1];
			
			$correctSalt = hash('sha256', $this->um->getLoginById($userId) . ":" . $this->um->getPassword($userId));
			
			if($encryptedSalt === $correctSalt){
				$this->usr = $this->um->getObjectById($userId);
				$this->doLogin($this->usr->getLogin(), $this->um->getPassword($this->usr->getId()), false, true);
			}
		}
		return $this->usr;
	}
	
	/**
	 * Check if user authorization is allowed 
	 * according to site rules
	 * 
	 * @param User $usr
	 */
	protected function authorize(User $usr){
		if(!$usr->isEnabled()){
			$this->doLogout();
			throw new RuntimeException("Account is disabled", static::EXCEPTION_ACCOUNT_DISABLED);
		}
	}
	
	/**
	 * Save userId for next requests
	 * 
	 * @param integer $userId
	 */
	protected function saveUserId($userId){
		$_SESSION[$this->config->sessionVarName] = $userId;
	} 
}
?>