<?php
class IpFilterManager extends DbAccessor {
	
	public function __construct ($instanceName = null) {
		parent::__construct($instanceName); 
		
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
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count('*', 'count'))
			->from(Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter'))
			->where($qb->expr()->equal(new Field('ip'), $ip));
			
		$this->query->exec($qb->getSQL());
		if($this->query->fetchField('count') != 0){
			throw new RuntimeException("Sorry, this IP already blacklisted!");
		}
		
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter'))
			->values(array( "ip" => $ip	));	
		$this->query->exec($qb->getSQL());
	}
	
	/**
	 * Remove IP from IPs blacklist
	 * 
	 * @param string $ip
	 * @throws RuntimeException
	 */
	public function removeFromBlackListIP($ip){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter'))
			->where($qb->expr()->equal(new Field("ip"), $ip));
		$this->query->exec($qb->getSQL());
		
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
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count('*', 'count'))
			->from(Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter'))
			->where($qb->expr()->equal(new Field('ip'), $ip));
		$this->query->exec($qb->getSQL());
		if($this->query->fetchField('count') != 0){
			throw new RuntimeException("Sorry, this IP already whitelisted!");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter'))
			->values(array( "ip" => $ip	));	
		$this->query->exec($qb->getSQL());
	}
	
	/**
	 * Remove IP from IPs whitelist
	 * 
	 * @param string $ip
	 * @throws RuntimeException
	 */
	public function removeFromWhiteListIP($ip){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter'))
			->where($qb->expr()->equal(new Field("ip"), $ip));
		$this->query->exec($qb->getSQL());
		
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
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count('*', 'count'))
			->from(Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter'))
			->where($qb->expr()->equal(new Field('country'), $countryCode));
			
		$this->query->exec($qb->getSQL());
		if($this->query->fetchField('count') != 0){
			throw new RuntimeException("Sorry, this country already blacklisted!");
		}
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter'))
			->values(array( "country" => $countryCode ));	
		$this->query->exec($qb->getSQL());
	}
	
	/**
	 * Remove country blacklist
	 * 
	 * @param string $countryCode
	 * @throws RuntimeException
	 */
	public function removeFromBlackListCountry($countryCode){
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter'))
			->where($qb->expr()->equal(new Field("country"), $countryCode));
		$this->query->exec($qb->getSQL());
		
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
		$qb = new QueryBuilder();
		$qb->select(new Field('ip'))
			->from(Tbl::get('TBL_SECURITY_BLACKLISTED_IPS', 'IpFilter'));
		$this->query->exec($qb->getSQL());
		
		return $this->query->fetchFields('ip');
	}
	
	/**
	 * Get list of whitelisted IPs
	 * 
	 * @return array
	 */
	public function getWhitelistedIps(){
		$qb = new QueryBuilder();
		$qb->select(new Field('ip'))
			->from(Tbl::get('TBL_SECURITY_WHITELISTED_IPS', 'IpFilter'));
		$this->query->exec($qb->getSQL());
		
		return $this->query->fetchFields('ip');
	}
	
	/**
	 * Get list of blacklisted countries
	 * 
	 * @return array
	 */
	public function getBlacklistedCountries(){
		$qb = new QueryBuilder();
		$qb->select(new Field('country'))
			->from(Tbl::get('TBL_SECURITY_BLACKLISTED_COUNTRIES', 'IpFilter'));
		$this->query->exec($qb->getSQL());
		
		return $this->query->fetchFields('country');
	}
}
