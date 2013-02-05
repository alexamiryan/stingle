<?php
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
	public function isBlocked($cacheMinutes = null){
		if(Cgi::getMode()){
			return true;
		}
		
		$isBlocked = $this->isBlockedByIP($cacheMinutes) || $this->isBlockedByCountry($cacheMinutes);
		$isWhiteListed = $this->isWhitelistedIP($cacheMinutes);
		
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
	private function isWhitelistedIP($cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count('*', 'count'))
			->from(Tbl::get('TBL_SECURITY_WHITELISTED_IPS'))
			->where($qb->expr()->equal(new Field('ip'), $this->remoteIp));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
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
	private function isBlockedByIP($cacheMinutes = null){
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count('*', 'count'))
			->from(Tbl::get('TBL_SECURITY_BLACKLISTED_IPS'))
			->where($qb->expr()->equal(new Field('ip'), $this->remoteIp));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
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
	private function isBlockedByCountry($cacheMinutes = null){
		$myLocation = Reg::get(ConfigManager::getConfig('GeoIP', 'GeoIP')->Objects->GeoIP)->getLocation();
		if(empty($myLocation)){
			return false;
		}
		
		$countryCode = $myLocation->country;
		
		if(empty($countryCode)){
			return false;
		}	
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count('*', 'count'))
			->from(Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES'))
			->where($qb->expr()->equal(new Field('country'), $countryCode));
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		
		$count = $this->query->fetchField('count');
		
		if ($count > 0) {
			return true;
		}
		return false;
	}
}
