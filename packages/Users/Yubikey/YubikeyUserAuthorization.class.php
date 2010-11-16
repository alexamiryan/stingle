<?
class YubikeyUserAuthorization extends UserAuthorization{
	
	const TBL_KEYS = "wum_yubico_keys";
	const TBL_KEYS_TO_USERS = "wum_yubico_keys_to_users";
	const TBL_KEYS_TO_GROUPS = "wum_yubico_keys_to_groups";
	const TBL_AUTH_USERS = "wum_yubico_auth_users";
	const TBL_AUTH_GROUPS = "wum_yubico_auth_groups";

	
	const STATUS_YUBIKEY_ENABLED = 1;
	const STATUS_YUBIKEY_DISABLED = 0;
	
	const EXCEPTION_INVALID_YUBIKEY = 4;
	
	protected $authYubikey;
	
	
	/**
	 * Class contructor
	 * @param var $usr
	 * @param var $sessionVar
	 */
	public function __construct(UserManagement $um, Config $config, $dbInstanceKey = null){
		parent::__construct($um, $config, $dbInstanceKey);
		
		$this->authYubikey = new Yubikey($config->yubico_id, $config->yubico_key);
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
			
			if(!in_array($this->getYubikeyKeyByOTP($yubikeyOTP), $available_yubikeys)){
				$this->doLogout();
				throw new RuntimeException("Invalid Yubikey", static::EXCEPTION_INVALID_YUBIKEY);
			}
			elseif(!$this->authYubikey->verify($yubikeyOTP)){
				$this->doLogout();
				throw new RuntimeException("Yubikey Validation Failed", static::EXCEPTION_INVALID_YUBIKEY);
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
								FROM `".Tbl::get('TBL_USERS_GROUPS', 'UserManagement')."` `ug`
								INNER JOIN `".Tbl::get('TBL_AUTH_GROUPS')."` `yag` ON (`ug`.`group_id` = `yag`.`group_id`)
								WHERE `ug`.`user_id`='$user_id'",
							$cacheMinutes);
		$groups_count = $this->query->fetchField("cnt");
		
		$this->query->exec("SELECT count(*) AS `cnt`
								FROM `".Tbl::get('TBL_AUTH_USERS')."` `yau`
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
								FROM `".Tbl::get('TBL_USERS_GROUPS', 'UserManagement')."` `ug`
								INNER JOIN `".Tbl::get('TBL_KEYS_TO_GROUPS')."` `yg` ON (`ug`.`group_id` = `yg`.`group_id`)
								INNER JOIN `".Tbl::get('TBL_KEYS')."` `yk`  ON (`yk`.`id` = `yg`.`yubikey_id`)
								WHERE `ug`.`user_id`='$user_id' AND `yk`.`status` = '".static::STATUS_YUBIKEY_ENABLED."'
							UNION
							SELECT `yk`.`key`
								FROM `".Tbl::get('TBL_KEYS_TO_USERS')."` `yu`
								INNER JOIN `".Tbl::get('TBL_KEYS')."` `yk` ON (`yu`.`yubikey_id` = `yk`.`id`)
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