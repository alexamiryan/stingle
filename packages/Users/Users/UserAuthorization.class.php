<?
/**
 * Obligatory constants
 *
 * define('LOGIN_COKIE', "site_name-login");
 */

class UserAuthorization extends DbAccessor{
	
	protected $usr;
	protected $sessionVar;
	protected $um;
	
	const REMEMBER_DAYS = 30;
	
	const EXCEPTION_INCORRECT_LOGIN_PASSWORD = 1;
	const EXCEPTION_ACCOUNT_DISABLED = 2;
	
	public function __construct(UserManagement $um, &$sessionVar){
		parent::__construct();
		
		$this->um = $um;
		$this->sessionVar = &$sessionVar;
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
		if($this->um->authorization($username, $password, $isPasswordEncrypted)){
			$this->usr = $this->um->getObjectByLogin($username);
		
			$this->serializeUserObject($this->usr);
			if($writeCookie){
				$secs = getdate();
				$exp_time = $secs[0] + (60 * 60 * 24 * static::REMEMBER_DAYS);
				$cookie_value = $username . ":" . md5($password);
				setcookie(LOGIN_COKIE, $cookie_value, $exp_time, '/');
			}
		}
		elseif($this->um->isUserExists($username)){
			$current_user = $this->um->getObjectById($this->um->getIdByLogin($username));
			if($this->um->getPassword($current_user->getId()) == md5($password) and !$current_user->isEnabled()){
				throw new RuntimeException("Account is disabled", static::EXCEPTION_ACCOUNT_DISABLED);
			}
			else{
				throw new RuntimeException("Incorrect login/password combination", static::EXCEPTION_INCORRECT_LOGIN_PASSWORD);
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
		$this->usr = new User();
		$this->serializeUserObject($this->usr);
		setcookie(LOGIN_COKIE, null, null, '/');
	}
	
	/**
	 * Authorizes user at every request
	 */
	public function authorize(){
		
		$this->doLoginByCookie();
		
		if(isset($this->sessionVar) and !empty($this->sessionVar)){
			$this->usr = unserialize($this->sessionVar);
			if(!is_a($this->usr, "User")){
				$this->usr = new User();
			}
		}
		else{
			$this->usr = new User();
		}
		
		return $this->usr;
	}
	
	protected function doLoginByCookie(){
		if(!empty($_COOKIE[LOGIN_COKIE]) and empty($this->sessionVar)){
			$cookieData = explode(":", $_COOKIE[LOGIN_COKIE]);
			$login = $cookieData[0];
			$encryptedPassword = $cookieData[1];
			
			if($this->um->authorization($login, $encryptedPassword, true)){
				$this->doLogin($login, $encryptedPassword, true, true);
			}
		}
	}
	
	public function serializeUserObject($usr){
		$this->sessionVar = serialize($usr);
	}
}
?>