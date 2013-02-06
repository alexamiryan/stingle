<?
class YubikeyUserAuthorization extends DbAccessor{
	
	const TBL_KEYS = "wum_yubico_keys";
	const TBL_KEYS_TO_USERS = "wum_yubico_keys_to_users";
	const TBL_KEYS_TO_GROUPS = "wum_yubico_keys_to_groups";
	const TBL_AUTH_USERS = "wum_yubico_auth_users";
	const TBL_AUTH_GROUPS = "wum_yubico_auth_groups";
	
	const STATUS_YUBIKEY_ENABLED = 1;
	const STATUS_YUBIKEY_DISABLED = 0;
	
	protected $authYubikey;
	
	
	/**
	 * Class contructor
	 * @param var $usr
	 * @param var $sessionVar
	 */
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		$this->authYubikey = new Yubikey($config->yubico_id, $config->yubico_key);
	}
	
	
	public function auth(User $user, $yubikeyOTP = null){
		
		if($this->isYubikeyRequired($user->id)){
			if(empty($yubikeyOTP)){
				throw new YubikeyRequiredException("Yubikey is required for authorization");
			}
			
			$available_yubikeys = $this->getAvailableYubikeysList($user->id);
			
			if(!in_array($this->getYubikeyKeyByOTP($yubikeyOTP), $available_yubikeys)){
				throw new InvalidYubikeyException("Invalid Yubikey");
			}
			else{
				try{
					$this->authYubikey->verify($yubikeyOTP);
				}
				catch(YubikeyException $e){
					throw new InvalidYubikeyException("Yubikey Validation Failed");
				}
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
								FROM `".Tbl::get('TBL_USERS_GROUPS', 'UserManager')."` `ug`
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
								FROM `".Tbl::get('TBL_USERS_GROUPS', 'UserManager')."` `ug`
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