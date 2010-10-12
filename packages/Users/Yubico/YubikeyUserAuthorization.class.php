<?
/**
 * Obligatory constants
 *
 * define('YUBICO_ID', 'XXX');
 * define('YUBICO_KEY', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXX');
 */

class YubikeyUserAuthorization extends UserAuthorization{
	
	const STATUS_YUBIKEY_ENABLED = 1;
	
	const STATUS_YUBIKEY_DISABLED = 0;
	
	const EXCEPTION_INVALID_YUBIKEY = 4;
	
	protected $authYubikey;
	
	/**
	 * @var string
	 */
	const TBL_KEYS = "wum_yubico_keys";

	/**
	 * @var string
	 */
	const TBL_KEYS_TO_USERS = "wum_yubico_keys_to_users";
	
	/**
	 * @var string
	 */
	const TBL_KEYS_TO_GROUPS = "wum_yubico_keys_to_groups";

	/**
	 * @var string
	 */
	const TBL_AUTH_USERS = "wum_yubico_auth_users";
	
	/**
	 * @var string
	 */
	const TBL_AUTH_GROUPS = "wum_yubico_auth_groups";

	
	/**
	 * Class contructor
	 * @param var $usr
	 * @param var $sessionVar
	 */
	public function __construct(UserManagement $um, &$sessionVar, Config $configYubico){
		parent::__construct($um, $sessionVar);
		
		$this->authYubikey = new Auth_Yubico($configYubico->yubico_id, $configYubico->yubico_key);
	}
	
	/**
	 * Does login operation
	 * @param string $username
	 * @param string $password
	 * @param string $yubikeyOTP
	 * @param bool $writeCookie
	 * @param bool $isPasswordEncrypted
	 *
	 * @throws RuntimeException (Codes: 1 - Incorrect login/password combination,
	 * 									2 - Account is disabled
	 * 									3 - User is not in Users group
	 * 									4 - Invalid Yubikey)
	 */
	public function doLogin($username, $password, $writeCookie = false, $isPasswordEncrypted = false, $yubikeyOTP = null){
		
		parent::doLogin($username, $password, $writeCookie, $isPasswordEncrypted);
		
		if($this->isYubikeyRequired($this->usr->getId())){
			$available_yubikeys = $this->getAvailableYubikeysList($this->usr->getId());
			try{
				if(!in_array($this->getYubikeyKeyByOTP($yubikeyOTP), $available_yubikeys)){
					throw new RuntimeException("Invalid Yubikey", static::EXCEPTION_INVALID_YUBIKEY);
				}
				else{
					$authResult = $this->authYubikey->verify($yubikeyOTP);
					if (PEAR::isError($authResult)) {
						throw new RuntimeException("Yubikey Validation Failed", static::EXCEPTION_INVALID_YUBIKEY);
					}
				}
			}
			catch (RuntimeException $e){
				$this->doLogout();
				throw $e;
			}
		}
	}
	
	/**
	 * Checks whether Yubikey is obligatory for given user
	 * @param int $user_id
	 * @param int $cacheMinutes
	 */
	public function isYubikeyRequired($user_id, $cacheMinutes = null){
		$this->query->exec("SELECT count(*) AS `cnt`
								FROM `".UserManagement::TBL_USERS_GROUPS."` `ug`
								INNER JOIN `".static::TBL_AUTH_GROUPS."` `yag` ON (`ug`.`group_id` = `yag`.`group_id`)
								WHERE `ug`.`user_id`='$user_id'",
							$cacheMinutes);
		$groups_count = $this->query->fetchField("cnt");
		
		$this->query->exec("SELECT count(*) AS `cnt`
								FROM `".static::TBL_AUTH_USERS."` `yau`
								WHERE `yau`.`user_id`='$user_id'",
							$cacheMinutes);
		$users_count = $this->query->fetchField("cnt");
		
		if(($groups_count+$users_count)>0){
			return true;
		}
		else{
			return false;
		}
	}
	
	/**
	 * Gets array of registered Yubikeys for given user
	 * @param int $user_id
	 * @param int $cacheMinutes
	 */
	protected function getAvailableYubikeysList($user_id, $cacheMinutes = null){
		$this->query->exec("SELECT `yk`.`key`
								FROM `".UserManagement::TBL_USERS_GROUPS."` `ug`
								INNER JOIN `".static::TBL_KEYS_TO_GROUPS."` `yg` ON (`ug`.`group_id` = `yg`.`group_id`)
								INNER JOIN `".static::TBL_KEYS."` `yk`  ON (`yk`.`id` = `yg`.`yubikey_id`)
								WHERE `ug`.`user_id`='$user_id' AND `yk`.`status` = '".static::STATUS_YUBIKEY_ENABLED."'
							UNION
							SELECT `yk`.`key`
								FROM `".static::TBL_KEYS_TO_USERS."` `yu`
								INNER JOIN `".static::TBL_KEYS."` `yk` ON (`yu`.`yubikey_id` = `yk`.`id`)
								WHERE `yu`.`user_id`='$user_id'  AND `yk`.`status` = '".static::STATUS_YUBIKEY_ENABLED."'",
							$cacheMinutes);
		return $this->query->fetchFields("key");
	}
	
	/**
	 * Returns Yubikey key from OTP
	 * @param string $otp
	 */
	private function getYubikeyKeyByOTP($otp){
		return substr($otp, 0, 12);
	}
}
?>