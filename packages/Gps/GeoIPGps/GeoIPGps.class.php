<?php
class GeoIPGps
{
	protected $geoIP;
	protected $gps;
	
	public function __construct(GeoIP $geoIP, Gps $gps){
		$this->geoIP = $geoIP;
		$this->gps = $gps;
	}
	
	public function getGpsByIP($ip = null, $type = 'CITY'){
		if($type == 'COUNTRY'){
			throw new InvalidArgumentException("Ilegal use of \$type variable. If you want to get country gps use getCountryGpsByIP method.");
		}
		
		$gpsToReturn = null;
		
		$location = $this->geoIP->getLocation($ip);
		
		$myGpses = $this->gps->getNodesByName(mysql_real_escape_string($location->city), $this->gps->getTypeId($type));		
		if(count($myGpses) > 1){
			// More than one node with same name
			// Check country
			foreach ($myGpses as $myGps){
				$country = $this->gps->getParentByType($myGps['id'], $this->gps->getTypeId('COUNTRY'));
				if($country['id'] == getValue($this->gps->getCountryByCode($location->country),'gps_id')){
					$gpsToReturn = $myGps;
					break;
				}
			}
		}
		else{
			// Only one city in gps databse
			$gpsToReturn = $myGpses[0];
		}
		
		return $gpsToReturn;
	}
	
	public function getCountryGpsByIP($ip = null){	
		$location = $this->geoIP->getLocation($ip);
		
		if($location){
			$country = $this->gps->getCountryByCode($location->country);
			if(!empty($country)){
				return $country['gps_id'];
			}
		}
		return null;
	}
}
?>