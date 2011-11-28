<?
class IpFilterManager extends DbAccessor {
	
	public function __construct ($dbInstanceKey = null) {
		parent::__construct($dbInstanceKey); 
		
	}
	
	/**
	 * Add IP to IPs blacklist
	 * 
	 * @param string $ip
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function blackListIP($ip){
		if(!preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/',$ip)){
			throw new InvalidArgumentException("Invalid IP specified for blacklisting");
		}
		$this->query->exec("SELECT count(*) as `count` FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter')."`
								WHERE `ip`='$ip'");
		if($this->query->fetchField('count') != 0){
			throw new RuntimeException("Sorry, this IP already blacklisted!");
		}
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter')."` 
								(`ip`) VALUES ('$ip') ");
	}
	
	/**
	 * Remove IP from IPs blacklist
	 * 
	 * @param string $ip
	 * @throws RuntimeException
	 */
	public function removeFromBlackListIP($ip){
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter')."` 
								WHERE `ip` = '$ip'");
		
		if($this->query->affected() == 0){
			throw new RuntimeException("Given IP $ip is not blacklisted!");
		}
	}
	
	/**
	 * Add IP to IPs whitelist
	 * 
	 * @param string $ip
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function whiteListIP($ip){
		if(!preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/',$ip)){
			throw new InvalidArgumentException("Invalid IP specified for whitelisting");
		}
		
		$this->query->exec("SELECT count(*) as `count` FROM `".Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter')."`
								WHERE `ip`='$ip'");
		if($this->query->fetchField('count') != 0){
			throw new RuntimeException("Sorry, this IP already whitelisted!");
		}
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter')."` 
								(`ip`) VALUES ('$ip') ");
	}
	
	/**
	 * Remove IP from IPs whitelist
	 * 
	 * @param string $ip
	 * @throws RuntimeException
	 */
	public function removeFromWhiteListIP($ip){
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter')."` 
								WHERE `ip` = '$ip'");
		
		if($this->query->affected() == 0){
			throw new RuntimeException("Given IP $ip is not whitelisted!");
		}
	}
	
	/**
	 * Blacklist given country
	 * 
	 * @param string $countryCode
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function blackListCountry($countryCode){
		if(!Reg::get(ConfigManager::getConfig('GeoIP', 'GeoIP')->Objects->GeoIP)->isValidCountryCode($countryCode)){
			throw new InvalidArgumentException("Invalid country code specified for blacklisting");
		}
		
		$this->query->exec("SELECT count(*) as `count` FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter')."`
								WHERE `country`='$countryCode'");
		if($this->query->fetchField('count') != 0){
			throw new RuntimeException("Sorry, this country already blacklisted!");
		}
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter')."` 
								(`country`) VALUES ('$countryCode') ");
	}
	
	/**
	 * Remove country blacklist
	 * 
	 * @param string $countryCode
	 * @throws RuntimeException
	 */
	public function removeFromBlackListCountry($countryCode){
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter')."` 
								WHERE `country` = '$countryCode'");
		
		if($this->query->affected() == 0){
			throw new RuntimeException("Given country $countryCode is not blacklisted!");
		}
	}
	
	/**
	 * Get list of blacklisted IPs
	 * 
	 * @return array
	 */
	public function getBlacklistedIps(){
		$this->query->exec("SELECT `ip` FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter')."`");
		
		return $this->query->fetchFields('ip');
	}
	
	/**
	 * Get list of whitelisted IPs
	 * 
	 * @return array
	 */
	public function getWhitelistedIps(){
		$this->query->exec("SELECT `ip` FROM `".Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter')."`");
		
		return $this->query->fetchFields('ip');
	}
	
	/**
	 * Get list of blacklisted countries
	 * 
	 * @return array
	 */
	public function getBlacklistedCountries(){
		$this->query->exec("SELECT `country` FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter')."`");
		
		return $this->query->fetchFields('country');
	}
}
?>