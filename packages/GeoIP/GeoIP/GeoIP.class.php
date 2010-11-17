<?
class GeoIP extends DbAccessor
{
	const TBL_BLOCKS = 'GeoIPBlocks';
	const TBL_LOCATIONS = 'GeoIPLocation';
	
	/**
	 * Get location by IP address
	 * @param string $ip
	 * @return GeoLocation
	 */
	public function getLocation($ip = null){
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		if(!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip)){
			throw new InvalidArgumentException('IP is in wrong format');
		}
		$this->query->exec("SELECT `country`, `region`, `city` FROM ".Tbl::get('TBL_BLOCKS')."
							LEFT JOIN ".Tbl::get('TBL_LOCATIONS')." USING (`locId`)
							WHERE index_geo = INET_ATON('".$ip."')-(INET_ATON('".$ip."')%65536) AND INET_ATON('".$ip."') BETWEEN `startIpNum` AND `endIpNum`");
		if($this->query->countRecords()){
			$row = $this->query->fetchRecord();
			$location = new GeoLocation();
			$location->country = $row['country'];
			$location->region = $row['region'];
			$location->city = $row['city'];
			
			return $location;
		}
		return null;
	}
	
	/**
	 * Get country code by IP
	 */
	public function getCountryCode($ip = null){
		if($ip === null){
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$this->query->exec("SELECT `country` FROM ".Tbl::get('TBL_BLOCKS')."
							LEFT JOIN ".Tbl::get('TBL_LOCATIONS')." USING (`locId`)
							WHERE index_geo = INET_ATON('".$ip."')-(INET_ATON('".$ip."')%65536) AND INET_ATON('".$ip."') BETWEEN `startIpNum` AND `endIpNum`");
		if($this->query->countRecords()){
			return $this->query->fetchField('country');
		}		
		return null;
	}
}
?>