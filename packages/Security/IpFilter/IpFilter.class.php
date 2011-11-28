<?
class IpFilter extends DbAccessor {
	
	const TBL_SECURITY_WHITELISTED_IPS	= "security_whitelisted_ips";
	const TBL_SECURITY_BLACKLISTED_IPS 	= "security_blacklisted_ips";
	const TBL_SECURITY_BLACKLISTED_COUNTRIES	= "security_blacklisted_countries";
	
	private $remoteIp = null;

	public function __construct ($dbInstanceKey = null) {
		parent::__construct($dbInstanceKey); 
		
		$this->remoteIp = $_SERVER["REMOTE_ADDR"];
		if(empty($this->remoteIp) and !Cgi::getMode()){
			throw new RuntimeException("Could not determine client ip address.");
		}
	}
	
	/**
	 * Check if remote IP is blocked by our system
	 * 
	 * @return boolean
	 */
	public function isBlocked(){
		if(Cgi::getMode()){
			return true;
		}
		
		$isBlocked = $this->isBlockedByIP() || $this->isBlockedByCountry();
		$isWhiteListed = $this->isWhitelistedIP();
		
		if($isBlocked and !$isWhiteListed){
			return true;
		}
		return false;
	}
	
	/**
	 * Is remote IP in our whitelist list
	 * 
	 * @return boolean
	 */
	private function isWhitelistedIP(){
		$this->query->exec("SELECT count(*) as `count` 
								FROM `".Tbl::get('TBL_SECURITY_WHITELISTED_IPS')."`
								WHERE `ip` = '{$this->remoteIp}'");
		
		$count = $this->query->fetchField('count');
		
		if ($count > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * Is remote IP is in out blacklist list
	 * 
	 * @return boolean
	 */
	private function isBlockedByIP(){
		$this->query->exec("SELECT count(*) as `count` 
								FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_IPS')."`
								WHERE `ip` = '{$this->remoteIp}'");
		
		$count = $this->query->fetchField('count');
		
		if ($count > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * Is remote IP blocked by country
	 * 
	 * @return boolean
	 */
	private function isBlockedByCountry(){
		$myLocation = Reg::get(ConfigManager::getConfig('GeoIP', 'GeoIP')->Objects->GeoIP)->getLocation();
		if(empty($myLocation)){
			return false;
		}
		
		$countryCode = $myLocation->country;
		
		if(empty($countryCode)){
			return false;
		}	
		
		$this->query->exec("SELECT count(*) as `count` 
								FROM `".Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES')."` 
								WHERE `country` = '$countryCode'");
		
		$count = $this->query->fetchField('count');
		
		if ($count > 0) {
			return true;
		}
		return false;
	}
}
?>