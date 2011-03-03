<?php
class GeoIPGps
{
	protected $geoIP;
	protected $gps;
	
	public function __construct(GeoIP $geoIP, Gps $gps){
		$this->geoIP = $geoIP;
		$this->gps = $gps;
	}
	
	public function getGpsByIP($ip = null, $type = 'CITY', $cacheMinutes = null){
		if($type == 'COUNTRY'){
			throw new InvalidArgumentException("Ilegal use of \$type variable. If you want to get country gps use getCountryGpsByIP method.");
		}
		
		$gpsToReturn = null;
		
		$location = $this->geoIP->getLocation($ip);
		
		$type_id = $this->gps->getTypeId($type);
		$myGpses = $this->gps->getNodesByName(mysql_real_escape_string($location->city), $type_id, false, $cacheMinutes);		
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
		elseif(count($myGpses) == 1){
			// Only one city in gps databse
			$gpsToReturn = $myGpses[0];
		}
		else{
			// No node with this name.
			// Take closest by latitude and longitude node.
			$gpsToReturn = $this->gps->getClosestNode($location->latitude, $location->longitude, $type_id);
		}
		
		return $gpsToReturn;
	}
	
	public function getCountryGpsByIP($ip = null, $cacheMinutes = null){	
		$location = $this->geoIP->getLocation($ip, $cacheMinutes);
		
		if($location){
			$country = $this->gps->getCountryByCode($location->country, $cacheMinutes);
			if(!empty($country)){
				return $country['gps_id'];
			}
		}
		return null;
	}
}
?>