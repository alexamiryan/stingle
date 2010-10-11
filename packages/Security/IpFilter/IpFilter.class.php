<?php
class IpFilter extends DbAccessor {
	
	const TBL_SECURITY_BLOCKEDIPS 	= "security_blockedips";
	const TBL_SECURITY_BLOCKEDIPRANGES = "security_blockedipranges";
	const TBL_SECURITY_BLOCKEDCOUNTRIES	= "security_blockedcountries";
	
	private $remoteIp;

	public function __construct ( ) {
		parent::__construct(); 
		
		if( false === ( $this->remoteIp = $this->getRemoteIp() ) )	{
			throw new Exception("Could not determine client ip address.");
		}
		
		$e = new IPBlockedException("Blocked host");
		//$e->setUserMessage("This site is not available for your country"); 
		
		if ( $this->checkIpInBlockedIpList() ) {
			throw $e;
		}
		
		if ( $this->checkIpInBlockedCountries() ) {
			throw $e;
		}
		
		if ( $this->checkIpInBlockedIpRanges() ) {
			throw $e;
		}
	}
	
	private function getRemoteIp() {
		
		$remoteIp = $_SERVER["REMOTE_ADDR"];
		
		if ( empty( $remoteIp ) ) {
			return false;
		}
		return $remoteIp;
	}
	
	private function checkIpInBlockedIpList () {
		$found = false;
		
		$this->query->exec("SELECT id FROM `".self::TBL_SECURITY_BLOCKEDIPS."`
							WHERE ip_address='".$this->remoteIp."'");
		$iplist = $this->query->fetchRecords();
		
		if ($this->query->countRecords() > 0) {
			
			$found = true;						
		}
		
		return $found;
	}
	
	private function checkIpInBlockedCountries () {
		global $gi;
		
		$countryCode = ""; 
		
		if ( false === ( $countryCode = geoip_country_code_by_addr($gi,$this->remoteIp) ) ) {
			return true;	
		}	
		
		$found = false;
		
		$this->query->exec("SELECT id FROM `".self::TBL_SECURITY_BLOCKEDCOUNTRIES."` 
							WHERE country='".$countryCode."'");
		
		if ($this->query->countRecords() > 0) {
			
			$found = true;						
		}
		$countries = $this->query->fetchRecords();

		return $found;
	}
	
	private function checkIpInBlockedIpRanges() { 
		
		$found = false;
		
		$this->query->exec("SELECT ip_range FROM `".self::TBL_SECURITY_BLOCKEDIPRANGES."`");
		$ranges = $this->query->fetchRecords();
		
		foreach ($ranges as $range) {
			$curRange = $range["ip_range"]; // a.b.*.* format
			
			if (strpos($curRange, '*') !==false) { 
		      $lower = str_replace('*', '0', $curRange);
		      $upper = str_replace('*', '255', $curRange);
		      $curRange = "$lower-$upper";
		    }
			if (strpos($curRange, '-')!==false) { // A-B format
				list($lower, $upper) = explode('-', $curRange, 2);
				$lower_dec = (float)sprintf("%u",ip2long($lower));
				$upper_dec = (float)sprintf("%u",ip2long($upper));
				$ip_dec = (float)sprintf("%u",ip2long($this->remoteIp));
				$found = ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
			}
			if($found) {
				break;
			}
		}
	    return $found;
	}	

}
?>