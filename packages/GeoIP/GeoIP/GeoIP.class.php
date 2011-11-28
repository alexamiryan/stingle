<?
class GeoIP extends DbAccessor
{
	const TBL_BLOCKS = 'GeoIPBlocks';
	const TBL_LOCATIONS = 'GeoIPLocation';
	
	/**
	 * Get location by IP address
	 * 
	 * @param string $ip
	 * @param int $cacheMinutes
	 * @return GeoLocation|null
	 */
	public function getLocation($ip = null, $cacheMinutes = null){
		if(isset($_SERVER['REMOTE_ADDR'])){
			if($ip === null){
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			if(!empty($ip)){
				if(!preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip)){
					throw new InvalidArgumentException('IP is in wrong format');
				}
				$this->query->exec("SELECT `country`, `region`, `city`, `latitude`,	`longitude` FROM ".Tbl::get('TBL_BLOCKS')."
									LEFT JOIN ".Tbl::get('TBL_LOCATIONS')." USING (`locId`)
									WHERE index_geo = INET_ATON('".$ip."')-(INET_ATON('".$ip."')%65536) AND INET_ATON('".$ip."') BETWEEN `startIpNum` AND `endIpNum`", $cacheMinutes);
				if($this->query->countRecords()){
					$row = $this->query->fetchRecord();
					$location = new GeoLocation();
					$location->country = $row['country'];
					$location->region = $row['region'];
					$location->city = $row['city'];
					$location->latitude = $row['latitude'];
					$location->longitude = $row['longitude'];
					
					return $location;
				}
			}
		}
		return null;
	}
	
	/**
	 * Get country code by IP
	 * 
	 * @param string $ip
	 * @param int $cacheMinutes
	 * @return string|false
	 */
	public function getCountryCode($ip = null, $cacheMinutes = null){
		$location = static::getLocation($ip, $cacheMinutes);
		if(!empty($location)){
			return $location->country;
		}
		return false;
	}
	
	/**
	 * Check if given country code is valid
	 * 
	 * @param string $countryCode
	 * @param int $cacheMinutes
	 */
	public function isValidCountryCode($countryCode = null, $cacheMinutes = null){
		$this->query->exec("SELECT count(*) as `count` FROM ".Tbl::get('TBL_LOCATIONS')."
								WHERE `country`='$countryCode'", $cacheMinutes);
		$count = $this->query->fetchField('count');
		if($count > 0){
			return true;
		}
		return false;
	}
}
?>