<?
class UserAuthorization extends DbAccessor{
	
	protected $usr;
	protected $um;
	protected $config;
	
	const REMEMBER_DAYS = 30;
	
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
				$exp_time = $secs[0] + (60 * 60 * 24 * static::REMEMBER_DAYS);
				$cookie_value = $this->usr->getId() . ":" . hash('sha256', $username . ":" . md5($password));
				
				setcookie($this->config->loginCookieName, $cookie_value, $exp_time, '/');
			}
		}
		else{
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
		
		if(is_numeric($_SESSION[$this->config->sessionVarName])){
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