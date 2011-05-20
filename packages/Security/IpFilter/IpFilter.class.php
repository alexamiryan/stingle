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
	
	private function isBlockedByCountry(){
		$countryCode = Reg::get(ConfigManager::getConfig('GeoIP', 'GeoIP')->Objects->GeoIP)->getLocation()->country;
		
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