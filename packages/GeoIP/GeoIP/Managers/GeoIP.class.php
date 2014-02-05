<?php
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
				
				$qb = new QueryBuilder();
				$qb->select(array(
							new Field('country', 'loc'),
							new Field('region', 'loc'),
							new Field('city', 'loc'),
							new Field('latitude', 'loc'),
							new Field('longitude', 'loc')
						))
						->from(Tbl::get('TBL_BLOCKS'), 'blocks')
						->leftJoin(
								Tbl::get('TBL_LOCATIONS'), 
								'loc', 
								$qb->expr()->equal(new Field('locId', 'blocks'), new Field('locId', 'loc'))
						)
						->where($qb->expr()->greaterEqual(new Field('endIpNum'), new Func('INET_ATON', $ip)))
						->limit(1);

				$this->query->exec($qb->getSQL(), $cacheMinutes);
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
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->count("*", "count"))
			->from(Tbl::get('TBL_LOCATIONS'))
			->where($qb->expr(new Field('country'), $countryCode));
		
		$this->query->exec($qb->getSQL(), $cacheMinutes);
		$count = $this->query->fetchField('count');
		if($count > 0){
			return true;
		}
		return false;
	}
}
